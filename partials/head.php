<?php
/**
 * Partial: Head
 * Common HTML head section with Tailwind CDN and basic meta tags
 * Expected vars: $pageTitle (optional), $additionalCSS (optional), $additionalJS (optional)
 */

// (Error reporting now centralized in includes/bootstrap.php; keep file slim.)

$pageTitle = $pageTitle ?? 'Pageant Tabulation System';
$additionalCSS = $additionalCSS ?? '';
$additionalJS = $additionalJS ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($pageTitle) ?></title>
    
    <!-- Tailwind CSS - Static version for better performance -->
    <link href="https://unpkg.com/tailwindcss@^3.0/dist/tailwind.min.css" rel="stylesheet">
    
    <!-- Fallback: Tailwind Play CDN script (loads if CSS fails) -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Fallback CSS for basic styling if Tailwind fails to load -->
    <style>
        .bg-gray-50 { background-color: #f9fafb; }
        .text-gray-900 { color: #111827; }
        .min-h-screen { min-height: 100vh; }
        .bg-gradient-to-br { background: linear-gradient(to bottom right, #eff6ff, #e0e7ff); }
        .from-blue-50 { --tw-gradient-from: #eff6ff; }
        .to-indigo-100 { --tw-gradient-to: #e0e7ff; }
        .flex { display: flex; }
        .items-center { align-items: center; }
        .justify-center { justify-content: center; }
        .p-4 { padding: 1rem; }
        .bg-white { background-color: #ffffff; }
        .shadow { box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1); }
        .rounded-lg { border-radius: 0.5rem; }
        .text-2xl { font-size: 1.5rem; line-height: 2rem; }
        .font-bold { font-weight: 700; }
        .mb-4 { margin-bottom: 1rem; }
        /* Add more fallback styles as needed */
    </style>
    
    <!-- Additional CSS -->
    <?php if ($additionalCSS): ?>
        <?= $additionalCSS ?>
    <?php endif; ?>
    
    <!-- Global JavaScript Configuration -->
    <script>
        window.APP_API_BASE = 'api/api.php';
    </script>
    
    <!-- Additional JS (head section) -->
    <?php if ($additionalJS): ?>
        <?= $additionalJS ?>
    <?php endif; ?>
</head>
<body class="bg-gray-50 text-gray-900">