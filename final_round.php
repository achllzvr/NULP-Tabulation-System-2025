<?php
require_once 'includes/bootstrap.php';
auth_require_login();

$page_title = 'Final Round';

include 'includes/head.php';
?>

<div class="max-w-6xl mx-auto p-6">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Final Round</h1>
            <p class="text-gray-600">Final round scoring and results</p>
        </div>
        <a href="dashboard.php" class="text-blue-600 hover:text-blue-800">← Back to Dashboard</a>
    </div>
    
    <!-- TODO: Implement final round interface -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold mb-4">Final Round Management</h2>
        <div class="border-l-4 border-red-400 bg-red-50 p-4">
            <h3 class="text-sm font-medium text-red-800">TODO: Implement Final Round Features</h3>
            <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                <li>Final round participant list</li>
                <li>Special final round criteria</li>
                <li>Real-time final scoring</li>
                <li>Final rankings calculation</li>
                <li>Winner determination logic</li>
                <li>Results announcement interface</li>
            </ul>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>