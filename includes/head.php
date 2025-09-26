<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'NULP Tabulation System') ?></title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Custom styles -->
    <style>
        .btn-primary {
            @apply bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors;
        }
        .btn-secondary {
            @apply bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2 px-4 rounded-lg transition-colors;
        }
        .btn-success {
            @apply bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg transition-colors;
        }
        .btn-danger {
            @apply bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-lg transition-colors;
        }
        .card {
            @apply bg-white rounded-lg shadow-sm border border-gray-200 p-6;
        }
        .form-input {
            @apply w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500;
        }
        .table {
            @apply w-full border-collapse bg-white rounded-lg overflow-hidden shadow-sm;
        }
        .table th {
            @apply bg-gray-50 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider;
        }
        .table td {
            @apply px-6 py-4 whitespace-nowrap text-sm text-gray-900 border-b border-gray-200;
        }
    </style>
</head>
<body class="bg-slate-50 min-h-screen"><?php
$current_user = get_current_app_user();
$current_pageant = get_current_app_pageant();
?>