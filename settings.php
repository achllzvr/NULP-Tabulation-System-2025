<?php
require __DIR__ . '/includes/bootstrap.php';
auth_require_login();

if (!auth_is_admin()) {
    set_flash_message('error', 'Access denied. Admin privileges required.');
    redirect('/dashboard.php');
}

$pageTitle = 'Settings';
include __DIR__ . '/includes/head.php';
?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="md:flex md:items-center md:justify-between">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold text-gray-900 sm:text-3xl sm:truncate">
                System Settings
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                Configure pageant settings and system preferences
            </p>
        </div>
    </div>

    <!-- Pageant Settings -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Pageant Configuration</h3>
            
            <form class="space-y-6">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label for="pageant_name" class="block text-sm font-medium text-gray-700">
                            Pageant Name
                        </label>
                        <input type="text" id="pageant_name" name="pageant_name" 
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
                               placeholder="Enter pageant name">
                    </div>
                    
                    <div>
                        <label for="pageant_year" class="block text-sm font-medium text-gray-700">
                            Year
                        </label>
                        <input type="number" id="pageant_year" name="pageant_year" 
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
                               value="<?php echo date('Y'); ?>">
                    </div>
                </div>
                
                <div>
                    <label for="pageant_description" class="block text-sm font-medium text-gray-700">
                        Description
                    </label>
                    <textarea id="pageant_description" name="pageant_description" rows="3" 
                              class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
                              placeholder="Brief description of the pageant"></textarea>
                </div>
                
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label for="event_date" class="block text-sm font-medium text-gray-700">
                            Event Date
                        </label>
                        <input type="date" id="event_date" name="event_date" 
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    
                    <div>
                        <label for="event_venue" class="block text-sm font-medium text-gray-700">
                            Venue
                        </label>
                        <input type="text" id="event_venue" name="event_venue" 
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
                               placeholder="Event venue">
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Scoring Settings -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Scoring Configuration</h3>
            
            <form class="space-y-6">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label for="min_score" class="block text-sm font-medium text-gray-700">
                            Minimum Score
                        </label>
                        <input type="number" id="min_score" name="min_score" min="1" max="10" value="1"
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    
                    <div>
                        <label for="max_score" class="block text-sm font-medium text-gray-700">
                            Maximum Score
                        </label>
                        <input type="number" id="max_score" name="max_score" min="5" max="100" value="10"
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">
                        Score Decimal Places
                    </label>
                    <div class="space-y-2">
                        <label class="inline-flex items-center">
                            <input type="radio" name="decimal_places" value="0" class="form-radio text-blue-600">
                            <span class="ml-2 text-sm text-gray-700">Whole numbers (10, 9, 8...)</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" name="decimal_places" value="1" class="form-radio text-blue-600" checked>
                            <span class="ml-2 text-sm text-gray-700">One decimal (9.5, 8.7...)</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" name="decimal_places" value="2" class="form-radio text-blue-600">
                            <span class="ml-2 text-sm text-gray-700">Two decimals (9.25, 8.75...)</span>
                        </label>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Public Display Settings -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Public Display</h3>
            
            <form class="space-y-6">
                <div class="space-y-4">
                    <div class="flex items-center">
                        <input type="checkbox" id="show_prelim" name="show_prelim" 
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="show_prelim" class="ml-2 block text-sm text-gray-900">
                            Show preliminary results to public
                        </label>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" id="show_final" name="show_final" 
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="show_final" class="ml-2 block text-sm text-gray-900">
                            Show final results to public
                        </label>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" id="show_awards" name="show_awards" 
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="show_awards" class="ml-2 block text-sm text-gray-900">
                            Show awards to public
                        </label>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- System Preferences -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">System Preferences</h3>
            
            <form class="space-y-6">
                <div>
                    <label for="timezone" class="block text-sm font-medium text-gray-700">
                        Timezone
                    </label>
                    <select id="timezone" name="timezone" 
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="Asia/Manila" selected>Asia/Manila (GMT+8)</option>
                        <option value="UTC">UTC (GMT+0)</option>
                        <!-- Add more timezones as needed -->
                    </select>
                </div>
                
                <div>
                    <label for="backup_frequency" class="block text-sm font-medium text-gray-700">
                        Backup Frequency
                    </label>
                    <select id="backup_frequency" name="backup_frequency" 
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="daily">Daily</option>
                        <option value="weekly" selected>Weekly</option>
                        <option value="monthly">Monthly</option>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <!-- Save Button -->
    <div class="flex justify-end">
        <button type="button" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            Save Settings
        </button>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>