<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start the session
session_start();

// Check if there is an admin already logged in
if (isset($_SESSION['adminID'])) {
    // If an admin is logged in, redirect to dashboard
    header("Location: admin/dashboard.php");
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
    $user = $con->loginAdmin($pageant_code, $username, $password);
    
    // If the user is found, set session variables
    if ($user) {
        $_SESSION['adminID'] = $user['id'];
        $_SESSION['adminFN'] = $user['full_name'];
        $_SESSION['adminLN'] = '';
        $_SESSION['adminUsername'] = $user['username'];
        $_SESSION['pageantID'] = $user['pageant_id'];
        $_SESSION['adminRole'] = $user['role'] ?? $user['global_role'];
        
        // Redirect to dashboard or specified page
        $redirect = urldecode($_GET['redirect'] ?? 'admin/dashboard.php');
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
            $error_details['error_reason'] = 'invalid_admin_credentials';
            $error_details['pageant_found'] = $pageant['name'];
        }
        
        $show_error_alert = true;
    }
}

$pageTitle = 'Admin Login';
include __DIR__ . '/partials/head.php';
?>

<main class="min-h-screen flex items-center justify-center px-4">
  <div class="mx-auto max-w-sm w-full p-8 space-y-6 bg-white bg-opacity-15 backdrop-blur-md rounded-2xl shadow-2xl border border-white border-opacity-20">
    <div class="text-center">
        <h1 class="text-2xl font-semibold text-white mb-2">Admin Login</h1>
        <div class="w-12 h-12 bg-blue-500 bg-opacity-30 backdrop-blur-sm rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-6 h-6 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.031 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
        </div>
    </div>
    
    <?php if (isset($error_message)): ?>
        <div class="bg-red-500 bg-opacity-20 backdrop-blur-sm border border-red-400 border-opacity-50 text-red-200 px-4 py-3 rounded-lg text-sm">
            <?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>
    
    <form id="adminLoginForm" method="POST" class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-slate-200 mb-2">Pageant Code</label>
            <select name="pageant_code" id="pageant_code" required class="w-full bg-white bg-opacity-20 backdrop-blur-sm border border-white border-opacity-30 rounded-lg px-4 py-3 text-sm text-white focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-opacity-50 focus:border-opacity-50 transition-all">
                <option value="" class="text-slate-800">Loading pageants...</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-200 mb-2">Username</label>
            <input name="username" type="text" required class="w-full bg-white bg-opacity-20 backdrop-blur-sm border border-white border-opacity-30 rounded-lg px-4 py-3 text-sm text-white placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-opacity-50 focus:border-opacity-50 transition-all" placeholder="Enter your username" />
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-200 mb-2">Password</label>
            <input name="password" type="password" required class="w-full bg-white bg-opacity-20 backdrop-blur-sm border border-white border-opacity-30 rounded-lg px-4 py-3 text-sm text-white placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-opacity-50 focus:border-opacity-50 transition-all" placeholder="Enter your password" />
        </div>
        <button name="login" type="submit" class="w-full bg-blue-500 bg-opacity-30 backdrop-blur-sm hover:bg-opacity-40 text-white font-medium px-4 py-3 rounded-lg border border-blue-400 border-opacity-50 hover:border-opacity-70 transition-all duration-300 transform hover:scale-105">Login</button>
    </form>
    
    <p class="text-xs text-slate-300 text-center">You are accessing the administration portal.</p>
  </div>
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
    makeFormLoadingEnabled('adminLoginForm', 'Signing in...', true);
    
    // Load pageant codes
    fetch('api.php?action=get_pageant_codes')
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('pageant_code');
            select.innerHTML = '<option value="" class="text-slate-800">Select a pageant...</option>';
            
            if (data.success && data.pageants) {
                data.pageants.forEach(pageant => {
                    const option = document.createElement('option');
                    option.value = pageant.code;
                    option.textContent = `${pageant.name} (${pageant.code})`;
                    option.className = 'text-slate-800';
                    select.appendChild(option);
                });
            } else {
                select.innerHTML = '<option value="" class="text-slate-800">No pageants available</option>';
            }
        })
        .catch(error => {
            console.error('Error loading pageants:', error);
            const select = document.getElementById('pageant_code');
            select.innerHTML = '<option value="" class="text-slate-800">Error loading pageants</option>';
        });
});
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
