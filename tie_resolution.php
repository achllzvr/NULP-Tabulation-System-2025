<?php
require __DIR__ . '/includes/bootstrap.php';
auth_require_login();

if (!auth_is_admin()) {
    set_flash_message('error', 'Access denied. Admin privileges required.');
    redirect('/dashboard.php');
}

$pageTitle = 'Tie Resolution';
include __DIR__ . '/includes/head.php';
?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="md:flex md:items-center md:justify-between">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold text-gray-900 sm:text-3xl sm:truncate">
                Tie Resolution
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                Resolve ties and handle score disputes
            </p>
        </div>
    </div>

    <!-- Tie Detection -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Automatic Tie Detection</h3>
            
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <span class="text-green-500">✅</span>
                    </div>
                    <div class="ml-3">
                        <h4 class="text-sm font-medium text-green-800">
                            No Ties Detected
                        </h4>
                        <p class="text-sm text-green-700 mt-1">
                            All participant scores are unique. No tie resolution needed at this time.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tie Resolution Methods -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Tie Resolution Methods</h3>
            
            <div class="space-y-4">
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <span class="text-blue-500">1️⃣</span>
                        </div>
                        <div class="ml-3">
                            <h4 class="text-sm font-medium text-gray-900">Highest Individual Score</h4>
                            <p class="text-sm text-gray-500 mt-1">
                                Compare the highest individual criteria score among tied participants.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <span class="text-green-500">2️⃣</span>
                        </div>
                        <div class="ml-3">
                            <h4 class="text-sm font-medium text-gray-900">Judge-by-Judge Comparison</h4>
                            <p class="text-sm text-gray-500 mt-1">
                                Compare scores from each individual judge to break the tie.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <span class="text-yellow-500">3️⃣</span>
                        </div>
                        <div class="ml-3">
                            <h4 class="text-sm font-medium text-gray-900">Manual Override</h4>
                            <p class="text-sm text-gray-500 mt-1">
                                Manually specify the ranking when automatic methods cannot resolve the tie.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Manual Tie Resolution -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Manual Tie Resolution</h3>
            
            <div class="text-center py-12">
                <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
                    <span class="text-2xl text-gray-400">⚖️</span>
                </div>
                <h4 class="text-lg font-medium text-gray-900 mb-2">No Active Ties</h4>
                <p class="text-gray-500 mb-4">
                    Tie resolution tools will appear here when ties are detected.
                </p>
                <button type="button" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" disabled>
                    Check for Ties
                </button>
            </div>
        </div>
    </div>

    <!-- Tie Resolution History -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Resolution History</h3>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Round
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Participants
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Method Used
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Resolution
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                No tie resolutions recorded yet.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>