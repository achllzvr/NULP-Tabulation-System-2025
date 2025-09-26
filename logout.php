<?php
require_once __DIR__ . '/includes/bootstrap.php';

// Logout the user
$auth->logout_user();

// Redirect to login page with success message
header('Location: /login.php?logged_out=1');
exit;