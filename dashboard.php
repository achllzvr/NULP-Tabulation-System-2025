<?php
require __DIR__ . '/includes/bootstrap.php';
auth_require_login();

$pageTitle = 'Dashboard';
include __DIR__ . '/includes/head.php';

$user = auth_user();
$currentPageant = pageant_get_current();
?>

<div class="space-y-6">
    <!-- Welcome Section -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-2">
                Welcome back, <?php echo esc($user['first_name'] ?? $user['username']); ?>!
            </h1>
            <p class="text-gray-600">
                Role: <span class="font-medium"><?php echo esc(ucfirst($user['role'])); ?></span>
            </p>
            
            <?php if ($currentPageant): ?>
            <p class="text-gray-600 mt-1">
                Current Pageant: <span class="font-medium"><?php echo esc($currentPageant['name']); ?></span>
            </p>
            <?php else: ?>
            <p class="text-amber-600 mt-1">
                No pageant selected. Please contact an administrator.
            </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Participants -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                            <span class="text-white text-sm font-medium">P</span>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Participants</dt>
                            <dd class="text-lg font-medium text-gray-900">-</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Judges -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                            <span class="text-white text-sm font-medium">J</span>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Active Judges</dt>
                            <dd class="text-lg font-medium text-gray-900">-</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rounds -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                            <span class="text-white text-sm font-medium">R</span>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Current Round</dt>
                            <dd class="text-lg font-medium text-gray-900">-</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Scores -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                            <span class="text-white text-sm font-medium">S</span>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Submitted Scores</dt>
                            <dd class="text-lg font-medium text-gray-900">-</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Quick Actions</h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                
                <?php if (auth_is_admin()): ?>
                <a href="/participants.php" class="bg-blue-50 hover:bg-blue-100 p-4 rounded-lg border border-blue-200 transition-colors">
                    <div class="text-blue-600 font-medium">Manage Participants</div>
                    <div class="text-blue-500 text-sm mt-1">Add, edit, or remove participants</div>
                </a>
                
                <a href="/rounds.php" class="bg-green-50 hover:bg-green-100 p-4 rounded-lg border border-green-200 transition-colors">
                    <div class="text-green-600 font-medium">Control Rounds</div>
                    <div class="text-green-500 text-sm mt-1">Open and close scoring rounds</div>
                </a>
                <?php endif; ?>
                
                <a href="/scoring.php" class="bg-yellow-50 hover:bg-yellow-100 p-4 rounded-lg border border-yellow-200 transition-colors">
                    <div class="text-yellow-600 font-medium">Score Participants</div>
                    <div class="text-yellow-500 text-sm mt-1">Submit your scores</div>
                </a>
                
                <a href="/leaderboard.php" class="bg-purple-50 hover:bg-purple-100 p-4 rounded-lg border border-purple-200 transition-colors">
                    <div class="text-purple-600 font-medium">View Leaderboard</div>
                    <div class="text-purple-500 text-sm mt-1">Check current standings</div>
                </a>
            </div>
        </div>
    </div>

    <!-- Recent Activity (placeholder) -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Recent Activity</h3>
            <div class="text-gray-500 text-center py-8">
                <p>Activity feed coming soon...</p>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>