<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? esc($page_title) . ' - ' : ''; ?>NULP Tabulation System</title>
    
    <!-- Tailwind CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.4.3/dist/tailwind.min.css" rel="stylesheet">
    
    <!-- Optional: Add favicon -->
    <!-- <link rel="icon" type="image/x-icon" href="/favicon.ico"> -->
    
    <!-- Custom styles can be added here if needed -->
    <style>
        /* Custom styles for the tabulation system */
        .flash-message {
            animation: slideIn 0.3s ease-out;
        }
        
        @keyframes slideIn {
            from { transform: translateY(-100%); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Flash messages -->
    <?php $flash_messages = get_flash_messages(); ?>
    <?php if (!empty($flash_messages)): ?>
        <div class="fixed top-0 left-0 right-0 z-50 p-4">
            <?php foreach ($flash_messages as $flash): ?>
                <div class="flash-message mb-2 max-w-md mx-auto rounded-lg shadow-lg p-4 <?php 
                    echo $flash['type'] === 'success' ? 'bg-green-500 text-white' : 
                         ($flash['type'] === 'error' ? 'bg-red-500 text-white' : 'bg-blue-500 text-white'); 
                ?>">
                    <?php echo esc($flash['message']); ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <script>
            // Auto-hide flash messages after 5 seconds
            setTimeout(function() {
                const flashMessages = document.querySelectorAll('.flash-message');
                flashMessages.forEach(function(message) {
                    message.style.opacity = '0';
                    message.style.transform = 'translateY(-100%)';
                    setTimeout(function() {
                        message.remove();
                    }, 300);
                });
            }, 5000);
        </script>
    <?php endif; ?>