<?php
require_once 'includes/bootstrap.php';

$page_title = 'Awards';
$current_pageant = pageant_get_current();
$awards = awards_list();

include 'includes/head.php';
?>

<div class="max-w-6xl mx-auto p-6">
    <div class="text-center mb-8">
        <h1 class="text-4xl font-bold text-gray-900 mb-2">
            <?php echo $current_pageant ? esc($current_pageant['pageant_name']) : 'NULP Pageant'; ?>
        </h1>
        <h2 class="text-2xl text-gray-600">Awards & Recognition</h2>
    </div>
    
    <?php if (empty($awards)): ?>
        <div class="bg-white rounded-lg shadow p-8">
            <div class="text-center">
                <span class="text-6xl mb-4 block">🏆</span>
                <p class="text-gray-600">Awards will be announced soon.</p>
            </div>
        </div>
    <?php else: ?>
        <!-- Awards display -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($awards as $award): ?>
                <?php $award_details = awards_get_details($award['award_id']); ?>
                <div class="bg-white rounded-lg shadow-lg p-6 text-center">
                    <div class="mb-4">
                        <span class="text-4xl">🏆</span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">
                        <?php echo esc($award['award_name']); ?>
                    </h3>
                    
                    <?php if ($award_details['winner_participant_id']): ?>
                        <div class="bg-gold-50 border border-gold-200 rounded-lg p-4 mb-4">
                            <p class="text-gold-800 font-semibold">Winner</p>
                            <p class="text-2xl font-bold text-gray-900">
                                #<?php echo esc($award_details['contestant_number']); ?>
                            </p>
                            <p class="text-lg text-gray-700">
                                <?php echo esc($award_details['winner_first_name'] . ' ' . $award_details['winner_last_name']); ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-4">
                            <p class="text-gray-500">To be announced</p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($award['description']): ?>
                        <p class="text-sm text-gray-600">
                            <?php echo esc($award['description']); ?>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- TODO: Add award ceremony details, photos, etc. -->
        <div class="mt-8 bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Award Ceremony</h3>
            <div class="border-l-4 border-purple-400 bg-purple-50 p-4">
                <h4 class="text-sm font-medium text-purple-800">TODO: Award Ceremony Features</h4>
                <ul class="mt-2 text-sm text-purple-700 list-disc list-inside">
                    <li>Award ceremony schedule</li>
                    <li>Photo gallery</li>
                    <li>Video recordings</li>
                    <li>Social media integration</li>
                    <li>Certificate downloads</li>
                </ul>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.bg-gold-50 { background-color: #fffbeb; }
.border-gold-200 { border-color: #fde68a; }
.text-gold-800 { color: #92400e; }
</style>

<?php include 'includes/footer.php'; ?>