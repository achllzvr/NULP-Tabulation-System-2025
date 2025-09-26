<?php
require_once __DIR__ . '/includes/bootstrap.php';

// Get pageant code from URL parameter
$pageant_code = $_GET['code'] ?? '';

if (empty($pageant_code)) {
    header('Location: /login.php');
    exit;
}

// Find pageant by code (in real implementation, this would query database)
// For now, simulate with demo data
$demo_pageant = null;
if (strtoupper($pageant_code) === 'DEMO2025') {
    $demo_pageant = [
        'id' => 1,
        'name' => 'NULP Demo Pageant 2025',
        'code' => 'DEMO2025',
        'show_participant_names' => true,
        'prelim_results_revealed' => true,
        'final_results_revealed' => false
    ];
}

if (!$demo_pageant) {
    die('Invalid pageant code');
}

$reveal_flags = $pageant->compute_reveal_flags($demo_pageant);

$title = 'Preliminary Results - ' . $demo_pageant['name'];
include __DIR__ . '/includes/head.php';
?>

<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
    <!-- Header -->
    <div class="bg-white shadow-sm">
        <div class="max-w-6xl mx-auto px-4 py-6">
            <div class="text-center">
                <h1 class="text-4xl font-bold text-gray-900 mb-2">
                    🏆 <?= esc($demo_pageant['name']) ?>
                </h1>
                <p class="text-xl text-gray-600">Preliminary Round Results</p>
                <div class="mt-2">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                        Code: <?= esc($demo_pageant['code']) ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-6xl mx-auto px-4 py-8">
        <?php if (!$reveal_flags['prelim_revealed']): ?>
        <!-- Results Not Yet Revealed -->
        <div class="text-center py-16">
            <div class="text-gray-400 mb-6">
                <svg class="w-20 h-20 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
            </div>
            <h2 class="text-3xl font-bold text-gray-900 mb-4">Results Coming Soon</h2>
            <p class="text-xl text-gray-600 mb-6">
                Preliminary round results will be revealed here once judging is complete.
            </p>
            <div class="text-gray-500">
                Please check back later or wait for the official announcement.
            </div>
        </div>
        <?php else: ?>
        <!-- Mock Preliminary Results -->
        <div class="space-y-8">
            <!-- Mr Division Results -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                    <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-lg mr-3">Mr Division</span>
                    Preliminary Results
                </h2>
                
                <div class="space-y-4">
                    <?php
                    $mr_results = [
                        ['rank' => 1, 'number' => '01', 'name' => $reveal_flags['show_names'] ? 'Alexander Johnson' : 'Contestant #01', 'score' => 87.5],
                        ['rank' => 2, 'number' => '03', 'name' => $reveal_flags['show_names'] ? 'Marcus Thompson' : 'Contestant #03', 'score' => 85.1],
                        ['rank' => 3, 'number' => '05', 'name' => $reveal_flags['show_names'] ? 'James Wilson' : 'Contestant #05', 'score' => 82.3],
                    ];
                    
                    foreach ($mr_results as $result):
                    ?>
                    <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:shadow-md transition-shadow">
                        <div class="flex items-center space-x-6">
                            <div class="flex items-center space-x-3">
                                <?php if ($result['rank'] <= 3): ?>
                                <div class="text-2xl">
                                    <?= $result['rank'] === 1 ? '🥇' : ($result['rank'] === 2 ? '🥈' : '🥉') ?>
                                </div>
                                <?php endif; ?>
                                <div class="text-2xl font-bold text-gray-700">#<?= $result['rank'] ?></div>
                            </div>
                            <div class="flex items-center space-x-4">
                                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center font-bold text-blue-800">
                                    <?= esc($result['number']) ?>
                                </div>
                                <div>
                                    <div class="font-semibold text-lg text-gray-900"><?= esc($result['name']) ?></div>
                                    <div class="text-sm text-gray-500">Mr Division</div>
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold text-blue-600"><?= number_format($result['score'], 1) ?></div>
                            <div class="text-sm text-gray-500">Total Score</div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Ms Division Results -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                    <span class="bg-pink-100 text-pink-800 px-3 py-1 rounded-full text-lg mr-3">Ms Division</span>
                    Preliminary Results
                </h2>
                
                <div class="space-y-4">
                    <?php
                    $ms_results = [
                        ['rank' => 1, 'number' => '02', 'name' => $reveal_flags['show_names'] ? 'Isabella Rodriguez' : 'Contestant #02', 'score' => 89.2],
                        ['rank' => 2, 'number' => '04', 'name' => $reveal_flags['show_names'] ? 'Sophia Chen' : 'Contestant #04', 'score' => 86.7],
                        ['rank' => 3, 'number' => '06', 'name' => $reveal_flags['show_names'] ? 'Emma Davis' : 'Contestant #06', 'score' => 84.1],
                    ];
                    
                    foreach ($ms_results as $result):
                    ?>
                    <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:shadow-md transition-shadow">
                        <div class="flex items-center space-x-6">
                            <div class="flex items-center space-x-3">
                                <?php if ($result['rank'] <= 3): ?>
                                <div class="text-2xl">
                                    <?= $result['rank'] === 1 ? '🥇' : ($result['rank'] === 2 ? '🥈' : '🥉') ?>
                                </div>
                                <?php endif; ?>
                                <div class="text-2xl font-bold text-gray-700">#<?= $result['rank'] ?></div>
                            </div>
                            <div class="flex items-center space-x-4">
                                <div class="w-12 h-12 bg-pink-100 rounded-full flex items-center justify-center font-bold text-pink-800">
                                    <?= esc($result['number']) ?>
                                </div>
                                <div>
                                    <div class="font-semibold text-lg text-gray-900"><?= esc($result['name']) ?></div>
                                    <div class="text-sm text-gray-500">Ms Division</div>
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold text-pink-600"><?= number_format($result['score'], 1) ?></div>
                            <div class="text-sm text-gray-500">Total Score</div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Advancement Notice -->
            <div class="bg-green-50 border border-green-200 rounded-lg p-6">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-lg font-medium text-green-800">Final Round Advancement</h3>
                        <p class="mt-1 text-green-700">
                            The top 2 contestants from each division will advance to the final round. 
                            Final round results and awards ceremony details will be announced separately.
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>