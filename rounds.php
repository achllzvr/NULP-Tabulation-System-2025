<?php
require_once __DIR__ . '/includes/bootstrap.php';

ensure_logged_in();
ensure_pageant_selected();

$current_user = get_current_user();
$current_pageant = get_current_pageant();

$round_list = $pageant->list_pageant_rounds($current_pageant['id']);
$active_round = $rounds->get_active_round($current_pageant['id']);

// Handle round actions (admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($current_user['role'] ?? '') === 'admin') {
    if (isset($_POST['open_round'])) {
        $round_id = (int)$_POST['round_id'];
        if ($rounds->open_round($round_id)) {
            header('Location: /rounds.php?success=round_opened');
            exit;
        }
    } elseif (isset($_POST['close_round'])) {
        $round_id = (int)$_POST['round_id'];
        if ($rounds->close_round($round_id)) {
            header('Location: /rounds.php?success=round_closed');
            exit;
        }
    }
}

$title = 'Rounds - NULP Tabulation System';
include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/nav.php';
?>

<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Rounds</h1>
            <p class="mt-2 text-gray-600">
                <?php if (($current_user['role'] ?? '') === 'admin'): ?>
                Manage competition rounds for <?= esc($current_pageant['name']) ?>
                <?php else: ?>
                View and score active rounds for <?= esc($current_pageant['name']) ?>
                <?php endif; ?>
            </p>
        </div>

        <?php if (isset($_GET['success'])): ?>
        <div class="mb-6 alert bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-md">
            <?php
            switch ($_GET['success']) {
                case 'round_opened': echo 'Round opened successfully!'; break;
                case 'round_closed': echo 'Round closed successfully!'; break;
                default: echo 'Action completed successfully!';
            }
            ?>
        </div>
        <?php endif; ?>

        <!-- Active Round (if any) -->
        <?php if ($active_round): ?>
        <div class="card mb-6 border-l-4 border-l-green-500">
            <div class="flex justify-between items-start">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">
                        🟢 Active Round: <?= esc($active_round['name']) ?>
                    </h2>
                    <p class="text-gray-600 mt-1"><?= esc($active_round['description'] ?? '') ?></p>
                    <div class="mt-2 text-sm text-gray-500">
                        Type: <?= esc($active_round['type']) ?> | 
                        Opened: <?= date('M j, Y g:i A', strtotime($active_round['opened_at'])) ?>
                    </div>
                </div>
                <div class="flex space-x-2">
                    <?php if (($current_user['role'] ?? '') === 'judge'): ?>
                    <a href="/live_control.php" class="btn-primary">Score Participants</a>
                    <?php elseif (($current_user['role'] ?? '') === 'admin'): ?>
                    <form method="POST" class="inline">
                        <input type="hidden" name="round_id" value="<?= $active_round['id'] ?>">
                        <button type="submit" name="close_round" class="btn-danger" 
                                onclick="return confirmAction('Are you sure you want to close this round?')">
                            Close Round
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Round criteria preview -->
            <?php
            $criteria = $rounds->get_round_criteria($active_round['id']);
            if (!empty($criteria)):
            ?>
            <div class="mt-4 pt-4 border-t border-gray-200">
                <h4 class="font-medium text-gray-900 mb-2">Scoring Criteria:</h4>
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-3">
                    <?php foreach ($criteria as $criterion): ?>
                    <div class="text-sm">
                        <div class="font-medium"><?= esc($criterion['name']) ?></div>
                        <div class="text-gray-600">
                            Weight: <?= number_format($criterion['weight'] * 100, 1) ?>% | 
                            Max: <?= $criterion['max_score'] ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- All Rounds -->
        <div class="card">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-semibold text-gray-900">All Rounds</h2>
                <?php if (($current_user['role'] ?? '') === 'admin'): ?>
                <button class="btn-primary" onclick="alert('Create round functionality would be implemented here')">
                    Create Round
                </button>
                <?php endif; ?>
            </div>

            <?php if (empty($round_list)): ?>
            <div class="text-center py-12">
                <div class="text-gray-500 mb-4">
                    <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No Rounds Created</h3>
                <p class="text-gray-600 mb-4">Create competition rounds to begin judging.</p>
                <?php if (($current_user['role'] ?? '') === 'admin'): ?>
                <button class="btn-primary" onclick="alert('Create round functionality would be implemented here')">
                    Create First Round
                </button>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($round_list as $round): ?>
                <div class="border border-gray-200 rounded-lg p-4 <?= $round['state'] === 'OPEN' ? 'border-green-300 bg-green-50' : '' ?>">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <div class="flex items-center space-x-2">
                                <h3 class="font-semibold text-gray-900"><?= esc($round['name']) ?></h3>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    <?php
                                    switch ($round['state']) {
                                        case 'OPEN': echo 'bg-green-100 text-green-800'; break;
                                        case 'CLOSED': echo 'bg-yellow-100 text-yellow-800'; break;
                                        case 'FINALIZED': echo 'bg-blue-100 text-blue-800'; break;
                                        default: echo 'bg-gray-100 text-gray-800';
                                    }
                                    ?>">
                                    <?= esc(ucfirst(strtolower($round['state']))) ?>
                                </span>
                            </div>
                            <p class="text-gray-600 mt-1"><?= esc($round['description'] ?? '') ?></p>
                            <div class="mt-2 text-sm text-gray-500">
                                Type: <?= esc($round['type']) ?> | 
                                Sequence: <?= $round['sequence_number'] ?>
                                <?php if ($round['opened_at']): ?>
                                | Opened: <?= date('M j, g:i A', strtotime($round['opened_at'])) ?>
                                <?php endif; ?>
                                <?php if ($round['closed_at']): ?>
                                | Closed: <?= date('M j, g:i A', strtotime($round['closed_at'])) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if (($current_user['role'] ?? '') === 'admin'): ?>
                        <div class="flex space-x-2 ml-4">
                            <?php if ($round['state'] === 'PENDING'): ?>
                            <form method="POST" class="inline">
                                <input type="hidden" name="round_id" value="<?= $round['id'] ?>">
                                <button type="submit" name="open_round" class="btn-success text-sm">
                                    Open Round
                                </button>
                            </form>
                            <?php elseif ($round['state'] === 'OPEN'): ?>
                            <form method="POST" class="inline">
                                <input type="hidden" name="round_id" value="<?= $round['id'] ?>">
                                <button type="submit" name="close_round" class="btn-danger text-sm"
                                        onclick="return confirmAction('Are you sure you want to close this round?')">
                                    Close Round
                                </button>
                            </form>
                            <?php endif; ?>
                            <button class="btn-secondary text-sm" onclick="alert('Edit round functionality would be implemented here')">
                                Edit
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>