<?php
require_once 'includes/bootstrap.php';
auth_require_login();
auth_require_role(['admin', 'organizer']);

$page_title = 'Advancement';

include 'includes/head.php';
?>

<div class="max-w-6xl mx-auto p-6">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Advancement</h1>
            <p class="text-gray-600">Manage participant advancement to next rounds</p>
        </div>
        <a href="dashboard.php" class="text-blue-600 hover:text-blue-800">← Back to Dashboard</a>
    </div>
    
    <!-- TODO: Implement advancement logic -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold mb-4">Round Advancement</h2>
        <div class="border-l-4 border-orange-400 bg-orange-50 p-4">
            <h3 class="text-sm font-medium text-orange-800">TODO: Implement Advancement System</h3>
            <ul class="mt-2 text-sm text-orange-700 list-disc list-inside">
                <li>Define advancement criteria (top N, score threshold, etc.)</li>
                <li>Semi-automatic participant advancement</li>
                <li>Manual override capabilities</li>
                <li>Cut-off management</li>
                <li>Advancement history tracking</li>
                <li>Integration with tie resolution system</li>
            </ul>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>