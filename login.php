<?php
/**
 * Login Page
 * Simple authentication page for the converted system
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once 'classes/Util.php';

// Handle logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    session_start();
    $message = 'You have been logged out successfully.';
}

// Handle login form submission
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Simple demo authentication (in production, use AuthService)
    if (!empty($email) && !empty($password)) {
        // Demo credentials
        if ($email === 'admin@demo.com' && $password === 'admin123') {
            $_SESSION['user_id'] = 'admin_001';
            $_SESSION['user_role'] = 'admin';
            $_SESSION['user_name'] = 'System Administrator';
            header('Location: dashboard.php');
            exit;
        } elseif ($email === 'judge@demo.com' && $password === 'judge123') {
            $_SESSION['user_id'] = 'judge_001';
            $_SESSION['user_role'] = 'judge';
            $_SESSION['user_name'] = 'Dr. Sarah Mitchell';
            header('Location: judge_active.php');
            exit;
        } else {
            $error = 'Invalid email or password';
        }
    } else {
        $error = 'Please enter both email and password';
    }
}

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['user_role'] ?? '';
    if ($role === 'admin') {
        header('Location: dashboard.php');
        exit;
    } elseif ($role === 'judge') {
        header('Location: judge_active.php');
        exit;
    }
}

$pageTitle = 'Login - Pageant Tabulation System';
include 'partials/head.php';
?>

<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="flex items-center justify-center mb-4">
                <!-- Crown SVG Icon -->
                <svg class="w-10 h-10 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3l4 6 4-7 4 7 4-6v18H5V3z"/>
                </svg>
                <h1 class="text-2xl font-bold text-gray-900">Pageant System</h1>
            </div>
            <p class="text-gray-600">Sign in to continue</p>
        </div>

        <!-- Login Form -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-8">
                <?php if (isset($error)): ?>
                    <div class="mb-4 bg-red-50 border border-red-200 rounded-md p-4">
                        <div class="flex">
                            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <div class="ml-3">
                                <p class="text-sm text-red-700"><?= Util::escape($error) ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (isset($message)): ?>
                    <div class="mb-4 bg-green-50 border border-green-200 rounded-md p-4">
                        <div class="flex">
                            <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <div class="ml-3">
                                <p class="text-sm text-green-700"><?= Util::escape($message) ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    <input type="hidden" name="action" value="login">
                    
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Enter your email"
                               value="<?= Util::escape($_POST['email'] ?? '') ?>">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Enter your password">
                    </div>

                    <button type="submit" 
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Sign In
                    </button>
                </form>
            </div>
        </div>

        <!-- Demo Credentials -->
        <div class="mt-6 bg-white shadow rounded-lg">
            <div class="px-6 py-4">
                <h3 class="text-sm font-medium text-gray-900 mb-3">Demo Credentials</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Admin:</span>
                        <span class="font-mono">admin@demo.com / admin123</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Judge:</span>
                        <span class="font-mono">judge@demo.com / judge123</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Back to Landing -->
        <div class="mt-6 text-center">
            <a href="index.php" class="text-blue-600 hover:text-blue-700 text-sm">
                ← Back to Landing Page
            </a>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>