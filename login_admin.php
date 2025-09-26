<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start the session
session_start();

// Check if there is an admin already logged in
if (isset($_SESSION['adminID'])) {
    // If an admin is logged in, redirect to dashboard
    header("Location: dashboard.php");
    exit();
}

// Include the database class file
require_once('classes/database.php');

// Create an instance of the database class
$con = new database();

// Check if the login form is submitted
if (isset($_POST['login'])) {
    
    // Get the username and password from the POST request
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // Validate the inputs
    $user = $con->loginAdmin($username, $password);
    
    // If the user is found, set session variables
    if ($user) {
        $_SESSION['adminID'] = $user['id'];
        $_SESSION['adminFN'] = $user['full_name'];
        $_SESSION['adminLN'] = '';
        $_SESSION['adminUsername'] = $user['username'];
        $_SESSION['pageantID'] = $user['pageant_id'] ?? 1;
        $_SESSION['adminRole'] = $user['role'] ?? $user['global_role'];
        
        // Redirect to dashboard or specified page
        $redirect = $_GET['redirect'] ?? 'dashboard.php';
        header("Location: " . $redirect);
        exit();
    } else {
        $error_message = "Invalid username or password.";
    }
}

$pageTitle = 'Admin Login';
include __DIR__ . '/partials/head.php';
?>

<main class="mx-auto max-w-sm w-full p-6 space-y-6">
    <h1 class="text-xl font-semibold text-slate-800">Admin Login</h1>
    
    <?php if (isset($error_message)): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded text-sm">
            <?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Username</label>
            <input name="username" type="text" required class="w-full border border-slate-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring focus:border-blue-500" />
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
            <input name="password" type="password" required class="w-full border border-slate-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring focus:border-blue-500" />
        </div>
        <button name="login" type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded w-full">Login</button>
    </form>
    
    <p class="text-xs text-slate-500">You are accessing the administration portal.</p>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
