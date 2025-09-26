<?php
require_once __DIR__ . '/includes/bootstrap.php';

// Check if user is logged in
if ($auth->is_logged_in()) {
    // Redirect to dashboard if already logged in
    header('Location: /dashboard.php');
    exit;
} else {
    // Show landing page or redirect to login
    header('Location: /login.php');
    exit;
}
?>