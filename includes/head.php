<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc($pageTitle ?? 'NULP Tabulation System'); ?></title>
    
    <!-- Tailwind CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.4.3/dist/tailwind.min.css" rel="stylesheet">
    
    <!-- Additional meta tags -->
    <meta name="description" content="NULP Pageant Tabulation System">
    <meta name="author" content="NULP">
    
    <!-- Favicon placeholder -->
    <link rel="icon" type="image/x-icon" href="/assets/favicon.ico">
</head>
<body class="bg-gray-50 min-h-screen">
    <?php 
    // Include navigation if user is logged in
    if (isset($_SESSION['user_id'])): 
        include __DIR__ . '/nav.php'; 
    endif; 
    ?>
    
    <!-- Flash messages -->
    <?php 
    $flash = get_flash_message();
    if ($flash): 
    ?>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
        <div class="<?php echo $flash['type'] === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700'; ?> px-4 py-3 rounded border" role="alert">
            <span class="block sm:inline"><?php echo esc($flash['message']); ?></span>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Main content wrapper -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">