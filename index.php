<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start the session
session_start();

$pageTitle = 'Landing | Pageant Tabulation System';
include __DIR__ . '/partials/head.php';
?>
<main class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 py-12 px-4">
  <div class="mx-auto max-w-6xl">
    <!-- Header Section -->
    <div class="text-center mb-16">
      <div class="inline-flex items-center gap-3 mb-6">
        <div class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center">
          <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
            <path d="M5 16L3 14l5.5-5.5L10 10l4-4h3l2 2v3l-4 4 1.5 1.5L11 22l-2-2h-3l-1-1z"/>
          </svg>
        </div>
        <h1 class="text-4xl font-bold text-slate-800">Pageant Tabulation System</h1>
      </div>
      <p class="text-lg text-slate-600 max-w-3xl mx-auto leading-relaxed">
        Professional scoring and management system for beauty pageants, talent competitions, and 
        similar events requiring real-time judging and public displays.
      </p>
    </div>

    <!-- Portal Cards -->
    <div class="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto">
      <!-- Admin Portal -->
      <a href="login_admin.php" class="group block bg-white rounded-2xl p-8 shadow-lg hover:shadow-xl transition-all duration-300 border border-slate-200 hover:border-blue-300">
        <div class="text-center">
          <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-6 group-hover:bg-blue-200 transition-colors">
            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.031 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
          </div>
          <h2 class="text-xl font-semibold text-slate-800 mb-3">Admin Portal</h2>
          <p class="text-slate-600 leading-relaxed">Manage participants, judges, rounds, and control the entire pageant flow</p>
          <div class="mt-6">
            <span class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-medium rounded-lg group-hover:bg-blue-700 transition-colors">
              Access Admin Dashboard
            </span>
          </div>
        </div>
      </a>

      <!-- Judge Portal -->
      <a href="login_judge.php" class="group block bg-white rounded-2xl p-8 shadow-lg hover:shadow-xl transition-all duration-300 border border-slate-200 hover:border-orange-300">
        <div class="text-center">
          <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-6 group-hover:bg-orange-200 transition-colors">
            <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
          </div>
          <h2 class="text-xl font-semibold text-slate-800 mb-3">Judge Portal</h2>
          <p class="text-slate-600 leading-relaxed">Submit scores for active rounds and view your judging history</p>
          <div class="mt-6">
            <span class="inline-flex items-center px-6 py-3 bg-orange-600 text-white font-medium rounded-lg group-hover:bg-orange-700 transition-colors">
              Judge Login
            </span>
          </div>
        </div>
      </a>

      <!-- Public Results -->
      <a href="public/public_select.php" class="group block bg-white rounded-2xl p-8 shadow-lg hover:shadow-xl transition-all duration-300 border border-slate-200 hover:border-green-300">
        <div class="text-center">
          <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6 group-hover:bg-green-200 transition-colors">
            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
          </div>
          <h2 class="text-xl font-semibold text-slate-800 mb-3">Public Results</h2>
          <p class="text-slate-600 leading-relaxed">View live leaderboards, final results, and award announcements</p>
          <div class="mt-6">
            <span class="inline-flex items-center px-6 py-3 bg-green-600 text-white font-medium rounded-lg group-hover:bg-green-700 transition-colors">
              View Public Display
            </span>
          </div>
        </div>
      </a>
    </div>
  </div>
</main>
<?php include __DIR__ . '/partials/footer.php';
