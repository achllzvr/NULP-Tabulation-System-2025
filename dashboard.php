<?php
require_once __DIR__ . '/includes/bootstrap.php';

ensure_logged_in();

$current_user = get_current_app_user();
$current_pageant = get_current_app_pageant();

// Handle pageant selection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['select_pageant'])) {
    $pageant_id = (int)$_POST['pageant_id'];
    if ($pageant->set_current_pageant($pageant_id)) {
        header('Location: /dashboard.php');
        exit;
    }
}

// Get user's pageants
$user_pageants = $pageant->list_user_pageants($current_user['id']);

$title = 'Dashboard - NULP Tabulation System';
include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/nav.php';
?>

<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
            <p class="mt-2 text-gray-600">Welcome back, <?= esc($current_user['full_name']) ?>!</p>
        </div>

        <?php if (!$current_pageant): ?>
        <!-- Pageant Selection -->
        <div class="card mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Select a Pageant</h2>
            
            <?php if (empty($user_pageants)): ?>
            <div class="text-center py-8">
                <div class="text-gray-500 mb-4">
                    <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No Pageants Found</h3>
                <p class="text-gray-600">You don't have access to any pageants yet. Contact your administrator to get assigned to a pageant.</p>
            </div>
            <?php else: ?>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($user_pageants as $p): ?>
                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                    <h3 class="font-semibold text-gray-900"><?= esc($p['name']) ?></h3>
                    <p class="text-sm text-gray-600 mt-1"><?= esc($p['description'] ?? '') ?></p>
                    <div class="mt-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            <?= esc(ucfirst($p['user_role'])) ?>
                        </span>
                    </div>
                    <form method="POST" class="mt-4">
                        <input type="hidden" name="pageant_id" value="<?= $p['id'] ?>">
                        <button type="submit" name="select_pageant" class="btn-primary text-sm w-full">
                            Select Pageant
                        </button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <!-- Main Dashboard Content -->
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
            <!-- Quick Stats -->
            <div class="card">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Pageant Overview</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Participants:</span>
                        <span class="font-medium">
                            <?php
                            $participants = $pageant->get_pageant_participants($current_pageant['id']);
                            echo count($participants);
                            ?>
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Rounds:</span>
                        <span class="font-medium">
                            <?php
                            $round_list = $pageant->list_pageant_rounds($current_pageant['id']);
                            echo count($round_list);
                            ?>
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Status:</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            Active
                        </span>
                    </div>
                </div>
            </div>

            <!-- Current Round Status -->
            <div class="card">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Current Round</h3>
                <?php
                $active_round = $rounds->get_active_round($current_pageant['id']);
                if ($active_round):
                ?>
                <div class="space-y-2">
                    <div class="font-medium text-blue-600"><?= esc($active_round['name']) ?></div>
                    <div class="text-sm text-gray-600"><?= esc($active_round['type']) ?></div>
                    <div class="text-xs text-gray-500">
                        Opened: <?= date('M j, Y g:i A', strtotime($active_round['opened_at'])) ?>
                    </div>
                </div>
                <?php else: ?>
                <div class="text-gray-500 text-center py-4">
                    No active round
                </div>
                <?php endif; ?>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                <div class="space-y-2">
                    <?php if (($current_user['role'] ?? '') === 'admin'): ?>
                    <a href="/participants.php" class="block btn-primary text-center text-sm">Manage Participants</a>
                    <a href="/rounds.php" class="block btn-secondary text-center text-sm">Manage Rounds</a>
                    <a href="/live_control.php" class="block btn-success text-center text-sm">Live Control</a>
                    <?php elseif (($current_user['role'] ?? '') === 'judge'): ?>
                    <a href="/rounds.php" class="block btn-primary text-center text-sm">Score Current Round</a>
                    <a href="/leaderboard.php" class="block btn-secondary text-center text-sm">View Leaderboard</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Activity or Announcements -->
        <div class="card">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">System Status</h3>
            <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="w-5 h-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h4 class="text-sm font-medium text-blue-800">Pageant System Ready</h4>
                        <p class="mt-1 text-sm text-blue-700">
                            All systems are operational. Current pageant: <strong><?= esc($current_pageant['name']) ?></strong>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>