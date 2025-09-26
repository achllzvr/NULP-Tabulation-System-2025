<?php
require_once __DIR__ . '/includes/bootstrap.php';

ensure_logged_in();
ensure_pageant_selected();

$current_user = get_current_user();
$current_pageant = get_current_pageant();

$title = 'Live Control - NULP Tabulation System';
include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/nav.php';
?>

<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Live Control</h1>
            <p class="mt-2 text-gray-600">
                <?php if (($current_user['role'] ?? '') === 'admin'): ?>
                Control live pageant operations and monitor judging progress
                <?php else: ?>
                Judge scoring interface for active rounds
                <?php endif; ?>
            </p>
        </div>

        <div class="card">
            <div class="text-center py-12">
                <div class="text-gray-500 mb-4">
                    <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Live Control Center</h3>
                <p class="text-gray-600 mb-4">
                    <?php if (($current_user['role'] ?? '') === 'admin'): ?>
                    This will be the main control center for managing live pageant operations.
                    <?php else: ?>
                    This will be your scoring interface when rounds are active.
                    <?php endif; ?>
                </p>
                <div class="space-y-2 text-sm text-gray-500">
                    <p>Features to be implemented:</p>
                    <ul class="text-left max-w-md mx-auto space-y-1">
                        <?php if (($current_user['role'] ?? '') === 'admin'): ?>
                        <li>• Real-time judging progress monitoring</li>
                        <li>• Round control (open/close/finalize)</li>
                        <li>• Live leaderboard updates</li>
                        <li>• Judge notification system</li>
                        <li>• Emergency controls and overrides</li>
                        <?php else: ?>
                        <li>• Participant scoring interface</li>
                        <li>• Criteria-based scoring forms</li>
                        <li>• Score submission tracking</li>
                        <li>• Round progress indicators</li>
                        <li>• Score review and edit capabilities</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>