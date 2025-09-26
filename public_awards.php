<?php
require __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Awards - NULP Tabulation System';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc($pageTitle); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.4.3/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Public Header -->
    <header class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <h1 class="text-xl font-bold text-gray-900">NULP Tabulation</h1>
                    <span class="ml-2 text-sm text-gray-500">Public View</span>
                </div>
                <nav class="flex space-x-4">
                    <a href="/public_prelim.php" class="text-gray-500 hover:text-gray-700 px-3 py-2 text-sm font-medium">
                        Preliminary
                    </a>
                    <a href="/public_final.php" class="text-gray-500 hover:text-gray-700 px-3 py-2 text-sm font-medium">
                        Final
                    </a>
                    <a href="/public_awards.php" class="text-blue-600 border-b-2 border-blue-600 px-3 py-2 text-sm font-medium">
                        Awards
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="text-center">
            <div class="w-24 h-24 mx-auto bg-purple-100 rounded-full flex items-center justify-center mb-6">
                <span class="text-4xl text-purple-600">🏆</span>
            </div>
            
            <h1 class="text-3xl font-bold text-gray-900 mb-4">
                Awards & Recognition
            </h1>
            
            <div class="max-w-md mx-auto bg-white rounded-lg shadow-sm border border-gray-200 p-8">
                <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
                    <span class="text-2xl text-gray-400">⏳</span>
                </div>
                
                <h2 class="text-xl font-semibold text-gray-900 mb-2">
                    Public View Coming Soon
                </h2>
                
                <p class="text-gray-600 mb-4">
                    Award winners and special recognitions will be displayed here once they are announced.
                </p>
                
                <div class="text-sm text-gray-500">
                    <p>Check back later for:</p>
                    <ul class="mt-2 space-y-1">
                        <li>• Main title winners</li>
                        <li>• Special awards</li>
                        <li>• Category recognitions</li>
                        <li>• Honorary mentions</li>
                    </ul>
                </div>
            </div>
            
            <div class="mt-8 text-sm text-gray-500">
                <p>Award announcements will be published here as they are finalized.</p>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="text-center text-sm text-gray-500">
                &copy; <?php echo date('Y'); ?> NULP Tabulation System. All rights reserved.
            </div>
        </div>
    </footer>
</body>
</html>