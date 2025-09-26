<?php
require_once __DIR__ . '/includes/bootstrap.php';

ensure_logged_in();
ensure_pageant_selected();

$current_user = get_current_user();
$current_pageant = get_current_pageant();

// Only admin can manage participants
if (($current_user['role'] ?? '') !== 'admin') {
    header('Location: /dashboard.php');
    exit;
}

$participants = $pageant->get_pageant_participants($current_pageant['id']);

$title = 'Participants - NULP Tabulation System';
include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/nav.php';
?>

<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Participants</h1>
            <p class="mt-2 text-gray-600">Manage pageant participants for <?= esc($current_pageant['name']) ?></p>
        </div>

        <!-- Participants List -->
        <div class="card">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-semibold text-gray-900">Registered Participants</h2>
                <button class="btn-primary" onclick="alert('Add participant functionality would be implemented here')">
                    Add Participant
                </button>
            </div>

            <?php if (empty($participants)): ?>
            <div class="text-center py-12">
                <div class="text-gray-500 mb-4">
                    <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No Participants Yet</h3>
                <p class="text-gray-600 mb-4">Start by adding participants to this pageant.</p>
                <button class="btn-primary" onclick="alert('Add participant functionality would be implemented here')">
                    Add First Participant
                </button>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Number</th>
                            <th>Name</th>
                            <th>Division</th>
                            <th>Advocacy</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($participants as $participant): ?>
                        <tr>
                            <td>
                                <span class="inline-flex items-center justify-center w-8 h-8 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">
                                    <?= esc($participant['number_label']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="font-medium text-gray-900"><?= esc($participant['full_name']) ?></div>
                                <?php if (!empty($participant['nickname'])): ?>
                                <div class="text-sm text-gray-500">"<?= esc($participant['nickname']) ?>"</div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    <?= esc($participant['division_name'] ?? 'N/A') ?>
                                </span>
                            </td>
                            <td>
                                <div class="max-w-xs">
                                    <?php if (!empty($participant['advocacy_short'])): ?>
                                    <div class="text-sm text-gray-900 truncate" title="<?= esc($participant['advocacy'] ?? '') ?>">
                                        <?= esc($participant['advocacy_short']) ?>
                                    </div>
                                    <?php else: ?>
                                    <span class="text-gray-400">No advocacy provided</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($participant['is_active']): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Active
                                </span>
                                <?php else: ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    Inactive
                                </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="flex space-x-2">
                                    <button class="text-blue-600 hover:text-blue-900 text-sm" onclick="alert('Edit functionality would be implemented here')">
                                        Edit
                                    </button>
                                    <button class="text-red-600 hover:text-red-900 text-sm" onclick="confirmAction('Are you sure you want to deactivate this participant?')">
                                        Deactivate
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Summary -->
            <div class="mt-6 flex justify-between items-center text-sm text-gray-600">
                <div>
                    Total participants: <?= count($participants) ?> 
                    (Active: <?= count(array_filter($participants, fn($p) => $p['is_active'])) ?>)
                </div>
                <div>
                    <?php
                    $divisions = array_count_values(array_column($participants, 'division_name'));
                    echo implode(' | ', array_map(fn($div, $count) => "$div: $count", array_keys($divisions), $divisions));
                    ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>