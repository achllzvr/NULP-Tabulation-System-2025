<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start the session
session_start();

// Check if there is a judge already logged in
if (isset($_SESSION['judgeID'])) {
    // If a judge is logged in, redirect to judge dashboard
    header("Location: judge/judge_active.php");
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
        $redirect = urldecode($_GET['redirect'] ?? 'judge/judge_active.php');
        header("Location: " . $redirect);
        exit();
    } else {
        // Determine specific error type for better debugging
        $error_details = [
            'attempted_pageant_code' => $pageant_code,
            'attempted_username' => $username,
            'timestamp' => date('Y-m-d H:i:s'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];
        
        // Check if pageant code exists
        $pageant = $con->getPageantByCode($pageant_code);
        if (!$pageant) {
            $error_type = "INVALID_PAGEANT_CODE";
            $error_message = "The pageant code '$pageant_code' is not valid or does not exist.";
            $error_details['error_reason'] = 'pageant_code_not_found';
        } else {
            $error_type = "INVALID_CREDENTIALS";
            $error_message = "Invalid username or password for this pageant.";
            $error_details['error_reason'] = 'invalid_user_credentials';
            $error_details['pageant_found'] = $pageant['name'];
        }
        
        $show_error_alert = true;
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
    
    <form id="judgeLoginForm" method="POST" class="space-y-4">
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
        <button name="login" type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded w-full transition-all">Login</button>
    </form>
    
    <p class="text-xs text-slate-500">You are accessing the judge portal.</p>
</main>

<?php if (isset($show_error_alert)): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    showDetailedError('<?= $error_type ?>', '<?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?>', <?= json_encode($error_details) ?>);
});
</script>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    makeFormLoadingEnabled('judgeLoginForm', 'Verifying credentials...', true);
});
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
