<?php
declare(strict_types=1);

function auth_login(string $username, string $password): array {
    try {
        $db = new database();
        $pdo = $db->opencon();
        
        // TODO: Implement actual user authentication
        // For now, return a placeholder response
        
        $stmt = $pdo->prepare("SELECT id, username, password_hash, role FROM users WHERE username = ? AND active = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && $db->verifyPassword($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['pageant_id'] = null; // Set when pageant is selected
            
            return ['success' => true, 'user' => $user];
        }
        
        return ['success' => false, 'error' => 'Invalid credentials'];
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Login failed: ' . $e->getMessage()];
    }
}

function auth_logout(): void {
    session_destroy();
    session_start();
}

function auth_require_login(): void {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /login.php');
        exit;
    }
}

function auth_user(): ?array {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    try {
        $db = new database();
        $pdo = $db->opencon();
        
        $stmt = $pdo->prepare("SELECT id, username, role, first_name, last_name FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch() ?: null;
    } catch (Exception $e) {
        return null;
    }
}

function auth_has_role(string $role): bool {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

function auth_is_admin(): bool {
    return auth_has_role('admin');
}

function auth_is_judge(): bool {
    return auth_has_role('judge');
}