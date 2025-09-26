<?php
require __DIR__ . '/includes/bootstrap.php';
auth_require_login();

$pageTitle = 'Scoring';
include __DIR__ . '/includes/head.php';

$user = auth_user();
?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="md:flex md:items-center md:justify-between">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold text-gray-900 sm:text-3xl sm:truncate">
                Scoring Workspace
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                Submit your scores for the current round
            </p>
        </div>
    </div>

    <!-- Current Round Info -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                    <span class="text-white text-sm font-medium">R</span>
                </div>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">
                    No Active Round
                </h3>
                <p class="text-sm text-blue-700">
                    Scoring is currently closed. Please wait for the administrator to open a round.
                </p>
            </div>
        </div>
    </div>

    <!-- Scoring Interface (placeholder) -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Score Submission</h3>
            
            <!-- Judge Info -->
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="text-sm font-medium text-gray-900">Judge Information</h4>
                        <p class="text-sm text-gray-600">
                            <?php echo esc($user['first_name'] ?? $user['username']); ?> 
                            (<?php echo esc(ucfirst($user['role'])); ?>)
                        </p>
                    </div>
                </div>
            </div>

            <!-- Scoring Form Placeholder -->
            <div class="text-center py-12">
                <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
                    <span class="text-2xl text-gray-400">📝</span>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Ready to Score</h3>
                <p class="text-gray-500 mb-4">
                    When a round is active, you'll be able to score participants here.
                </p>
                <div class="space-y-2 text-sm text-gray-600">
                    <p>• Participants will be listed with their numbers and names</p>
                    <p>• Each scoring criteria will have input fields</p>
                    <p>• Scores will be automatically saved as you enter them</p>
                </div>
            </div>
        </div>
    </div>

    <!-- My Previous Scores -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">My Previous Scores</h3>
            
            <div class="text-center py-8">
                <p class="text-gray-500">No scores submitted yet.</p>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>