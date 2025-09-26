<?php
/**
 * Authentication helper functions
 * Provides session-based login/logout functionality using database::opencon()
 */

/**
 * Authenticate user login
 * @param string $email User email
 * @param string $password Plain text password
 * @return bool True on success, false on failure
 */
function auth_login($email, $password) {
    $pdo = database::opencon();
    
    $stmt = $pdo->prepare("SELECT user_id, password_hash, role FROM users WHERE email = ? AND active = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && database::verifyPassword($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_role'] = $user['role'];
        return true;
    }
    
    return false;
}

/**
 * Log out current user
 */
function auth_logout() {
    session_destroy();
}

/**
 * Get current authenticated user
 * @return array|null User data or null if not authenticated
 */
function auth_user() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    $pdo = database::opencon();
    $stmt = $pdo->prepare("SELECT user_id, email, role, first_name, last_name FROM users WHERE user_id = ? AND active = 1");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

/**
 * Require user to be logged in
 * Redirects to login.php if not authenticated
 */
function auth_require_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Require user to have specific role(s)
 * @param array $roles Array of allowed roles
 */
function auth_require_role($roles) {
    auth_require_login();
    
    if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $roles)) {
        http_response_code(403);
        die('Access denied');
    }
}

/**
 * Check if user has specific role
 * @param string $role Role to check
 * @return bool
 */
function auth_has_role($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}