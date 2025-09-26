<?php
require __DIR__ . '/includes/bootstrap.php';

// Perform logout
auth_logout();

// Set flash message
set_flash_message('success', 'You have been logged out successfully.');

// Redirect to login
redirect('/login.php');