/**
 * Modal Management
 * Provides functions for showing/hiding modals without React
 */

/**
 * Show modal by ID
 */
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }
}

/**
 * Hide modal by ID
 */
function hideModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = 'auto';
    }
}

/**
 * Setup modal close on backdrop click
 */
function setupModalBackdropClose(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                hideModal(modalId);
            }
        });
    }
}

/**
 * Setup modal close on ESC key
 */
function setupModalEscapeClose(modalId) {
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            hideModal(modalId);
        }
    });
}

/**
 * Initialize all modals on page load
 */
document.addEventListener('DOMContentLoaded', function() {
    // Find all modal elements and setup close handlers
    const modals = document.querySelectorAll('[id$="Modal"]');
    modals.forEach(modal => {
        setupModalBackdropClose(modal.id);
        setupModalEscapeClose(modal.id);
    });
});

/**
 * Confirmation modal
 */
function showConfirmModal(title, message, onConfirm, onCancel = null) {
    // Create modal HTML
    const modalHtml = `
        <div id="confirmModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                <div class="mb-4">
                    <h3 class="text-lg font-medium text-gray-900">${title}</h3>
                    <p class="text-sm text-gray-600 mt-2">${message}</p>
                </div>
                <div class="flex gap-2 justify-end">
                    <button id="confirmCancel" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button id="confirmOk" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                        Confirm
                    </button>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing confirm modal
    const existing = document.getElementById('confirmModal');
    if (existing) {
        existing.remove();
    }
    
    // Add to body
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Setup event listeners
    document.getElementById('confirmOk').addEventListener('click', function() {
        hideModal('confirmModal');
        document.getElementById('confirmModal').remove();
        if (onConfirm) onConfirm();
    });
    
    document.getElementById('confirmCancel').addEventListener('click', function() {
        hideModal('confirmModal');
        document.getElementById('confirmModal').remove();
        if (onCancel) onCancel();
    });
    
    // Show modal
    showModal('confirmModal');
}