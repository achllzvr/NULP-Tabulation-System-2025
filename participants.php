<?php
require_once 'includes/bootstrap.php';
auth_require_login();
auth_require_role(['admin', 'organizer']);

$page_title = 'Participants';
$participants = pageant_list_participants();

include 'includes/head.php';
?>

<div class="max-w-6xl mx-auto p-6">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Participants</h1>
            <p class="text-gray-600">Manage pageant participants</p>
        </div>
        <div class="space-x-4">
            <a href="dashboard.php" class="text-blue-600 hover:text-blue-800">← Back to Dashboard</a>
            <!-- TODO: Add participant button -->
            <button class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                Add Participant
            </button>
        </div>
    </div>
    
    <!-- Participants table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Contestant #
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Name
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Age
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Status
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($participants)): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                            No participants found. Add participants to get started.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($participants as $participant): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                #<?php echo esc($participant['contestant_number']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo esc($participant['first_name'] . ' ' . $participant['last_name']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo esc($participant['age'] ?? 'N/A'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $participant['active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <?php echo $participant['active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <!-- TODO: Add edit/delete actions -->
                                <button class="text-indigo-600 hover:text-indigo-900 mr-4">Edit</button>
                                <button class="text-red-600 hover:text-red-900">Remove</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- TODO: Add participant form modal or separate page -->
</div>

<?php include 'includes/footer.php'; ?>