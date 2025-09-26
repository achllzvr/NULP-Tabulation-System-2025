<?php
require_once 'includes/bootstrap.php';
auth_require_login();

$page_title = 'Awards';
$awards = awards_list();

include 'includes/head.php';
?>

<div class="max-w-6xl mx-auto p-6">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Awards</h1>
            <p class="text-gray-600">Manage awards and winners</p>
        </div>
        <a href="dashboard.php" class="text-blue-600 hover:text-blue-800">← Back to Dashboard</a>
    </div>
    
    <!-- Awards list -->
    <?php if (empty($awards)): ?>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-500 text-center">No awards configured for this pageant.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($awards as $award): ?>
                <?php $award_details = awards_get_details($award['award_id']); ?>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold"><?php echo esc($award['award_name']); ?></h3>
                        <?php if ($award_details['winner_participant_id']): ?>
                            <span class="px-2 py-1 bg-gold-100 text-gold-800 rounded-full text-sm">🏆 Winner</span>
                        <?php else: ?>
                            <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded-full text-sm">Pending</span>
                        <?php endif; ?>
                    </div>
                    
                    <p class="text-gray-600 text-sm mb-4"><?php echo esc($award['description'] ?? ''); ?></p>
                    
                    <?php if ($award_details['winner_participant_id']): ?>
                        <div class="border-t pt-4">
                            <p class="text-sm text-gray-500">Winner:</p>
                            <p class="font-medium">
                                #<?php echo esc($award_details['contestant_number']); ?> - 
                                <?php echo esc($award_details['winner_first_name'] . ' ' . $award_details['winner_last_name']); ?>
                            </p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (auth_has_role('admin') || auth_has_role('organizer')): ?>
                        <div class="mt-4 pt-4 border-t">
                            <!-- TODO: Add winner selection interface -->
                            <button class="text-blue-600 hover:text-blue-800 text-sm">
                                <?php echo $award_details['winner_participant_id'] ? 'Change Winner' : 'Select Winner'; ?>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>