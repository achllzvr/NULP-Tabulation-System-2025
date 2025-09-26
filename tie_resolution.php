<?php
require_once 'includes/bootstrap.php';
auth_require_login();
auth_require_role(['admin', 'organizer']);

$page_title = 'Tie Resolution';

include 'includes/head.php';
?>

<div class="max-w-6xl mx-auto p-6">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Tie Resolution</h1>
            <p class="text-gray-600">Resolve scoring ties and conflicts</p>
        </div>
        <a href="dashboard.php" class="text-blue-600 hover:text-blue-800">← Back to Dashboard</a>
    </div>
    
    <!-- TODO: Implement tie resolution system -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold mb-4">Tie Resolution System</h2>
        <div class="border-l-4 border-red-400 bg-red-50 p-4">
            <h3 class="text-sm font-medium text-red-800">TODO: Implement Tie Resolution Features</h3>
            <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                <li>Automatic tie detection</li>
                <li>Configurable tie-breaking rules</li>
                <li>Manual tie resolution interface</li>
                <li>Tie-breaker criteria prioritization</li>
                <li>Historical tie resolution tracking</li>
                <li>Integration with advancement logic</li>
                <li>Special round for tie-breaking if needed</li>
            </ul>
        </div>
        
        <div class="mt-6 p-4 bg-gray-50 rounded-lg">
            <h3 class="font-medium text-gray-900 mb-2">Common Tie-Breaking Methods:</h3>
            <ul class="text-sm text-gray-700 space-y-1">
                <li>• Highest individual criterion score</li>
                <li>• Judge panel vote</li>
                <li>• Additional round of judging</li>
                <li>• Pre-defined criterion priority</li>
                <li>• Manual organizer decision</li>
            </ul>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>