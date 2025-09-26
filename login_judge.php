<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start the session
session_start();

// Check if there is a judge already logged in
if (isset($_SESSION['judgeID'])) {
    // If a judge is logged in, redirect to judge dashboard
    header("Location: judge_active.php");
    exit();
}

// Include the database class file
require_once('classes/database.php');

// Create an instance of the database class
$con = new database();

// Check if the login form is submitted
if (isset($_POST['login'])) {
    
    // Get the form data from the POST request
    $pageant_code = $_POST['pageant_code'] ?? '';
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // Validate the inputs
    $user = $con->loginJudge($pageant_code, $username, $password);
    
    // If the user is found, set session variables
    if ($user) {
        $_SESSION['judgeID'] = $user['id'];
        $_SESSION['judgeFN'] = $user['full_name'];
        $_SESSION['judgeLN'] = '';
        $_SESSION['judgeUsername'] = $user['username'];
        $_SESSION['pageantID'] = $user['pageant_id'];
        $_SESSION['judgeRole'] = $user['role'] ?? $user['global_role'];
        
        // Redirect to judge dashboard or specified page
        $redirect = $_GET['redirect'] ?? 'judge_active.php';
        header("Location: " . $redirect);
        exit();
    } else {
        $error_message = "Invalid pageant code, username, or password.";
    }
}

$pageTitle = 'Judge Login';
include __DIR__ . '/partials/head.php';
?>

<main class="mx-auto max-w-sm w-full p-6 space-y-6">
    <h1 class="text-xl font-semibold text-slate-800">Judge Login</h1>
    
    <?php if (isset($error_message)): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded text-sm">
            <?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Pageant Code</label>
            <input name="pageant_code" type="text" required class="w-full border border-slate-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring focus:border-blue-500" />
        </div>
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
    
    <p class="text-xs text-slate-500">You are accessing the judging panel.</p>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
