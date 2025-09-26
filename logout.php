<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start the session
session_start();

// Destroy the session and redirect to index
session_destroy();
header("Location: index.php");
exit();

?>