<?php
require_once 'includes/bootstrap.php';
auth_require_login();

$page_title = 'Dashboard';
$user = auth_user();
$current_pageant = pageant_get_current();

include 'includes/head.php';
?>

<div class="max-w-6xl mx-auto p-6">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
        <p class="text-gray-600">Welcome back, <?php echo esc($user['first_name'] ?? $user['email']); ?>!</p>
    </div>
    
    <!-- Current pageant status -->
    <div class="bg-white rounded-lg shadow mb-6 p-6">
        <h2 class="text-xl font-semibold mb-4">Current Pageant</h2>
        <?php if ($current_pageant): ?>
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-medium"><?php echo esc($current_pageant['pageant_name']); ?></h3>
                    <p class="text-gray-600"><?php echo esc($current_pageant['description'] ?? ''); ?></p>
                </div>
                <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm">Active</span>
            </div>
        <?php else: ?>
            <p class="text-gray-500">No pageant selected. Please select a pageant to begin.</p>
            <!-- TODO: Add pageant selection interface -->
        <?php endif; ?>
    </div>
    
    <!-- Quick actions based on user role -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <?php if (auth_has_role('admin') || auth_has_role('organizer')): ?>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-medium mb-4">Manage Participants</h3>
                <p class="text-gray-600 mb-4">Add, edit, or remove participants from the pageant.</p>
                <a href="participants.php" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    Manage Participants
                </a>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-medium mb-4">Manage Judges</h3>
                <p class="text-gray-600 mb-4">Set up judges and their permissions.</p>
                <a href="judges.php" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                    Manage Judges
                </a>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-medium mb-4">Live Control</h3>
                <p class="text-gray-600 mb-4">Control rounds and live scoring.</p>
                <a href="live_control.php" class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                    Live Control
                </a>
            </div>
        <?php endif; ?>
        
        <?php if (auth_has_role('judge')): ?>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-medium mb-4">Judge Panel</h3>
                <p class="text-gray-600 mb-4">Enter scores for the current round.</p>
                <a href="rounds.php" class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700">
                    Start Judging
                </a>
            </div>
        <?php endif; ?>
        
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium mb-4">Leaderboard</h3>
            <p class="text-gray-600 mb-4">View current standings and results.</p>
            <a href="leaderboard.php" class="inline-flex items-center px-4 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700">
                View Leaderboard
            </a>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium mb-4">Awards</h3>
            <p class="text-gray-600 mb-4">View awards and winners.</p>
            <a href="awards.php" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                View Awards
            </a>
        </div>
    </div>
    
    <!-- TODO: Add recent activity or statistics widgets -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold mb-4">Recent Activity</h2>
        <p class="text-gray-500">No recent activity to display.</p>
        <!-- TODO: Implement activity tracking and display -->
    </div>
</div>

<?php include 'includes/footer.php'; ?>