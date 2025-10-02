<?php

class database {
    
    // Function to open connection with database
    function opencon() {
        $host = getenv('DB_HOST') ?: 'localhost';
        $db   = getenv('DB_NAME') ?: 'u754480983_TabSys_DB';
        $user = getenv('DB_USER') ?: 'u754480983_NULP';
        $pass = getenv('DB_PASS') ?: 'NULPPageant2025';
        
        $con = new mysqli($host, $user, $pass, $db);
        if ($con->connect_error) {
            die("Connection failed: " . $con->connect_error);
        }
        return $con;
    }

    // Function to login admin user with pageant code validation
    function loginAdmin($pageant_code, $username, $password) {
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
        
        // Now validate admin credentials for this specific pageant
        $stmt = $con->prepare("SELECT u.id, u.username, u.full_name, u.password_hash, u.global_role, u.is_active, pu.pageant_id, pu.role 
                               FROM users u 
                               JOIN pageant_users pu ON u.id = pu.user_id 
                               WHERE u.username = ? AND u.is_active = 1 AND pu.pageant_id = ? AND (pu.role = 'admin' OR u.global_role = 'SUPERADMIN')");
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
        $stmt = $con->prepare("SELECT setting_key, setting_value FROM pageant_settings WHERE setting_key IN ('reveal_names', 'reveal_scores', 'reveal_awards', 'reveal_numbers')");
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Initialize default flags
        $flags = [
            'reveal_names' => false,
            'reveal_scores' => false,
            'reveal_awards' => false,
            'reveal_numbers' => false
        ];
        
        // Update flags based on database values
        while ($row = $result->fetch_assoc()) {
            $flags[$row['setting_key']] = (bool)(int)$row['setting_value'];
        }
        // If any awards are marked REVEALED for this pageant, treat reveal_awards as true
        $stmt2 = $con->prepare("SELECT COUNT(*) AS cnt FROM awards WHERE pageant_id = ? AND visibility_state = 'REVEALED'");
        $stmt2->bind_param('i', $pageant_id);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        if ($row2 = $res2->fetch_assoc()) {
            if ((int)$row2['cnt'] > 0) {
                $flags['reveal_awards'] = true;
            }
        }
        $stmt2->close();

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
            SUM(COALESCE(s.override_score, s.raw_score) * (CASE WHEN rc.weight > 1 THEN rc.weight/100.0 ELSE rc.weight END)) as total_score
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
                'score' => number_format($current_score, 2),
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
            SUM(COALESCE(s.override_score, s.raw_score) * (CASE WHEN rc.weight > 1 THEN rc.weight/100.0 ELSE rc.weight END)) as total_score
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
                'total_score' => number_format($current_score, 2),
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
            SUM(COALESCE(s.override_score, s.raw_score) * (CASE WHEN rc.weight > 1 THEN rc.weight/100.0 ELSE rc.weight END)) as total_score
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

    // Function to calculate leaderboard for a stage (scoring_mode PRELIM or FINAL)
    function getStageLeaderboard($pageant_id, $division, $scoring_mode) {
        $con = $this->opencon();
        $sql = "SELECT 
            p.id,
            p.full_name,
            p.number_label,
            d.name as division,
            SUM(COALESCE(s.override_score, s.raw_score) * (CASE WHEN rc.weight > 1 THEN rc.weight/100.0 ELSE rc.weight END)) as total_score
        FROM participants p
        JOIN divisions d ON p.division_id = d.id
        LEFT JOIN scores s ON p.id = s.participant_id
        LEFT JOIN round_criteria rc ON s.criterion_id = rc.criterion_id
        LEFT JOIN rounds r ON rc.round_id = r.id
        WHERE r.pageant_id = ? AND r.scoring_mode = ? AND r.state IN ('CLOSED', 'FINALIZED') AND p.is_active = 1 AND d.name = ?
        GROUP BY p.id, p.full_name, p.number_label, d.name
        ORDER BY total_score DESC, p.full_name ASC";
        $stmt = $con->prepare($sql);
        $stmt->bind_param("iss", $pageant_id, $scoring_mode, $division);
        $stmt->execute();
        $result = $stmt->get_result();
        $leaderboard = [];
        $rank = 1; $prev_score = null; $actual_rank = 1;
        while ($row = $result->fetch_assoc()) {
            $current_score = (float)($row['total_score'] ?? 0);
            if ($prev_score !== null && $current_score !== $prev_score) { $rank = $actual_rank; }
            $leaderboard[] = [
                'id' => $row['id'],
                'rank' => $rank,
                'name' => $row['full_name'],
                'number_label' => $row['number_label'],
                'division' => $row['division'],
                'total_score' => number_format($current_score, 2),
                'raw_score' => $current_score
            ];
            $prev_score = $current_score; $actual_rank++;
        }
        $stmt->close();
        $con->close();
        return $leaderboard;
    }

    // Function to get awards for public viewing (aligned to schema: awards + award_results)
    function getPublicAwards($pageant_id) {
        $con = $this->opencon();
        $sql = "SELECT a.name, a.division_scope, ar.position, p.full_name, p.number_label
                FROM awards a
                LEFT JOIN award_results ar ON ar.award_id = a.id
                LEFT JOIN participants p ON p.id = ar.participant_id
                WHERE a.pageant_id = ? AND a.visibility_state = 'REVEALED'
                ORDER BY a.id, ar.position";
        $stmt = $con->prepare($sql);
        $stmt->bind_param('i', $pageant_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $awardGroups = [];
        while ($row = $result->fetch_assoc()) {
            $key = $row['name'];
            if (!isset($awardGroups[$key])) {
                $awardGroups[$key] = [
                    'name' => $row['name'],
                    'division_scope' => $row['division_scope'],
                    'winners' => []
                ];
            }
            if (!empty($row['full_name'])) {
                $awardGroups[$key]['winners'][] = [
                    'full_name' => $row['full_name'],
                    'number_label' => $row['number_label'],
                    'position' => (int)$row['position']
                ];
            }
        }
        $stmt->close();
        $con->close();
        return array_values($awardGroups);
    }
}
