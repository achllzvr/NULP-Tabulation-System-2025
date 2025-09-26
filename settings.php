<?php
require_once 'includes/bootstrap.php';
auth_require_login();
auth_require_role(['admin', 'organizer']);

$page_title = 'Settings';

include 'includes/head.php';
?>

<div class="max-w-6xl mx-auto p-6">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Settings</h1>
            <p class="text-gray-600">System and pageant configuration</p>
        </div>
        <a href="dashboard.php" class="text-blue-600 hover:text-blue-800">← Back to Dashboard</a>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Pageant Settings -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Pageant Settings</h2>
            <div class="border-l-4 border-blue-400 bg-blue-50 p-4">
                <h3 class="text-sm font-medium text-blue-800">TODO: Pageant Configuration</h3>
                <ul class="mt-2 text-sm text-blue-700 list-disc list-inside">
                    <li>Pageant information management</li>
                    <li>Round configuration</li>
                    <li>Criteria setup and weighting</li>
                    <li>Scoring scale settings</li>
                    <li>Advancement rules</li>
                </ul>
            </div>
        </div>
        
        <!-- User Management -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">User Management</h2>
            <div class="border-l-4 border-green-400 bg-green-50 p-4">
                <h3 class="text-sm font-medium text-green-800">TODO: User Administration</h3>
                <ul class="mt-2 text-sm text-green-700 list-disc list-inside">
                    <li>User account management</li>
                    <li>Role assignments</li>
                    <li>Judge permissions</li>
                    <li>Password management</li>
                    <li>Activity logging</li>
                </ul>
            </div>
        </div>
        
        <!-- System Settings -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">System Settings</h2>
            <div class="border-l-4 border-purple-400 bg-purple-50 p-4">
                <h3 class="text-sm font-medium text-purple-800">TODO: System Configuration</h3>
                <ul class="mt-2 text-sm text-purple-700 list-disc list-inside">
                    <li>Database configuration</li>
                    <li>Email settings</li>
                    <li>Security settings</li>
                    <li>Backup management</li>
                    <li>System maintenance</li>
                </ul>
            </div>
        </div>
        
        <!-- Display Settings -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Display Settings</h2>
            <div class="border-l-4 border-yellow-400 bg-yellow-50 p-4">
                <h3 class="text-sm font-medium text-yellow-800">TODO: Display Configuration</h3>
                <ul class="mt-2 text-sm text-yellow-700 list-disc list-inside">
                    <li>Public display customization</li>
                    <li>Branding and logos</li>
                    <li>Color themes</li>
                    <li>Live display options</li>
                    <li>Print formats</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>