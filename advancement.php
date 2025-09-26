<?php
require __DIR__ . '/includes/bootstrap.php';
auth_require_login();

if (!auth_is_admin()) {
    set_flash_message('error', 'Access denied. Admin privileges required.');
    redirect('/dashboard.php');
}

$pageTitle = 'Advancement';
include __DIR__ . '/includes/head.php';
?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="md:flex md:items-center md:justify-between">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold text-gray-900 sm:text-3xl sm:truncate">
                Advancement Control
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                Manage participant advancement to next rounds
            </p>
        </div>
    </div>

    <!-- Advancement Status -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Current Round Status</h3>
            
            <div class="bg-gray-50 rounded-lg p-4">
                <div class="text-center py-6">
                    <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <span class="text-2xl text-gray-400">⏳</span>
                    </div>
                    <h4 class="text-lg font-medium text-gray-900 mb-2">No Active Round</h4>
                    <p class="text-gray-500">
                        Advancement options will be available after completing a round.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Advancement Rules -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Advancement Rules</h3>
            
            <div class="space-y-4">
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-medium text-gray-900">Preliminary to Semi-Final</h4>
                            <p class="text-sm text-gray-500">Top 15 participants advance</p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                Pending
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-medium text-gray-900">Semi-Final to Final</h4>
                            <p class="text-sm text-gray-500">Top 5 participants advance</p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                Pending
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Manual Advancement -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Manual Advancement</h3>
            
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <span class="text-yellow-400">⚠️</span>
                    </div>
                    <div class="ml-3">
                        <h4 class="text-sm font-medium text-yellow-800">
                            Manual Override Available
                        </h4>
                        <p class="text-sm text-yellow-700 mt-1">
                            You can manually select participants to advance if needed.
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="text-center py-8">
                <p class="text-gray-500 mb-4">Manual advancement controls will appear here when a round is complete.</p>
                <button type="button" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" disabled>
                    Configure Advancement
                </button>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>