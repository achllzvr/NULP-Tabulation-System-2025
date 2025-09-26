<?php
require_once 'includes/bootstrap.php';

// Destroy session and redirect to login
auth_logout();
redirect('login.php');