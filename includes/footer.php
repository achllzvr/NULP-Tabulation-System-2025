    <footer class="bg-white border-t border-gray-200 mt-auto">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center">
                <div class="text-sm text-gray-600">
                    © <?= date('Y') ?> NULP Tabulation System
                </div>
                <div class="text-xs text-gray-500">
                    <?php if ($current_pageant): ?>
                    Current Pageant: <?= esc($current_pageant['name'] ?? 'Unknown') ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </footer>

    <!-- Basic JavaScript utilities -->
    <script>
        // Simple confirmation dialogs
        function confirmAction(message) {
            return confirm(message || 'Are you sure?');
        }
        
        // Auto-hide flash messages
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    alert.style.opacity = '0';
                    setTimeout(function() {
                        alert.remove();
                    }, 300);
                }, 5000);
            });
        });
        
        // Form validation helper
        function validateRequired(formSelector) {
            const form = document.querySelector(formSelector);
            if (!form) return true;
            
            const required = form.querySelectorAll('[required]');
            let valid = true;
            
            required.forEach(function(field) {
                if (!field.value.trim()) {
                    field.classList.add('border-red-500');
                    valid = false;
                } else {
                    field.classList.remove('border-red-500');
                }
            });
            
            return valid;
        }
    </script>
</body>
</html>