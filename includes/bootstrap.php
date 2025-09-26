<?php
/**
 * Bootstrap file for NULP Tabulation System
 * Single session_start, class loading, error reporting, and helper functions
 */

// Start session (only place in codebase that calls session_start)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting for development (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load class files
require_once __DIR__ . '/../classes/database.php';
require_once __DIR__ . '/../classes/auth.php';
require_once __DIR__ . '/../classes/pageant.php';
require_once __DIR__ . '/../classes/rounds.php';
require_once __DIR__ . '/../classes/scores.php';
require_once __DIR__ . '/../classes/awards.php';

/**
 * HTML escape helper function for safe output
 * @param mixed $data Data to escape
 * @return string Escaped string
 */
function esc($data) {
    if (is_array($data)) {
        return array_map('esc', $data);
    }
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect helper function
 * @param string $url URL to redirect to
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Flash message helper
 * @param string $type Message type (success, error, info)
 * @param string $message Message text
 */
function flash($type, $message) {
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/**
 * Get and clear flash messages
 * @return array Array of flash messages
 */
function get_flash_messages() {
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}