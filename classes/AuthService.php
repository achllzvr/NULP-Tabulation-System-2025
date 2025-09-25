<?php
require_once 'database.php';

/**
 * Authentication Service
 * Handles user login, logout, session management and role-based access control
 */
class AuthService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Authenticate user with email and password
     */
    public function login(string $email, string $password): array
    {
        // Find user by email or username
        $user = $this->db->fetch(
            "SELECT id, email, username, password_hash, full_name, global_role, is_active FROM users WHERE (email = ? OR username = ?) AND is_active = 1",
            [$email, $email]
        );
        
        if (!$user) {
            throw new Exception('Invalid credentials or account disabled');
        }
        
        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            throw new Exception('Invalid credentials');
        }
        
        // Start session and store user data
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_username'] = $user['username'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_role'] = $user['global_role']; // SUPERADMIN or STANDARD
        $_SESSION['login_time'] = time();
        
        // Update last login
        $this->db->execute(
            "UPDATE users SET updated_at = NOW() WHERE id = ?",
            [$user['id']]
        );
        
        return [
            'id' => $user['id'],
            'email' => $user['email'],
            'username' => $user['username'],
            'name' => $user['full_name'],
            'role' => $user['global_role']
        ];
    }
    
    /**
     * Logout current user
     */
    public function logout(): void {
        session_unset();
        session_destroy();
        session_start();
    }
    
    /**
     * Require user to be logged in
     */
    public function requireLogin(): void {
        if (!$this->isLoggedIn()) {
            header('Location: login.php');
            exit;
        }
    }
    
    /**
     * Require specific pageant role
     */
    public function requirePageantRole(int $pageantId, array $roles): void {
        $this->requireLogin();
        
        $userRole = $this->getUserPageantRole($pageantId);
        if (!in_array($userRole, $roles)) {
            header('HTTP/1.1 403 Forbidden');
            exit('Access denied');
        }
    }
    
    /**
     * Get current authenticated user
     */
    public function currentUser(): ?array {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return $this->db->fetch(
            "SELECT * FROM users WHERE id = ?", 
            [$_SESSION['user_id']]
        );
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn(): bool {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Get user's role in specific pageant
     */
    public function getUserPageantRole(int $pageantId): ?string {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        $role = $this->db->fetch(
            "SELECT role FROM pageant_users WHERE pageant_id = ? AND user_id = ?",
            [$pageantId, $_SESSION['user_id']]
        );
        
        return $role ? $role['role'] : null;
    }
    
    /**
     * Set user session data
     */
    private function setUserSession(array $user): void {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['full_name'];
    }
}