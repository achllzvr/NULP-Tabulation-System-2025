<?php
/**
 * Partial: Judge Navigation
 * Expected vars: $currentUser, $activeRound
 */

require_once 'classes/Util.php';

$currentUser = $currentUser ?? null;
$activeRound = $activeRound ?? null;
?>

<!-- Header -->
<header class="bg-white border-b border-gray-200">
    <div class="px-6 py-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="flex items-center space-x-2">
                    <!-- User SVG Icon -->
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <h1 class="text-2xl font-bold text-gray-900">Judge Portal</h1>
                </div>
                <?php if ($activeRound): ?>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">
                        <?= Util::escape($activeRound['name'] ?? 'No Active Round') ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="flex items-center space-x-4">
                <span class="text-gray-600">Welcome, <?= Util::escape($currentUser['full_name'] ?? 'Judge') ?></span>
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