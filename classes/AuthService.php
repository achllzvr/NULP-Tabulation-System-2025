<?php
/**
 * AuthService
 * Basic session based auth & role checks.
 * Expected session keys: user_id, role, pageant_id
 */
require_once __DIR__ . '/database.php';

class AuthService {
    public static function start(): void {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        // inactivity timeout (15 minutes)
        $now = time();
        if (!empty($_SESSION['last_activity']) && ($now - (int)$_SESSION['last_activity'] > 900)) {
            // Expire session
            session_unset();
            session_destroy();
            session_start();
        }
        $_SESSION['last_activity'] = $now;
    }

    public static function requireLogin(): void {
        self::start();
        if (empty($_SESSION['user_id'])) {
            header('Location: index.php?auth=required');
            exit;
        }
    }

    public static function requireRole(array $roles): void {
        self::start();
        if (empty($_SESSION['role']) || !in_array($_SESSION['role'], $roles, true)) {
            http_response_code(403);
            echo 'Forbidden';
            exit;
        }
    }

    public static function currentUser(): ?array {
        self::start();
        if (empty($_SESSION['user_id'])) return null;
        return [
            'user_id' => (int)$_SESSION['user_id'],
            'role' => $_SESSION['role'] ?? null,
            'pageant_id' => isset($_SESSION['pageant_id']) ? (int)$_SESSION['pageant_id'] : null,
            'name' => $_SESSION['name'] ?? null,
        ];
    }

    public static function regenerate(): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }
}
