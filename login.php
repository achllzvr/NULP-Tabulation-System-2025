<?php
require_once __DIR__ . '/includes/bootstrap.php';

$error = '';
$success = '';

// Redirect if already logged in
if ($auth->is_logged_in()) {
    header('Location: /dashboard.php');
    exit;
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($identifier) || empty($password)) {
        $error = 'Please enter both username/email and password.';
    } else {
        if ($auth->login_user($identifier, $password)) {
            header('Location: /dashboard.php');
            exit;
        } else {
            $error = 'Invalid credentials. Please try again.';
        }
    }
}

$title = 'Login - NULP Tabulation System';
include __DIR__ . '/includes/head.php';
?>

<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-100 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <div class="mx-auto h-12 w-12 flex items-center justify-center text-4xl">
                🏆
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                NULP Tabulation System
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Sign in to your account
            </p>
        </div>
        
        <form class="mt-8 space-y-6" method="POST">
            <?php if ($error): ?>
            <div class="alert bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md">
                <?= esc($error) ?>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-md">
                <?= esc($success) ?>
            </div>
            <?php endif; ?>
            
            <div class="rounded-md shadow-sm -space-y-px">
                <div>
                    <label for="identifier" class="sr-only">Username or Email</label>
                    <input id="identifier" name="identifier" type="text" required 
                           class="relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" 
                           placeholder="Username or Email"
                           value="<?= esc($_POST['identifier'] ?? '') ?>">
                </div>
                <div>
                    <label for="password" class="sr-only">Password</label>
                    <input id="password" name="password" type="password" required 
                           class="relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" 
                           placeholder="Password">
                </div>
            </div>

            <div>
                <button type="submit" 
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Sign in
                </button>
            </div>
            
            <div class="text-center">
                <div class="text-sm text-gray-600">
                    <strong>Demo Access:</strong><br>
                    Admin: admin@demo.com / admin123<br>
                    Judge: judge@demo.com / judge123
                </div>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>