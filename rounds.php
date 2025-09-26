<?php
require_once 'includes/bootstrap.php';
auth_require_login();

$page_title = 'Rounds';
$rounds = pageant_list_rounds();
$current_pageant = pageant_get_current();

include 'includes/head.php';
?>

<div class="max-w-6xl mx-auto p-6">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Rounds</h1>
            <p class="text-gray-600">
                <?php if ($current_pageant): ?>
                    <?php echo esc($current_pageant['pageant_name']); ?> - Round Management
                <?php else: ?>
                    No pageant selected
                <?php endif; ?>
            </p>
        </div>
        <a href="dashboard.php" class="text-blue-600 hover:text-blue-800">← Back to Dashboard</a>
    </div>
    
    <?php if (empty($rounds)): ?>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-500 text-center">
                No rounds configured for this pageant.
                <?php if (auth_has_role('admin') || auth_has_role('organizer')): ?>
                    Please configure rounds in the settings.
                <?php endif; ?>
            </p>
        </div>
    <?php else: ?>
        <div class="space-y-6">
            <?php foreach ($rounds as $round): ?>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-xl font-semibold"><?php echo esc($round['round_name']); ?></h2>
                            <p class="text-gray-600"><?php echo esc($round['description'] ?? ''); ?></p>
                        </div>
                        <div class="flex items-center space-x-4">
                            <span class="px-3 py-1 rounded-full text-sm <?php 
                                echo $round['status'] === 'open' ? 'bg-green-100 text-green-800' : 
                                    ($round['status'] === 'closed' ? 'bg-gray-100 text-gray-800' : 'bg-blue-100 text-blue-800');
                            ?>">
                                <?php echo ucfirst(esc($round['status'])); ?>
                            </span>
                            
                            <?php if (auth_has_role('admin') || auth_has_role('organizer')): ?>
                                <?php if ($round['status'] === 'pending'): ?>
                                    <button onclick="openRound(<?php echo $round['round_id']; ?>)" 
                                            class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                                        Open Round
                                    </button>
                                <?php elseif ($round['status'] === 'open'): ?>
                                    <button onclick="closeRound(<?php echo $round['round_id']; ?>)" 
                                            class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                                        Close Round
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php if (auth_has_role('judge') && $round['status'] === 'open'): ?>
                                <a href="live_control.php?round_id=<?php echo $round['round_id']; ?>" 
                                   class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700">
                                    Start Judging
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- TODO: Add round details like criteria, participants, scoring progress -->
                    <div class="border-t pt-4">
                        <p class="text-sm text-gray-500">
                            Round details and scoring progress will be displayed here.
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function openRound(roundId) {
    if (confirm('Are you sure you want to open this round? This will close any currently open round.')) {
        fetch('/api/api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=open_round&round_id=${roundId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to open round');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred');
        });
    }
}

function closeRound(roundId) {
    if (confirm('Are you sure you want to close this round?')) {
        fetch('/api/api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=close_round&round_id=${roundId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to close round');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred');
        });
    }
}
</script>

<?php include 'includes/footer.php'; ?>