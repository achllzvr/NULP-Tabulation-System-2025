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
        
        // Check if pageant_visibility table exists, if not create it
        $checkTable = "SHOW TABLES LIKE 'pageant_visibility'";
        $result = $con->query($checkTable);
        
        if ($result->num_rows == 0) {
            $createTable = "CREATE TABLE pageant_visibility (
                id INT AUTO_INCREMENT PRIMARY KEY,
                pageant_id INT NOT NULL,
                reveal_names BOOLEAN DEFAULT FALSE,
                reveal_scores BOOLEAN DEFAULT FALSE,
                reveal_awards BOOLEAN DEFAULT FALSE,
                UNIQUE KEY unique_pageant (pageant_id)
            )";
            $con->query($createTable);
        }
        
        $stmt = $con->prepare("SELECT reveal_names, reveal_scores, reveal_awards FROM pageant_visibility WHERE pageant_id = ?");
        $stmt->bind_param("i", $pageant_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $flags = $result->fetch_assoc();
        } else {
            // Insert default flags
            $insertStmt = $con->prepare("INSERT INTO pageant_visibility (pageant_id, reveal_names, reveal_scores, reveal_awards) VALUES (?, 0, 0, 0)");
            $insertStmt->bind_param("i", $pageant_id);
            $insertStmt->execute();
            $insertStmt->close();
            
            $flags = ['reveal_names' => 0, 'reveal_scores' => 0, 'reveal_awards' => 0];
        }
        
        $stmt->close();
        $con->close();
        return $flags;
    }
}
