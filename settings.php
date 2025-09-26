<?php
require_once __DIR__ . '/includes/bootstrap.php';

ensure_logged_in();
ensure_pageant_selected();

$title = 'Settings - NULP Tabulation System';
include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/nav.php';
?>

<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
        <h1 class="text-3xl font-bold text-gray-900 mb-6">Settings</h1>
        <div class="card text-center py-12">
            <h3 class="text-lg font-medium text-gray-900 mb-2">System Settings</h3>
            <p class="text-gray-600">Configure pageant settings, visibility options, and system preferences.</p>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>