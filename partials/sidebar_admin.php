<?php
/** sidebar_admin.php : Professional Admin Sidebar Navigation */
// Determine if we're in a subdirectory
$isInAdminDir = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
$prefix = $isInAdminDir ? '' : 'admin/';

$navItems = [
    [
        'label' => 'Dashboard',
        'href' => $prefix . 'dashboard.php',
        'icon' => 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z M8 5a2 2 0 012-2h2a2 2 0 012 2v0H8v0z'
    ],
    [
        'label' => 'Participants',
        'href' => $prefix . 'participants.php',
        'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z'
    ],
    [
        'label' => 'Judges',
        'href' => $prefix . 'judges.php',
        'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'
    ],
    [
        'label' => 'Rounds & Criteria',
        'href' => $prefix . 'rounds.php',
        'icon' => 'M9 5H7a2 2 0 00-2 2v4a2 2 0 002 2h2m2-6h6a2 2 0 012 2v4a2 2 0 01-2 2h-6m2-6V4a2 2 0 00-2-2H9a2 2 0 00-2 2v1m2 0h4'
    ],
    [
        'label' => 'Live Control',
        'href' => $prefix . 'live_control.php',
        'icon' => 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z'
    ],
    [
        'label' => 'Leaderboard',
        'href' => $prefix . 'leaderboard.php',
        'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'
    ],
    [
        'label' => 'Advancement',
        'href' => $prefix . 'advancement.php',
        'icon' => 'M13 10V3L4 14h7v7l9-11h-7z'
    ],
    [
        'label' => 'Awards',
        'href' => $prefix . 'awards.php',
        'icon' => 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z'
    ],
    [
        'label' => 'Tie Resolution',
        'href' => $prefix . 'tie_resolution.php',
        'icon' => 'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zM21 5a2 2 0 00-2-2h-4a2 2 0 00-2 2v12a4 4 0 004 4 4 4 0 004-4V5z'
    ],
    [
        'label' => 'Settings',
        'href' => $prefix . 'settings.php',
        'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z'
    ]
];

$current = basename($_SERVER['PHP_SELF']);
?>

<!-- Sidebar Layout Container -->
<div class="flex h-screen bg-slate-100">
  <!-- Sidebar -->
  <div class="hidden md:flex md:w-64 md:flex-col">
    <div class="flex flex-col flex-grow pt-5 overflow-y-auto bg-white border-r border-slate-200">
      <!-- Logo/Brand Section -->
      <div class="flex items-center flex-shrink-0 px-6 pb-4">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
            </svg>
          </div>
          <div class="ml-3">
            <h1 class="text-lg font-semibold text-slate-800">NULP Tabulation</h1>
            <p class="text-xs text-slate-500">Admin Panel</p>
          </div>
        </div>
      </div>

      <!-- Navigation -->
      <nav class="mt-2 flex-1 px-3 space-y-1">
        <?php foreach ($navItems as $item): 
          $active = ($item['href'] === $current || str_ends_with($item['href'], $current)); 
        ?>
          <a href="<?= $item['href'] ?>" 
             class="<?= $active ? 'bg-blue-50 border-r-4 border-blue-600 text-blue-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?> group flex items-center px-3 py-2 text-sm font-medium rounded-l-lg transition-colors duration-150 ease-in-out">
            <svg class="<?= $active ? 'text-blue-500' : 'text-slate-400 group-hover:text-slate-500' ?> mr-3 flex-shrink-0 h-5 w-5 transition-colors duration-150 ease-in-out" 
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $item['icon'] ?>"/>
            </svg>
            <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
          </a>
        <?php endforeach; ?>
      </nav>

      <!-- User Profile Section -->
      <div class="flex-shrink-0 border-t border-slate-200 p-4">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
              <span class="text-sm font-medium text-blue-600">
                <?= strtoupper(substr($_SESSION['adminFN'] ?? 'A', 0, 1)) ?>
              </span>
            </div>
          </div>
          <div class="ml-3 flex-1">
            <p class="text-sm font-medium text-slate-700">
              <?= htmlspecialchars($_SESSION['adminFN'] ?? 'Admin', ENT_QUOTES, 'UTF-8') ?>
            </p>
            <form method="post" action="<?= $isInAdminDir ? '../logout.php' : 'logout.php' ?>" class="inline" id="logoutForm">
              <button type="button" class="text-xs text-slate-500 hover:text-slate-700 transition-colors duration-150" onclick="confirmLogout()">
                Sign out
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Mobile menu button -->
  <div class="md:hidden">
    <button type="button" class="fixed top-4 left-4 z-50 inline-flex items-center justify-center p-2 rounded-md text-slate-400 hover:text-slate-500 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500" id="mobile-menu-button">
      <span class="sr-only">Open main menu</span>
      <svg class="block h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
      </svg>
    </button>
  </div>

  <!-- Mobile Sidebar Overlay -->
  <div class="fixed inset-0 flex z-40 md:hidden hidden" id="mobile-sidebar">
    <div class="fixed inset-0 bg-slate-600 bg-opacity-75" id="mobile-overlay"></div>
    <div class="relative flex-1 flex flex-col max-w-xs w-full bg-white">
      <div class="absolute top-0 right-0 -mr-12 pt-2">
        <button type="button" class="ml-1 flex items-center justify-center h-10 w-10 rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white" id="mobile-close-button">
          <span class="sr-only">Close sidebar</span>
          <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
      
      <!-- Mobile sidebar content (duplicate of desktop) -->
      <div class="flex-1 h-0 pt-5 pb-4 overflow-y-auto">
        <div class="flex-shrink-0 flex items-center px-4">
          <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
          </svg>
          <h1 class="ml-3 text-lg font-semibold text-slate-800">NULP Tabulation</h1>
        </div>
        <nav class="mt-5 px-2 space-y-1">
          <?php foreach ($navItems as $item): 
            $active = ($item['href'] === $current || str_ends_with($item['href'], $current)); 
          ?>
            <a href="<?= $item['href'] ?>" 
               class="<?= $active ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?> group flex items-center px-2 py-2 text-base font-medium rounded-md">
              <svg class="<?= $active ? 'text-blue-500' : 'text-slate-400 group-hover:text-slate-500' ?> mr-4 flex-shrink-0 h-6 w-6" 
                   fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $item['icon'] ?>"/>
              </svg>
              <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
            </a>
          <?php endforeach; ?>
        </nav>
      </div>
      
      <!-- Mobile user section -->
      <div class="flex-shrink-0 flex border-t border-slate-200 p-4">
        <div class="flex items-center">
          <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
            <span class="text-sm font-medium text-blue-600">
              <?= strtoupper(substr($_SESSION['adminFN'] ?? 'A', 0, 1)) ?>
            </span>
          </div>
          <div class="ml-3">
            <p class="text-base font-medium text-slate-700">
              <?= htmlspecialchars($_SESSION['adminFN'] ?? 'Admin', ENT_QUOTES, 'UTF-8') ?>
            </p>
            <button type="button" class="text-sm text-slate-500 hover:text-slate-700" onclick="confirmLogout()">
              Sign out
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Main Content Area (to be wrapped around page content) -->
  <div class="flex flex-col flex-1 overflow-hidden">
    <!-- Top bar for mobile -->
    <div class="md:hidden bg-white shadow-sm border-b border-slate-200 px-4 py-3">
      <h1 class="text-lg font-semibold text-slate-800">NULP Tabulation</h1>
    </div>
    
    <!-- Page content will go here -->
    <main class="flex-1 relative overflow-y-auto focus:outline-none bg-slate-50">

<script>
// Mobile menu functionality
document.addEventListener('DOMContentLoaded', function() {
  const mobileMenuButton = document.getElementById('mobile-menu-button');
  const mobileSidebar = document.getElementById('mobile-sidebar');
  const mobileOverlay = document.getElementById('mobile-overlay');
  const mobileCloseButton = document.getElementById('mobile-close-button');

  function openMobileMenu() {
    mobileSidebar.classList.remove('hidden');
  }

  function closeMobileMenu() {
    mobileSidebar.classList.add('hidden');
  }

  if (mobileMenuButton) {
    mobileMenuButton.addEventListener('click', openMobileMenu);
  }
  
  if (mobileCloseButton) {
    mobileCloseButton.addEventListener('click', closeMobileMenu);
  }
  
  if (mobileOverlay) {
    mobileOverlay.addEventListener('click', closeMobileMenu);
  }
});

// Logout confirmation
function confirmLogout() {
  if (typeof showConfirm === 'function') {
    showConfirm('Confirm Logout', 'Are you sure you want to logout?', 'Yes, Logout', 'Cancel')
    .then((result) => {
      if (result.isConfirmed) {
        document.getElementById('logoutForm').submit();
      }
    });
  } else {
    // Fallback to native confirm if SweetAlert2 isn't loaded yet
    if (confirm('Are you sure you want to logout?')) {
      document.getElementById('logoutForm').submit();
    }
  }
}
</script>