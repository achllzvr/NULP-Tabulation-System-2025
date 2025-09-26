<?php
require_once 'includes/bootstrap.php';
auth_require_login();

$page_title = 'Live Control';
$current_pageant = pageant_get_current();

include 'includes/head.php';
?>

<div class="max-w-6xl mx-auto p-6">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Live Control</h1>
            <p class="text-gray-600">Real-time pageant control and monitoring</p>
        </div>
        <a href="dashboard.php" class="text-blue-600 hover:text-blue-800">← Back to Dashboard</a>
    </div>
    
    <!-- TODO: Implement live control interface -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Round Control</h2>
            <div class="space-y-4">
                <div class="border-l-4 border-blue-400 bg-blue-50 p-4">
                    <h3 class="text-sm font-medium text-blue-800">TODO: Round Controls</h3>
                    <ul class="mt-2 text-sm text-blue-700 list-disc list-inside">
                        <li>Open/close rounds in real-time</li>
                        <li>Monitor scoring progress</li>
                        <li>Send notifications to judges</li>
                        <li>Control public display screens</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Scoring Monitor</h2>
            <div class="space-y-4">
                <div class="border-l-4 border-green-400 bg-green-50 p-4">
                    <h3 class="text-sm font-medium text-green-800">TODO: Scoring Dashboard</h3>
                    <ul class="mt-2 text-sm text-green-700 list-disc list-inside">
                        <li>Real-time scoring updates</li>
                        <li>Judge completion status</li>
                        <li>Score validation alerts</li>
                        <li>Auto-calculate rankings</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Participant Management</h2>
            <div class="space-y-4">
                <div class="border-l-4 border-purple-400 bg-purple-50 p-4">
                    <h3 class="text-sm font-medium text-purple-800">TODO: Live Participant Controls</h3>
                    <ul class="mt-2 text-sm text-purple-700 list-disc list-inside">
                        <li>Mark participants as present/absent</li>
                        <li>Handle last-minute changes</li>
                        <li>Manage contestant order</li>
                        <li>Emergency procedures</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Communication</h2>
            <div class="space-y-4">
                <div class="border-l-4 border-yellow-400 bg-yellow-50 p-4">
                    <h3 class="text-sm font-medium text-yellow-800">TODO: Communication Tools</h3>
                    <ul class="mt-2 text-sm text-yellow-700 list-disc list-inside">
                        <li>Broadcast messages to judges</li>
                        <li>Send instructions or updates</li>
                        <li>Emergency notifications</li>
                        <li>Technical support alerts</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>