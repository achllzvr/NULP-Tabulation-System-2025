<?php
// Start session if not already started
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Error reporting for development
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Include all core classes
require_once __DIR__ . '/../classes/database.php';
require_once __DIR__ . '/../classes/auth.php';
require_once __DIR__ . '/../classes/pageant.php';
require_once __DIR__ . '/../classes/rounds.php';
require_once __DIR__ . '/../classes/scores.php';
require_once __DIR__ . '/../classes/awards.php';
require_once __DIR__ . '/../classes/tie_resolution.php';

// Initialize core objects
$db = new database();
$auth = new auth($db);
$pageant = new pageant($db);
$rounds = new rounds($db);
$scores = new scores($db);
$awards = new awards($db);
$tie_resolution = new tie_resolution($db);

// Helper functions
function esc($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function ensure_logged_in(): void {
    global $auth;
    $auth->ensure_logged_in();
}

function ensure_pageant_selected(): void {
    global $pageant;
    $pageant->ensure_pageant_selected();
}

function get_current_app_user(): ?array {
    global $auth;
    return $auth->get_current_user();
}

function get_current_app_pageant(): ?array {
    global $pageant;
    return $pageant->get_current_pageant();
}

// Set timezone
date_default_timezone_set('UTC');