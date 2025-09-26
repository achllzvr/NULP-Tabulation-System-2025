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
        $normalizedRequired = array_map(fn($r)=>strtoupper($r), $roles);
        $currentRole = isset($_SESSION['role']) ? strtoupper((string)$_SESSION['role']) : null;

        // Not logged in
        if (empty($_SESSION['user_id'])) {
            $currentUrl = urlencode($_SERVER['REQUEST_URI'] ?? 'index.php');
            // Choose login page based on required role priority
            if (in_array('ADMIN', $normalizedRequired, true)) {
                header('Location: login_admin.php?redirect=' . $currentUrl);
                exit;
            }
            if (in_array('JUDGE', $normalizedRequired, true)) {
                header('Location: login_judge.php?redirect=' . $currentUrl);
                exit;
            }
            header('Location: index.php?auth=required');
            exit;
        }

        // Logged in but wrong role
        if (!$currentRole || !in_array($currentRole, $normalizedRequired, true)) {
            header('Location: forbidden.php');
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
