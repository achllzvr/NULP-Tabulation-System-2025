<?php
/**
 * Session Management Helper
 * Provides centralized session handling and user authentication across all pages
 */

class SessionManager {
    
    /**
     * Initialize session if not already started
     */
    public static function start(): void {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }
    
    /**
     * Check if user is logged in
     */
    public static function isLoggedIn(): bool {
        self::start();
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Get current user ID
     */
    public static function getUserId(): ?int {
        self::start();
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Get current user role (global role: SUPERADMIN or STANDARD)
     */
    public static function getUserRole(): ?string {
        self::start();
        return $_SESSION['user_role'] ?? null;
    }
    
    /**
     * Get current user data
     */
    public static function getUserData(): array {
        self::start();
        return [
            'id' => $_SESSION['user_id'] ?? null,
            'email' => $_SESSION['user_email'] ?? null,
            'username' => $_SESSION['user_username'] ?? null,
            'full_name' => $_SESSION['user_name'] ?? null,
            'role' => $_SESSION['user_role'] ?? null,
            'login_time' => $_SESSION['login_time'] ?? null
        ];
    }
    
    /**
     * Require user to be logged in (redirects to login if not)
     */
    public static function requireLogin(string $redirectUrl = 'login.php'): void {
        if (!self::isLoggedIn()) {
            header("Location: $redirectUrl");
            exit;
        }
    }
    
    /**
     * Require user to have SUPERADMIN role (redirects if not)
     */
    public static function requireAdmin(string $redirectUrl = 'index.php'): void {
        self::requireLogin();
        if (self::getUserRole() !== 'SUPERADMIN') {
            header("Location: $redirectUrl");
            exit;
        }
    }
    
    /**
     * Require user to be either SUPERADMIN or have specific pageant role
     */
    public static function requirePageantAccess(int $pageantId, array $allowedRoles = ['ADMIN', 'JUDGE'], string $redirectUrl = 'index.php'): void {
        self::requireLogin();
        
        // SUPERADMIN can access everything
        if (self::getUserRole() === 'SUPERADMIN') {
            return;
        }
        
        // Check pageant-specific role
        require_once 'Database.php';
        $db = Database::getInstance();
        $role = $db->fetch(
            "SELECT role FROM pageant_users WHERE pageant_id = ? AND user_id = ?",
            [$pageantId, self::getUserId()]
        );
        
        if (!$role || !in_array($role['role'], $allowedRoles)) {
            header("Location: $redirectUrl");
            exit;
        }
    }
    
    /**
     * Check if user has pageant role
     */
    public static function hasPageantRole(int $pageantId, string $role): bool {
        if (!self::isLoggedIn()) {
            return false;
        }
        
        // SUPERADMIN can access everything
        if (self::getUserRole() === 'SUPERADMIN') {
            return true;
        }
        
        require_once 'Database.php';
        $db = Database::getInstance();
        $userRole = $db->fetch(
            "SELECT role FROM pageant_users WHERE pageant_id = ? AND user_id = ?",
            [$pageantId, self::getUserId()]
        );
        
        return $userRole && $userRole['role'] === $role;
    }
    
    /**
     * Set user session data (used during login)
     */
    public static function setUserSession(array $userData): void {
        self::start();
        $_SESSION['user_id'] = $userData['id'];
        $_SESSION['user_email'] = $userData['email'];
        $_SESSION['user_username'] = $userData['username'];
        $_SESSION['user_name'] = $userData['full_name'];
        $_SESSION['user_role'] = $userData['global_role'];
        $_SESSION['login_time'] = time();
    }
    
    /**
     * Clear user session (logout)
     */
    public static function logout(): void {
        self::start();
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }
    
    /**
     * Redirect already logged in users
     */
    public static function redirectIfLoggedIn(): void {
        if (self::isLoggedIn()) {
            $role = self::getUserRole();
            if ($role === 'SUPERADMIN') {
                header('Location: dashboard.php');
                exit;
            } else {
                header('Location: index.php');
                exit;
            }
        }
    }
}