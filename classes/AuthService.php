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
    public function login(string $email, string $password): ?array {
        $user = $this->db->fetch(
            "SELECT * FROM users WHERE email = ? AND is_active = 1", 
            [$email]
        );
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $this->setUserSession($user);
            session_regenerate_id(true);
            return $user;
        }
        
        return null;
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