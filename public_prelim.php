<?php
require_once 'includes/bootstrap.php';

$page_title = 'Preliminary Results';
$current_pageant = pageant_get_current();

include 'includes/head.php';
?>

<div class="max-w-6xl mx-auto p-6">
    <div class="text-center mb-8">
        <h1 class="text-4xl font-bold text-gray-900 mb-2">
            <?php echo $current_pageant ? esc($current_pageant['pageant_name']) : 'NULP Pageant'; ?>
        </h1>
        <h2 class="text-2xl text-gray-600">Preliminary Results</h2>
    </div>
    
    <!-- TODO: Implement public preliminary results display -->
    <div class="bg-white rounded-lg shadow p-8">
        <div class="text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-100 rounded-full mb-4">
                <span class="text-2xl">📊</span>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">Preliminary Results</h3>
            <p class="text-gray-600 mb-6">Results will be displayed here once preliminary rounds are completed.</p>
            
            <div class="border-l-4 border-blue-400 bg-blue-50 p-4 text-left">
                <h4 class="text-sm font-medium text-blue-800">TODO: Public Preliminary Display</h4>
                <ul class="mt-2 text-sm text-blue-700 list-disc list-inside">
                    <li>Real-time preliminary results</li>
                    <li>Contestant standings</li>
                    <li>Advancement announcements</li>
                    <li>Next round information</li>
                    <li>Auto-refresh functionality</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>