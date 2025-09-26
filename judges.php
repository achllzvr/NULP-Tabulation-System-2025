<?php
require_once __DIR__ . '/includes/bootstrap.php';

ensure_logged_in();
ensure_pageant_selected();

$current_user = get_current_user();
$current_pageant = get_current_pageant();

// Only admin can manage judges
if (($current_user['role'] ?? '') !== 'admin') {
    header('Location: /dashboard.php');
    exit;
}

$title = 'Judges - NULP Tabulation System';
include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/nav.php';
?>

<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Judges</h1>
            <p class="mt-2 text-gray-600">Manage judges for <?= esc($current_pageant['name']) ?></p>
        </div>

        <div class="card">
            <div class="text-center py-12">
                <div class="text-gray-500 mb-4">
                    <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Judge Management</h3>
                <p class="text-gray-600 mb-4">This functionality will be implemented to manage judge assignments, credentials, and permissions.</p>
                <div class="space-y-2 text-sm text-gray-500">
                    <p>Features to be implemented:</p>
                    <ul class="text-left max-w-md mx-auto space-y-1">
                        <li>• Add/remove judges</li>
                        <li>• Assign judges to specific rounds</li>
                        <li>• Generate judge login credentials</li>
                        <li>• Monitor judge submission status</li>
                        <li>• Send notifications to judges</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>