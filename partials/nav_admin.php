<?php
/**
 * Partial: Admin Navigation
 * Expected vars: $currentPage, $state (array with user/pageant data)
 */

// Util class removed; escaping handled by global esc() from bootstrap

$currentPage = $currentPage ?? '';
$state = $state ?? [];
$currentUser = $state['currentUser'] ?? null;
$pageantCode = $state['pageantCode'] ?? 'DEMO2025';

$navigation = [
    ['name' => 'Dashboard', 'href' => 'dashboard.php', 'icon' => 'home'],
    ['name' => 'Participants', 'href' => 'participants.php', 'icon' => 'users'],
    ['name' => 'Judges', 'href' => 'judges.php', 'icon' => 'user-check'],
    ['name' => 'Rounds & Criteria', 'href' => 'rounds.php', 'icon' => 'target'],
    ['name' => 'Live Control', 'href' => 'live_control.php', 'icon' => 'radio'],
    ['name' => 'Leaderboard', 'href' => 'leaderboard.php', 'icon' => 'trophy'],
    ['name' => 'Advancement', 'href' => 'advancement.php', 'icon' => 'trending-up'],
    ['name' => 'Final Round', 'href' => 'final_round.php', 'icon' => 'crown'],
    ['name' => 'Awards', 'href' => 'awards.php', 'icon' => 'award'],
    ['name' => 'Tie Resolution', 'href' => 'tie_resolution.php', 'icon' => 'git-merge'],
    ['name' => 'Settings', 'href' => 'settings.php', 'icon' => 'settings'],
];

// Simple progress calculation (stub)
$completedSteps = 3;
$totalSteps = 6;
$progressPercentage = ($completedSteps / $totalSteps) * 100;
?>

<!-- Header -->
<header class="bg-white border-b border-gray-200">
    <div class="px-6 py-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="flex items-center space-x-2">
                    <!-- Crown SVG Icon -->
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3l4 6 4-7 4 7 4-6v18H5V3z"/>
                    </svg>
                    <h1 class="text-2xl font-bold text-gray-900">Pageant Admin</h1>
                </div>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    <?= esc($pageantCode) ?>
                </span>
            </div>
            <div class="flex items-center space-x-4">
                <span class="text-gray-600">Welcome, <?= esc($currentUser['full_name'] ?? 'User') ?></span>
                <a href="login.php?logout=1" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <!-- LogOut SVG Icon -->
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Logout
                </a>
            </div>
        </div>
    </div>
</header>

<div class="flex">
    <!-- Sidebar -->
    <aside class="w-64 bg-white shadow-sm min-h-screen">
        <div class="p-6">
            <!-- Progress Overview -->
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-sm font-medium text-gray-900 mb-2">Setup Progress</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <span>Completion</span>
                            <span><?= $completedSteps ?>/<?= $totalSteps ?></span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full transition-all" style="width: <?= $progressPercentage ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="space-y-1">
                <?php foreach ($navigation as $item): ?>
                    <?php 
                    $isActive = ($currentPage === $item['href']);
                    $activeClass = $isActive ? 
                        'bg-blue-100 text-blue-700 border-r-2 border-blue-600' : 
                        'text-gray-600 hover:bg-gray-100 hover:text-gray-900';
                    ?>
                    <a href="<?= esc($item['href']) ?>" 
                       class="w-full flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors <?= $activeClass ?>">
                        <!-- Icon placeholder - in real app, you'd use proper SVG icons -->
                        <span class="w-4 h-4 mr-3">•</span>
                        <?= esc($item['name']) ?>
                        <?php if ($isActive): ?>
                            <svg class="w-4 h-4 ml-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>
    </aside>