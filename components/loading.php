<?php
/**
 * Loading Spinner Component
 * Usage: include this file and call createLoadingSpinner($id, $message, $showStatus)
 */

function createLoadingSpinner($id = 'defaultLoader', $message = 'Loading', $showStatus = true) {
    $statusDisplay = $showStatus ? 'block' : 'none';
    
    return '
    <div id="' . $id . '" class="flex flex-col items-center justify-center py-12 space-y-4">
        <!-- Animated Spinner -->
        <div class="relative">
            <div class="w-12 h-12 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin"></div>
            <div class="absolute inset-0 w-12 h-12 border-4 border-transparent border-r-blue-400 rounded-full animate-pulse"></div>
        </div>
        
        <!-- Loading Message -->
        <div class="text-center space-y-2">
            <p class="text-slate-600 font-medium">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>
            
            <!-- Live Status (for development) -->
            <div id="' . $id . '_status" class="text-xs text-slate-400 min-h-[1rem]" style="display: ' . $statusDisplay . ';">
                Initializing...
            </div>
            
            <!-- Progress Dots -->
            <div class="flex justify-center space-x-1">
                <div class="w-2 h-2 bg-blue-600 rounded-full animate-bounce"></div>
                <div class="w-2 h-2 bg-blue-600 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                <div class="w-2 h-2 bg-blue-600 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
            </div>
        </div>
    </div>';
}

function createSimpleSpinner($id = 'simpleLoader', $size = 'w-8 h-8') {
    return '
    <div id="' . $id . '" class="flex items-center justify-center py-4">
        <div class="' . $size . ' border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin"></div>
    </div>';
}

?>

<style>
/* Enhanced animations for loading spinner */
@keyframes spin {
    to { transform: rotate(360deg); }
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

@keyframes bounce {
    0%, 100% { 
        transform: translateY(0);
        animation-timing-function: cubic-bezier(0.8, 0, 1, 1);
    }
    50% { 
        transform: translateY(-25%);
        animation-timing-function: cubic-bezier(0, 0, 0.2, 1);
    }
}

.animate-spin { animation: spin 0.8s linear infinite; }
.animate-pulse { animation: pulse 1.5s ease-in-out infinite; }
.animate-bounce { animation: bounce 1s infinite; }

/* Loading status fade in/out */
#loadingStatus {
    transition: opacity 0.3s ease-in-out;
}
</style>

<script>
// Loading status management
class LoadingManager {
    constructor(loaderId) {
        this.loaderId = loaderId;
        this.statusElement = document.getElementById(loaderId + '_status');
        this.startTime = Date.now();
        this.statusMessages = [
            'Connecting to server...',
            'Fetching data...',
            'Processing information...',
            'Almost ready...',
            'Finalizing...'
        ];
        this.currentMessageIndex = 0;
    }
    
    updateStatus(message) {
        if (this.statusElement) {
            this.statusElement.textContent = message;
        }
    }
    
    startStatusUpdates() {
        this.updateStatus(this.statusMessages[0]);
        
        this.statusInterval = setInterval(() => {
            this.currentMessageIndex = (this.currentMessageIndex + 1) % this.statusMessages.length;
            this.updateStatus(this.statusMessages[this.currentMessageIndex]);
        }, 1500);
    }
    
    setCustomStatus(message) {
        this.updateStatus(message);
    }
    
    finish(successMessage = 'Complete!') {
        if (this.statusInterval) {
            clearInterval(this.statusInterval);
        }
        
        const elapsed = ((Date.now() - this.startTime) / 1000).toFixed(1);
        this.updateStatus(`${successMessage} (${elapsed}s)`);
        
        // Fade out after showing completion
        setTimeout(() => {
            const loaderElement = document.getElementById(this.loaderId);
            if (loaderElement) {
                loaderElement.style.transition = 'opacity 0.5s ease-out';
                loaderElement.style.opacity = '0';
                setTimeout(() => {
                    if (loaderElement.parentNode) {
                        loaderElement.remove();
                    }
                }, 500);
            }
        }, 1000);
    }
    
    error(errorMessage = 'Failed to load') {
        if (this.statusInterval) {
            clearInterval(this.statusInterval);
        }
        
        const elapsed = ((Date.now() - this.startTime) / 1000).toFixed(1);
        this.updateStatus(`❌ ${errorMessage} (${elapsed}s)`);
        
        // Change spinner to error state
        const loaderElement = document.getElementById(this.loaderId);
        if (loaderElement) {
            const spinner = loaderElement.querySelector('.animate-spin');
            if (spinner) {
                spinner.classList.remove('border-t-blue-600', 'animate-spin');
                spinner.classList.add('border-t-red-600');
                spinner.innerHTML = '<div class="text-red-600 text-xl">✕</div>';
            }
        }
    }
}

// Global function to create and start a loader
function createLoader(containerId, message = 'Loading', showStatus = true) {
    const container = document.getElementById(containerId);
    if (!container) return null;
    
    const loaderId = containerId + '_loader';
    const statusDisplay = showStatus ? 'block' : 'none';
    
    container.innerHTML = `
    <div id="${loaderId}" class="flex flex-col items-center justify-center py-12 space-y-4">
        <div class="relative">
            <div class="w-12 h-12 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin"></div>
            <div class="absolute inset-0 w-12 h-12 border-4 border-transparent border-r-blue-400 rounded-full animate-pulse"></div>
        </div>
        <div class="text-center space-y-2">
            <p class="text-slate-600 font-medium">${message}</p>
            <div id="${loaderId}_status" class="text-xs text-slate-400 min-h-[1rem]" style="display: ${statusDisplay};">
                Initializing...
            </div>
            <div class="flex justify-center space-x-1">
                <div class="w-2 h-2 bg-blue-600 rounded-full animate-bounce"></div>
                <div class="w-2 h-2 bg-blue-600 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                <div class="w-2 h-2 bg-blue-600 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
            </div>
        </div>
    </div>`;
    
    const manager = new LoadingManager(loaderId);
    if (showStatus) {
        manager.startStatusUpdates();
    }
    
    return manager;
}

// Example usage functions
function showLoadingInElement(elementId, message = 'Loading...') {
    return createLoader(elementId, message, true);
}

function showSimpleLoadingInElement(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.innerHTML = `
        <div class="flex items-center justify-center py-4">
            <div class="w-8 h-8 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin"></div>
        </div>`;
    }
}

// Form loading enhancement
function makeFormLoadingEnabled(formId, buttonText = 'Processing...', showStatus = false) {
    const form = document.getElementById(formId);
    if (!form) return;
    
    const submitButton = form.querySelector('button[type="submit"]');
    if (!submitButton) return;
    
    const originalText = submitButton.textContent;
    const originalDisabled = submitButton.disabled;
    
    form.addEventListener('submit', function(e) {
        // DON'T prevent default - let the form submit naturally
        // Just show loading state immediately
        setTimeout(() => {
            // Show loading state after a brief delay to ensure form submission starts
            submitButton.disabled = true;
            submitButton.innerHTML = `
                <div class="flex items-center justify-center space-x-2">
                    <div class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                    <span>${buttonText}</span>
                </div>
            `;
            
            // Add loading status if enabled
            let statusElement = null;
            if (showStatus) {
                statusElement = document.createElement('div');
                statusElement.className = 'text-xs text-blue-600 mt-2 text-center';
                statusElement.textContent = 'Processing your request...';
                submitButton.parentNode.appendChild(statusElement);
            }
        }, 10); // Very small delay to allow form submission to start
        
        // Auto-restore after timeout (failsafe) - only if page hasn't redirected
        setTimeout(() => {
            if (document.getElementById(formId)) { // Check if still on same page
                submitButton.disabled = originalDisabled;
                submitButton.textContent = originalText;
                const statusElement = submitButton.parentNode.querySelector('.text-xs.text-blue-600');
                if (statusElement) {
                    statusElement.remove();
                }
            }
        }, 10000);
    });
}
</script>