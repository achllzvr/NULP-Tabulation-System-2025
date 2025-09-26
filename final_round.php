<?php
require __DIR__ . '/includes/bootstrap.php';
auth_require_login();

if (!auth_is_admin()) {
    set_flash_message('error', 'Access denied. Admin privileges required.');
    redirect('/dashboard.php');
}

$pageTitle = 'Final Round';
include __DIR__ . '/includes/head.php';
?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="md:flex md:items-center md:justify-between">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold text-gray-900 sm:text-3xl sm:truncate">
                Final Round
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                Manage the final round and determine winners
            </p>
        </div>
    </div>

    <!-- Final Round Status -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Final Round Status</h3>
            
            <div class="bg-gray-50 rounded-lg p-4">
                <div class="text-center py-6">
                    <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <span class="text-2xl text-gray-400">🏆</span>
                    </div>
                    <h4 class="text-lg font-medium text-gray-900 mb-2">Final Round Not Started</h4>
                    <p class="text-gray-500 mb-4">
                        Complete preliminary and semi-final rounds first.
                    </p>
                    <button type="button" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" disabled>
                        Start Final Round
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Finalists -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Finalists</h3>
            
            <div class="text-center py-12">
                <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
                    <span class="text-2xl text-gray-400">👑</span>
                </div>
                <h4 class="text-lg font-medium text-gray-900 mb-2">No Finalists Yet</h4>
                <p class="text-gray-500">
                    Finalists will be determined after semi-final round completion.
                </p>
            </div>
        </div>
    </div>

    <!-- Final Question & Scoring -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Final Question & Scoring</h3>
            
            <div class="space-y-4">
                <div>
                    <label for="final_question" class="block text-sm font-medium text-gray-700 mb-2">
                        Final Question
                    </label>
                    <textarea id="final_question" rows="3" 
                              class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
                              placeholder="Enter the final question for all finalists..."
                              disabled></textarea>
                </div>
                
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <span class="text-blue-500">ℹ️</span>
                        </div>
                        <div class="ml-3">
                            <h4 class="text-sm font-medium text-blue-800">
                                Final Round Scoring
                            </h4>
                            <p class="text-sm text-blue-700 mt-1">
                                In the final round, each finalist will answer the same question and be scored by all judges.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Winner Selection -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Winner Selection</h3>
            
            <div class="text-center py-8">
                <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
                    <span class="text-2xl text-gray-400">🎯</span>
                </div>
                <h4 class="text-lg font-medium text-gray-900 mb-2">Ready for Winner Selection</h4>
                <p class="text-gray-500 mb-4">
                    Winner will be automatically determined based on final round scores.
                </p>
                <button type="button" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" disabled>
                    Calculate Final Results
                </button>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>