<?php
declare(strict_types=1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load environment variables if .env file exists
if (file_exists(__DIR__ . '/../.env')) {
    $env = parse_ini_file(__DIR__ . '/../.env');
    foreach ($env as $key => $value) {
        $_ENV[$key] = $value;
    }
}

// Set error reporting for development
if (($_ENV['APP_ENV'] ?? 'production') === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Set default timezone
date_default_timezone_set('Asia/Manila');

// Require class files
require_once __DIR__ . '/../classes/database.php';
require_once __DIR__ . '/../classes/auth.php';
require_once __DIR__ . '/../classes/pageant.php';
require_once __DIR__ . '/../classes/rounds.php';
require_once __DIR__ . '/../classes/scores.php';
require_once __DIR__ . '/../classes/awards.php';

// Helper functions
function esc(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void {
    header("Location: $url");
    exit;
}

function is_current_page(string $page): bool {
    $current = basename($_SERVER['PHP_SELF']);
    return $current === $page;
}

function get_flash_message(): ?array {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

function set_flash_message(string $type, string $message): void {
    $_SESSION['flash_message'] = ['type' => $type, 'message' => $message];
}