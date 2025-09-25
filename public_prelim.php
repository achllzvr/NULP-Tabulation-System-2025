<?php
/**
 * Public Preliminary Results
 * Converted from: components/public/PublicPrelim.tsx
 * Preserves exact Tailwind classes and layout structure
 */

$code = $_GET['code'] ?? 'DEMO2025';

// Mock preliminary results for public display
$preliminaryResults = [
    // Mr Division
    ['rank' => 1, 'division' => 'Mr', 'number_label' => '01', 'display_name' => 'Alexander Johnson', 'score' => 87.5],
    ['rank' => 2, 'division' => 'Mr', 'number_label' => '03', 'display_name' => 'Marcus Thompson', 'score' => 85.1],
    // Ms Division
    ['rank' => 1, 'division' => 'Ms', 'number_label' => '02', 'display_name' => 'Isabella Rodriguez', 'score' => 89.2],
    ['rank' => 2, 'division' => 'Ms', 'number_label' => '04', 'display_name' => 'Sophia Chen', 'score' => 86.7]
];

$mrResults = array_filter($preliminaryResults, fn($r) => $r['division'] === 'Mr');
$msResults = array_filter($preliminaryResults, fn($r) => $r['division'] === 'Ms');

require_once 'classes/Util.php';
$pageTitle = 'Preliminary Standings - ' . $code;
include 'partials/head.php';
?>

<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
    <!-- Header -->
    <header class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <!-- Eye SVG Icon -->
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Preliminary Standings</h1>
                        <p class="text-gray-600">Pageant Code: <?= Util::escape($code) ?></p>
                    </div>
                </div>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    LIVE RESULTS
                </span>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-6 py-8">
        <div class="grid lg:grid-cols-2 gap-8">
            <!-- Mr Division -->
            <div class="bg-white shadow rounded-lg border-2 border-blue-200">
                <div class="px-6 py-4 bg-blue-50 border-b border-blue-200">
                    <h2 class="text-xl font-semibold text-blue-700 flex items-center gap-2">
                        <!-- Crown SVG Icon -->
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3l4 6 4-7 4 7 4-6v18H5V3z"/>
                        </svg>
                        Mr Division
                    </h2>
                </div>
                <div class="overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-20">
                                    Rank
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Contestant
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Score
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($mrResults as $result): ?>
                                <tr class="<?= $result['rank'] === 1 ? 'bg-yellow-50' : ($result['rank'] === 2 ? 'bg-gray-50' : '') ?>">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $result['rank'] === 1 ? 'bg-yellow-500 text-white' : 'bg-gray-100 text-gray-800' ?>">
                                                #<?= $result['rank'] ?>
                                            </span>
                                            <?php if ($result['rank'] <= 2): ?>
                                                <!-- Trophy SVG Icon -->
                                                <svg class="w-4 h-4 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                                </svg>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div>
                                            <p class="text-lg font-semibold text-gray-900">#<?= Util::escape($result['number_label']) ?></p>
                                            <p class="text-gray-600"><?= Util::escape($result['display_name']) ?></p>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <span class="text-xl font-bold text-gray-900"><?= number_format($result['score'], 1) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Ms Division -->
            <div class="bg-white shadow rounded-lg border-2 border-pink-200">
                <div class="px-6 py-4 bg-pink-50 border-b border-pink-200">
                    <h2 class="text-xl font-semibold text-pink-700 flex items-center gap-2">
                        <!-- Crown SVG Icon -->
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3l4 6 4-7 4 7 4-6v18H5V3z"/>
                        </svg>
                        Ms Division
                    </h2>
                </div>
                <div class="overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-20">
                                    Rank
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Contestant
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Score
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($msResults as $result): ?>
                                <tr class="<?= $result['rank'] === 1 ? 'bg-yellow-50' : ($result['rank'] === 2 ? 'bg-gray-50' : '') ?>">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $result['rank'] === 1 ? 'bg-yellow-500 text-white' : 'bg-gray-100 text-gray-800' ?>">
                                                #<?= $result['rank'] ?>
                                            </span>
                                            <?php if ($result['rank'] <= 2): ?>
                                                <!-- Trophy SVG Icon -->
                                                <svg class="w-4 h-4 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                                </svg>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div>
                                            <p class="text-lg font-semibold text-gray-900">#<?= Util::escape($result['number_label']) ?></p>
                                            <p class="text-gray-600"><?= Util::escape($result['display_name']) ?></p>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <span class="text-xl font-bold text-gray-900"><?= number_format($result['score'], 1) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Status -->
        <div class="mt-8 bg-white shadow rounded-lg">
            <div class="p-6 text-center">
                <h3 class="text-xl font-semibold mb-2">Preliminary Round Complete</h3>
                <p class="text-gray-600">
                    Top 2 contestants from each division have qualified for the final round.
                </p>
                <p class="text-sm text-gray-500 mt-2">
                    Final round results will be announced upon completion.
                </p>
            </div>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>