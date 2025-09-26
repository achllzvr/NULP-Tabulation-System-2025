<?php
require_once 'includes/bootstrap.php';

$page_title = 'Final Results';
$current_pageant = pageant_get_current();

include 'includes/head.php';
?>

<div class="max-w-6xl mx-auto p-6">
    <div class="text-center mb-8">
        <h1 class="text-4xl font-bold text-gray-900 mb-2">
            <?php echo $current_pageant ? esc($current_pageant['pageant_name']) : 'NULP Pageant'; ?>
        </h1>
        <h2 class="text-2xl text-gray-600">Final Results</h2>
    </div>
    
    <!-- TODO: Implement public final results display -->
    <div class="bg-white rounded-lg shadow p-8">
        <div class="text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-gold-100 rounded-full mb-4">
                <span class="text-2xl">🏆</span>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">Final Results</h3>
            <p class="text-gray-600 mb-6">Final results and winners will be announced here.</p>
            
            <div class="border-l-4 border-gold-400 bg-gold-50 p-4 text-left">
                <h4 class="text-sm font-medium text-gold-800">TODO: Public Final Results Display</h4>
                <ul class="mt-2 text-sm text-gold-700 list-disc list-inside">
                    <li>Final rankings with podium display</li>
                    <li>Winner announcements</li>
                    <li>Score breakdowns</li>
                    <li>Photo gallery integration</li>
                    <li>Social media sharing</li>
                    <li>Certificate generation</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
.bg-gold-100 { background-color: #fef3c7; }
.text-gold-800 { color: #92400e; }
.text-gold-700 { color: #b45309; }
.border-gold-400 { border-color: #fbbf24; }
.bg-gold-50 { background-color: #fffbeb; }
</style>

<?php include 'includes/footer.php'; ?>