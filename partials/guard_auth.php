<?php
/**
 * Partial: Authentication Guard
 * Include this at the top of pages that require authentication
 * Expected vars: $requiredRole (optional), $pageantId (optional)
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once 'classes/AuthService.php';

$authService = new AuthService();

// Require login
$authService->requireLogin();

// Check pageant role if specified
if (isset($requiredRole) && isset($pageantId)) {
    $authService->requirePageantRole($pageantId, (array)$requiredRole);
}

// Make current user available to the page
$currentUser = $authService->currentUser();
?>