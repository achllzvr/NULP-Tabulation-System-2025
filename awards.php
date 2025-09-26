<?php
require __DIR__ . '/includes/bootstrap.php';
auth_require_login();

if (!auth_is_admin()) {
    set_flash_message('error', 'Access denied. Admin privileges required.');
    redirect('/dashboard.php');
}

$pageTitle = 'Awards';
include __DIR__ . '/includes/head.php';
?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="md:flex md:items-center md:justify-between">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold text-gray-900 sm:text-3xl sm:truncate">
                Awards Management
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                Manage awards and special recognitions
            </p>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4">
            <button type="button" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Add Award
            </button>
        </div>
    </div>

    <!-- Main Awards -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                Main Titles
            </h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">
                Primary competition awards based on final scores.
            </p>
        </div>
        
        <div class="border-t border-gray-200">
            <div class="px-4 py-5 sm:p-6">
                <div class="space-y-4">
                    <!-- Winner -->
                    <div class="border border-yellow-200 bg-yellow-50 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-lg font-medium text-yellow-800">🏆 Winner</h4>
                                <p class="text-sm text-yellow-700">Highest overall score</p>
                            </div>
                            <div class="text-right">
                                <div class="text-sm text-yellow-700">Winner: <span class="font-medium">TBD</span></div>
                                <div class="text-xs text-yellow-600">Based on final round scores</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- First Runner-up -->
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-medium text-gray-900">🥈 First Runner-up</h4>
                                <p class="text-sm text-gray-500">Second highest score</p>
                            </div>
                            <div class="text-right">
                                <div class="text-sm text-gray-700">Winner: <span class="font-medium">TBD</span></div>
                                <div class="text-xs text-gray-500">Automatic</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Second Runner-up -->
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-medium text-gray-900">🥉 Second Runner-up</h4>
                                <p class="text-sm text-gray-500">Third highest score</p>
                            </div>
                            <div class="text-right">
                                <div class="text-sm text-gray-700">Winner: <span class="font-medium">TBD</span></div>
                                <div class="text-xs text-gray-500">Automatic</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Special Awards -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                Special Awards
            </h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">
                Additional recognitions and special categories.
            </p>
        </div>
        
        <div class="border-t border-gray-200">
            <div class="px-4 py-5 sm:p-6">
                <div class="space-y-4">
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-medium text-gray-900">🎭 Best in Talent</h4>
                                <p class="text-sm text-gray-500">Highest talent segment score</p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <select class="block px-3 py-1 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm">
                                    <option>Select Winner</option>
                                </select>
                                <button type="button" class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                                    Auto-Calculate
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-medium text-gray-900">👗 Best in Evening Gown</h4>
                                <p class="text-sm text-gray-500">Highest evening gown score</p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <select class="block px-3 py-1 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm">
                                    <option>Select Winner</option>
                                </select>
                                <button type="button" class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                                    Auto-Calculate
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-medium text-gray-900">🎤 People's Choice</h4>
                                <p class="text-sm text-gray-500">Audience favorite</p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <select class="block px-3 py-1 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm">
                                    <option>Select Winner</option>
                                </select>
                                <span class="text-xs text-gray-500">Manual only</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end">
                    <button type="button" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        Save All Awards
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Award History -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                Award History
            </h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">
                Previously assigned awards and changes.
            </p>
        </div>
        
        <div class="border-t border-gray-200">
            <div class="px-4 py-5 sm:p-6">
                <div class="text-center py-8">
                    <p class="text-gray-500">No awards assigned yet.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>