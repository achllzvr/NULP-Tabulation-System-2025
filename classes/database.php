<?php

class database {
    
    // Function to open connection with database
    function opencon() {
        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $db   = getenv('DB_NAME') ?: 'NULP-Tabulation-DB';
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASS') ?: '';
        
        $con = new mysqli($host, $user, $pass, $db);
        if ($con->connect_error) {
            die("Connection failed: " . $con->connect_error);
        }
        return $con;
    }

    // Function to login admin user
    function loginAdmin($username, $password) {
        $con = $this->opencon();
        
        $stmt = $con->prepare("SELECT u.id, u.username, u.full_name, u.password_hash, u.global_role, u.is_active, pu.pageant_id, pu.role 
                               FROM users u 
                               LEFT JOIN pageant_users pu ON u.id = pu.user_id 
                               WHERE u.username = ? AND u.is_active = 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password_hash'])) {
                // Check if user has ADMIN role or is SUPERADMIN
                if ($user['role'] == 'ADMIN' || $user['global_role'] == 'SUPERADMIN') {
                    $stmt->close();
                    $con->close();
                    return $user;
                }
            }
        }
        
        $stmt->close();
        $con->close();
        return false;
    }

    // Function to login judge user with pageant code validation
    function loginJudge($pageant_code, $username, $password) {
        $con = $this->opencon();
        
        // First validate the pageant code and get pageant_id
        $stmt = $con->prepare("SELECT id FROM pageants WHERE UPPER(code) = UPPER(?)");
        $stmt->bind_param("s", $pageant_code);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            $stmt->close();
            $con->close();
            return false; // Invalid pageant code
        }
        
        $pageant = $result->fetch_assoc();
        $pageant_id = $pageant['id'];
        $stmt->close();
        
        // Now validate judge credentials for this specific pageant
        $stmt = $con->prepare("SELECT u.id, u.username, u.full_name, u.password_hash, u.global_role, u.is_active, pu.pageant_id, pu.role 
                               FROM users u 
                               JOIN pageant_users pu ON u.id = pu.user_id 
                               WHERE u.username = ? AND u.is_active = 1 AND pu.pageant_id = ? AND pu.role = 'judge'");
        $stmt->bind_param("si", $username, $pageant_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password_hash'])) {
                $stmt->close();
                $con->close();
                return $user;
            }
        }
        
        $stmt->close();
        $con->close();
        return false;
    }

    // Function to get pageant by code
    function getPageantByCode($code) {
        $con = $this->opencon();
        
        $stmt = $con->prepare("SELECT * FROM pageants WHERE UPPER(code) = UPPER(?)");
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $pageant = $result->fetch_assoc();
            $stmt->close();
            $con->close();
            return $pageant;
        }
        
        $stmt->close();
        $con->close();
        return false;
    }

    // Function to get pageant rounds
    function getPageantRounds($pageant_id) {
        $con = $this->opencon();
        
        $stmt = $con->prepare("SELECT * FROM rounds WHERE pageant_id = ? ORDER BY sequence");
        $stmt->bind_param("i", $pageant_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $rounds = [];
        while ($row = $result->fetch_assoc()) {
            $rounds[] = $row;
        }
        
        $stmt->close();
        $con->close();
        return $rounds;
    }

    // Function to get visibility flags
    function getVisibilityFlags($pageant_id) {
        $con = $this->opencon();
        
        // Get settings from pageant_settings table
        $stmt = $con->prepare("SELECT setting_key, setting_value FROM pageant_settings WHERE setting_key IN ('reveal_names', 'reveal_scores', 'reveal_awards')");
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Initialize default flags
        $flags = [
            'reveal_names' => false,
            'reveal_scores' => false,
            'reveal_awards' => false
        ];
        
        // Update flags based on database values
        while ($row = $result->fetch_assoc()) {
            $flags[$row['setting_key']] = (bool)(int)$row['setting_value'];
        }
        
        $stmt->close();
        $con->close();
        return $flags;
    }

    // Function to calculate leaderboard for a specific round
    function getRoundLeaderboard($round_id, $division = 'all') {
        $con = $this->opencon();
        
        $divisionFilter = '';
        $params = [$round_id];
        $types = 'i';
        
        if ($division !== 'all') {
            $divisionFilter = ' AND d.name = ?';
            $params[] = $division;
            $types .= 's';
        }
        
        $sql = "SELECT 
            p.id,
            p.full_name,
            p.number_label,
            d.name as division,
            SUM(COALESCE(s.override_score, s.raw_score) * (rc.weight / 100.0)) as total_score
        FROM participants p
        JOIN divisions d ON p.division_id = d.id
        LEFT JOIN scores s ON p.id = s.participant_id
        LEFT JOIN round_criteria rc ON s.criterion_id = rc.criterion_id AND rc.round_id = ?
        WHERE p.is_active = 1" . $divisionFilter . "
        GROUP BY p.id, p.full_name, p.number_label, d.name
        ORDER BY total_score DESC, p.full_name ASC";
        
        $stmt = $con->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $leaderboard = [];
        $rank = 1;
        $prev_score = null;
        $actual_rank = 1;
        
        while ($row = $result->fetch_assoc()) {
            $current_score = (float)($row['total_score'] ?? 0);
            
            // Handle ties
            if ($prev_score !== null && $current_score !== $prev_score) {
                $rank = $actual_rank;
            }
            
            $leaderboard[] = [
                'id' => $row['id'],
                'rank' => $rank,
                'name' => $row['full_name'],
                'number_label' => $row['number_label'],
                'division' => $row['division'],
                'score' => $current_score > 0 ? number_format($current_score, 2) : null,
                'raw_score' => $current_score
            ];
            
            $prev_score = $current_score;
            $actual_rank++;
        }
        
        $stmt->close();
        $con->close();
        return $leaderboard;
    }

    // Function to calculate overall leaderboard across all finalized rounds
    function getOverallLeaderboard($pageant_id, $division = 'all') {
        $con = $this->opencon();
        
        $divisionFilter = '';
        $params = [$pageant_id];
        $types = 'i';
        
        if ($division !== 'all') {
            $divisionFilter = ' AND d.name = ?';
            $params[] = $division;
            $types .= 's';
        }
        
        $sql = "SELECT 
            p.id,
            p.full_name,
            p.number_label,
            d.name as division,
            SUM(COALESCE(s.override_score, s.raw_score) * (rc.weight / 100.0)) as total_score
        FROM participants p
        JOIN divisions d ON p.division_id = d.id
        LEFT JOIN scores s ON p.id = s.participant_id
        LEFT JOIN round_criteria rc ON s.criterion_id = rc.criterion_id
        LEFT JOIN rounds r ON rc.round_id = r.id
        WHERE r.pageant_id = ? AND r.state IN ('CLOSED', 'FINALIZED') AND p.is_active = 1" . $divisionFilter . "
        GROUP BY p.id, p.full_name, p.number_label, d.name
        ORDER BY total_score DESC, p.full_name ASC";
        
        $stmt = $con->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $leaderboard = [];
        $rank = 1;
        $prev_score = null;
        $actual_rank = 1;
        
        while ($row = $result->fetch_assoc()) {
            $current_score = (float)($row['total_score'] ?? 0);
            
            // Handle ties
            if ($prev_score !== null && $current_score !== $prev_score) {
                $rank = $actual_rank;
            }
            
            $leaderboard[] = [
                'id' => $row['id'],
                'rank' => $rank,
                'name' => $row['full_name'],
                'number_label' => $row['number_label'],
                'division' => $row['division'],
                'total_score' => $current_score > 0 ? number_format($current_score, 2) : '0.00',
                'raw_score' => $current_score
            ];
            
            $prev_score = $current_score;
            $actual_rank++;
        }
        
        $stmt->close();
        $con->close();
        return $leaderboard;
    }

    // Function to get top participants for advancement
    function getTopParticipants($pageant_id, $division, $count = 5) {
        $con = $this->opencon();
        
        $sql = "SELECT 
            p.id,
            p.full_name,
            p.number_label,
            SUM(COALESCE(s.override_score, s.raw_score) * (rc.weight / 100.0)) as total_score
        FROM participants p
        JOIN divisions d ON p.division_id = d.id
        LEFT JOIN scores s ON p.id = s.participant_id
        LEFT JOIN round_criteria rc ON s.criterion_id = rc.criterion_id
        LEFT JOIN rounds r ON rc.round_id = r.id
        WHERE r.pageant_id = ? AND d.name = ? AND r.state IN ('CLOSED', 'FINALIZED') AND p.is_active = 1
        GROUP BY p.id, p.full_name, p.number_label
        ORDER BY total_score DESC
        LIMIT ?";
        
        $stmt = $con->prepare($sql);
        $stmt->bind_param("isi", $pageant_id, $division, $count);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $participants = [];
        $rank = 1;
        while ($row = $result->fetch_assoc()) {
            $participants[] = [
                'id' => $row['id'],
                'rank' => $rank,
                'name' => $row['full_name'],
                'number_label' => $row['number_label'],
                'score' => number_format((float)($row['total_score'] ?? 0), 2)
            ];
            $rank++;
        }
        
        $stmt->close();
        $con->close();
        return $participants;
    }

    // Function to get awards for public viewing
    function getPublicAwards($pageant_id) {
        $con = $this->opencon();
        
        // First check if awards table exists, if not create it
        $checkTable = "SHOW TABLES LIKE 'awards'";
        $result = $con->query($checkTable);
        
        if ($result->num_rows == 0) {
            $createAwardsTable = "CREATE TABLE awards (
                id INT AUTO_INCREMENT PRIMARY KEY,
                pageant_id INT NOT NULL,
                name VARCHAR(255) NOT NULL,
                division_scope ENUM('ALL', 'Mr', 'Ms') DEFAULT 'ALL',
                sequence INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            $con->query($createAwardsTable);
            
            $createWinnersTable = "CREATE TABLE award_winners (
                id INT AUTO_INCREMENT PRIMARY KEY,
                award_id INT NOT NULL,
                participant_id INT NOT NULL,
                position INT DEFAULT 1,
                FOREIGN KEY (award_id) REFERENCES awards(id) ON DELETE CASCADE,
                FOREIGN KEY (participant_id) REFERENCES participants(id) ON DELETE CASCADE
            )";
            $con->query($createWinnersTable);
        }
        
        $sql = "SELECT a.name, a.division_scope,
                       p.full_name, p.number_label, aw.position
                FROM awards a
                LEFT JOIN award_winners aw ON a.id = aw.award_id
                LEFT JOIN participants p ON aw.participant_id = p.id
                WHERE a.pageant_id = ?
                ORDER BY a.sequence, aw.position";
        
        $stmt = $con->prepare($sql);
        $stmt->bind_param("i", $pageant_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $awardGroups = [];
        while ($row = $result->fetch_assoc()) {
            $key = $row['name'] . '_' . $row['division_scope'];
            if (!isset($awardGroups[$key])) {
                $awardGroups[$key] = [
                    'name' => $row['name'],
                    'division_scope' => $row['division_scope'],
                    'winners' => []
                ];
            }
            
            if ($row['full_name']) {
                $awardGroups[$key]['winners'][] = [
                    'full_name' => $row['full_name'],
                    'number_label' => $row['number_label'],
                    'position' => $row['position']
                ];
            }
        }
        
        $stmt->close();
        $con->close();
        return array_values($awardGroups);
    }
}
