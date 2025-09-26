<?php
/** head.php : Shared <head> section and opening <body>. Expects $pageTitle */
if (!isset($pageTitle)) { $pageTitle = 'Pageant Tabulation System'; }
?><!DOCTYPE html>
<html lang="en" class="h-full bg-slate-100">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script>window.APP_API_BASE='api.php';</script>
<?php include __DIR__ . '/../components/loading.php'; ?>
</head>
<body class="min-h-full font-[Inter] text-slate-800">
<div id="app" class="min-h-full flex flex-col">
