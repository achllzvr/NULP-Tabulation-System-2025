<?php
/**
 * Partial: Head
 * Common HTML head section with Tailwind CDN and basic meta tags
 * Expected vars: $pageTitle (optional), $additionalCSS (optional), $additionalJS (optional)
 */

$pageTitle = $pageTitle ?? 'Pageant Tabulation System';
$additionalCSS = $additionalCSS ?? '';
$additionalJS = $additionalJS ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= Util::escape($pageTitle) ?></title>
    
    <!-- Tailwind CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.4.3/dist/tailwind.min.css" rel="stylesheet">
    
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