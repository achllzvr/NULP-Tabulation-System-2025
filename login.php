<?php
require __DIR__ . '/includes/bootstrap.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    redirect('/dashboard.php');
}

$pageTitle = 'Login - NULP Tabulation System';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc($pageTitle); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.4.3/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full space-y-8">
        <div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                NULP Tabulation System
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Sign in to your account
            </p>
        </div>
        
        <?php 
        $flash = get_flash_message();
        if ($flash): 
        ?>
        <div class="<?php echo $flash['type'] === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700'; ?> px-4 py-3 rounded border" role="alert">
            <span class="block sm:inline"><?php echo esc($flash['message']); ?></span>
        </div>
        <?php endif; ?>
        
        <form class="mt-8 space-y-6" id="loginForm">
            <div class="rounded-md shadow-sm -space-y-px">
                <div>
                    <label for="username" class="sr-only">Username</label>
                    <input id="username" name="username" type="text" required 
                           class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" 
                           placeholder="Username">
                </div>
                <div>
                    <label for="password" class="sr-only">Password</label>
                    <input id="password" name="password" type="password" required 
                           class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" 
                           placeholder="Password">
                </div>
            </div>

            <div>
                <button type="submit" 
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Sign in
                </button>
            </div>
        </form>
    </div>

    <script>
    document.getElementById('loginForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const data = {
            action: 'login',
            username: formData.get('username'),
            password: formData.get('password')
        };
        
        try {
            const response = await fetch('/api/api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (result.success) {
                window.location.href = '/dashboard.php';
            } else {
                alert(result.error || 'Login failed');
            }
        } catch (error) {
            alert('Network error. Please try again.');
        }
    });
    </script>
</body>
</html>