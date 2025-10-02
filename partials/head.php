<?php
/** head.php : Shared <head> section and opening <body>. Expects $pageTitle */
if (!isset($pageTitle)) { $pageTitle = 'Pageant Tabulation System'; }
?><!DOCTYPE html>
<html lang="en" class="h-full">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
<?php
  // Compute asset prefix so favicon path works from admin/, judge/, public/, or root
  $__phpSelf = $_SERVER['PHP_SELF'] ?? '';
  $__prefix = (strpos($__phpSelf, '/admin/') !== false || strpos($__phpSelf, '/judge/') !== false || strpos($__phpSelf, '/public/') !== false) ? '../' : '';
?>
<link rel="icon" type="image/png" href="<?= $__prefix ?>assets/media/NULP.png" />
<link rel="shortcut icon" href="<?= $__prefix ?>assets/media/NULP.png" />
<link rel="apple-touch-icon" href="<?= $__prefix ?>assets/media/NULP.png" />
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
  .custom-blue-gradient {
    background: linear-gradient(135deg, rgba(53, 64, 142, 1) 0%, rgba(26, 32, 73, 1) 100%);
  }
  body {
    background: linear-gradient(135deg, rgba(53, 64, 142, 1) 0%, rgba(26, 32, 73, 1) 100%);
  }
  /* Glass button utilities (plain CSS for use without Tailwind build) */
  .btn-glass { color: #fff; border-radius: 0.5rem; border-width: 1px; border-style: solid; backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px); transition: background-color .15s ease, border-color .15s ease, transform .12s ease; }
  .btn-glass-primary { background-color: rgba(59,130,246,0.30); border-color: rgba(147,197,253,0.50); }
  .btn-glass-primary:hover { background-color: rgba(59,130,246,0.40); border-color: rgba(147,197,253,0.70); }
  .btn-glass-success { background-color: rgba(34,197,94,0.30); border-color: rgba(134,239,172,0.50); }
  .btn-glass-success:hover { background-color: rgba(34,197,94,0.40); border-color: rgba(134,239,172,0.70); }
  .btn-glass-danger { background-color: rgba(239,68,68,0.30); border-color: rgba(252,165,165,0.50); }
  .btn-glass-danger:hover { background-color: rgba(239,68,68,0.40); border-color: rgba(252,165,165,0.70); }
  .btn-glass-ghost { background-color: rgba(255,255,255,0.10); border-color: rgba(255,255,255,0.20); }
  .btn-glass-ghost:hover { background-color: rgba(255,255,255,0.20); }
  /* Modal animations */
  .modal-enter { opacity: 0; transform: translateY(6px) scale(0.98); }
  .modal-enter-active { transition: opacity .15s ease, transform .15s ease; opacity: 1; transform: translateY(0) scale(1); }
  .modal-exit { opacity: 1; transform: translateY(0) scale(1); }
  .modal-exit-active { transition: opacity .12s ease, transform .12s ease; opacity: 0; transform: translateY(4px) scale(0.98); }
</style>
<script>
window.APP_DEBUG = <?= (getenv('APP_DEBUG') === 'true' || ini_get('display_errors')) ? 'true' : 'false' ?>;
</script>
<?php include __DIR__ . '/../components/loading.php'; ?>
</head>
<body class="min-h-full font-[Inter] text-slate-200">
<div id="toast-root" class="fixed top-4 right-4 z-50 space-y-2"></div>
<div id="app" class="min-h-full flex flex-col">
