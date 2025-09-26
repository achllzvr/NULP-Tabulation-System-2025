<?php
class auth {
    private database $db;

    public function __construct(database $db) {
        $this->db = $db;
    }

    public function login_user(string $identifier, string $password): bool {
        try {
            $pdo = $this->db->opencon();
            
            // Allow login by email or username
            $stmt = $pdo->prepare(
                "SELECT id, username, email, password_hash, role, full_name, is_active 
                 FROM users 
                 WHERE (email = ? OR username = ?) AND is_active = 1"
            );
            $stmt->execute([$identifier, $identifier]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password_hash'])) {
                return false;
            }

            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['username'] = $user['username'];

            return true;
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }

    public function logout_user(): void {
        session_destroy();
        session_start(); // Start a fresh session
    }

    public function is_logged_in(): bool {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    public function get_current_user(): ?array {
        if (!$this->is_logged_in()) {
            return null;
        }

        return [
            'id' => $_SESSION['user_id'],
            'role' => $_SESSION['user_role'] ?? null,
            'full_name' => $_SESSION['full_name'] ?? '',
            'username' => $_SESSION['username'] ?? ''
        ];
    }

    public function ensure_logged_in(): void {
        if (!$this->is_logged_in()) {
            header('Location: /login.php');
            exit;
        }
    }

    public function ensure_role(string $required_role): void {
        $this->ensure_logged_in();
        if (($_SESSION['user_role'] ?? '') !== $required_role) {
            header('Location: /dashboard.php');
            exit;
        }
    }
}