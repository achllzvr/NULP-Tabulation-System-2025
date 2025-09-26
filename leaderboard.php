<?php
require_once 'includes/bootstrap.php';
auth_require_login();

$page_title = 'Leaderboard';
$current_pageant = pageant_get_current();
$rounds = pageant_list_rounds();

// Get selected round or default to latest closed round
$selected_round_id = $_GET['round_id'] ?? null;
$leaderboard = [];

if ($selected_round_id) {
    $leaderboard = scores_aggregate_round($selected_round_id);
}

include 'includes/head.php';
?>

<div class="max-w-6xl mx-auto p-6">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Leaderboard</h1>
            <p class="text-gray-600">Current standings and rankings</p>
        </div>
        <a href="dashboard.php" class="text-blue-600 hover:text-blue-800">← Back to Dashboard</a>
    </div>
    
    <!-- Round selector -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4">Select Round</h2>
        <div class="flex flex-wrap gap-4">
            <?php foreach ($rounds as $round): ?>
                <a href="?round_id=<?php echo $round['round_id']; ?>" 
                   class="px-4 py-2 rounded-md <?php 
                       echo $selected_round_id == $round['round_id'] 
                           ? 'bg-blue-600 text-white' 
                           : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; 
                   ?>">
                    <?php echo esc($round['round_name']); ?>
                    <span class="text-xs">(<?php echo ucfirst($round['status']); ?>)</span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Leaderboard -->
    <?php if (empty($leaderboard)): ?>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-500 text-center">
                <?php if (!$selected_round_id): ?>
                    Select a round to view the leaderboard.
                <?php else: ?>
                    No scores available for this round yet.
                <?php endif; ?>
            </p>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Rank
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Contestant
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Name
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Total Score
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Progress
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($leaderboard as $index => $participant): ?>
                        <tr class="<?php echo $index < 3 ? 'bg-yellow-50' : ''; ?>">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center">
                                    <?php if ($index === 0): ?>
                                        <span class="text-2xl">🥇</span>
                                    <?php elseif ($index === 1): ?>
                                        <span class="text-2xl">🥈</span>
                                    <?php elseif ($index === 2): ?>
                                        <span class="text-2xl">🥉</span>
                                    <?php else: ?>
                                        <span class="text-gray-600 font-bold"><?php echo $index + 1; ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                #<?php echo esc($participant['contestant_number']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo esc($participant['first_name'] . ' ' . $participant['last_name']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <span class="font-bold text-lg"><?php echo number_format($participant['weighted_total'], 2); ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo $participant['criteria_scored']; ?> criteria, 
                                <?php echo $participant['judges_scored']; ?> judges
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>