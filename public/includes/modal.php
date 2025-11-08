<?php
/**
 * Reusable Modal Component
 * Displays error and success messages in a modal dialog instead of browser alerts
 * 
 * Usage:
 * 1. Include this file: require_once __DIR__ . '/../includes/modal.php';
 * 2. Call showModal() from JavaScript: showModal('Error', 'Something went wrong', 'error');
 */
?>

<style>
/* Modal overlay */
.modal-overlay {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    animation: fadeIn 0.2s ease;
}

.modal-overlay.show {
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Modal content */
.modal-content {
    background-color: white;
    border-radius: var(--radius-md);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    max-width: 500px;
    width: 90%;
    animation: slideIn 0.3s ease;
}

/* Modal header */
.modal-header {
    padding: var(--spacing-lg) var(--spacing-xl);
    border-bottom: 1px solid var(--gray-200);
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
}

.modal-header.error {
    border-bottom-color: var(--danger-color);
}

.modal-header.success {
    border-bottom-color: var(--success-color);
}

.modal-header.warning {
    border-bottom-color: var(--warning-color);
}

.modal-icon {
    font-size: 24px;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.modal-icon.error {
    background-color: var(--danger-bg);
    color: var(--danger-color);
}

.modal-icon.success {
    background-color: var(--success-bg);
    color: var(--success-color);
}

.modal-icon.warning {
    background-color: var(--warning-bg);
    color: var(--warning-color);
}

.modal-title {
    font-size: var(--font-size-lg);
    font-weight: 600;
    color: var(--gray-900);
    margin: 0;
}

/* Modal body */
.modal-body {
    padding: var(--spacing-xl);
    color: var(--gray-700);
    line-height: 1.6;
}

/* Modal footer */
.modal-footer {
    padding: var(--spacing-lg) var(--spacing-xl);
    border-top: 1px solid var(--gray-200);
    display: flex;
    justify-content: flex-end;
    gap: var(--spacing-md);
}

.modal-button {
    padding: var(--spacing-sm) var(--spacing-lg);
    border: none;
    border-radius: var(--radius-sm);
    font-weight: 500;
    cursor: pointer;
    transition: all var(--transition-fast);
}

.modal-button.primary {
    background-color: var(--primary-color);
    color: white;
}

.modal-button.primary:hover {
    background-color: var(--primary-hover);
}

.modal-button.secondary {
    background-color: var(--gray-200);
    color: var(--gray-700);
}

.modal-button.secondary:hover {
    background-color: var(--gray-300);
}

/* Animations */
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideIn {
    from { 
        transform: translateY(-20px);
        opacity: 0;
    }
    to { 
        transform: translateY(0);
        opacity: 1;
    }
}
</style>

<!-- Modal HTML Structure -->
<div id="globalModal" class="modal-overlay">
    <div class="modal-content">
        <div id="modalHeader" class="modal-header">
            <div id="modalIcon" class="modal-icon"></div>
            <h3 id="modalTitle" class="modal-title"></h3>
        </div>
        <div id="modalBody" class="modal-body"></div>
        <div class="modal-footer">
            <button type="button" class="modal-button primary" onclick="closeModal()">OK</button>
        </div>
    </div>
</div>

<script>
/**
 * Show modal dialog
 * @param {string} title - Modal title
 * @param {string} message - Modal message content
 * @param {string} type - Modal type: 'error', 'success', 'warning', 'info' (default: 'info')
 * @param {function} callback - Optional callback function to execute after closing
 */
function showModal(title, message, type = 'info', callback = null) {
    const modal = document.getElementById('globalModal');
    const modalHeader = document.getElementById('modalHeader');
    const modalIcon = document.getElementById('modalIcon');
    const modalTitle = document.getElementById('modalTitle');
    const modalBody = document.getElementById('modalBody');
    
    // Set title and message
    modalTitle.textContent = title;
    modalBody.innerHTML = message;
    
    // Reset classes
    modalHeader.className = 'modal-header';
    modalIcon.className = 'modal-icon';
    
    // Apply type-specific styling and icons
    switch(type) {
        case 'error':
            modalHeader.classList.add('error');
            modalIcon.classList.add('error');
            modalIcon.innerHTML = '✕';
            break;
        case 'success':
            modalHeader.classList.add('success');
            modalIcon.classList.add('success');
            modalIcon.innerHTML = '✓';
            break;
        case 'warning':
            modalHeader.classList.add('warning');
            modalIcon.classList.add('warning');
            modalIcon.innerHTML = '⚠';
            break;
        default: // info
            modalIcon.innerHTML = 'ℹ';
    }
    
    // Store callback if provided
    if (callback) {
        modal.dataset.callback = callback.toString();
    } else {
        delete modal.dataset.callback;
    }
    
    // Show modal
    modal.classList.add('show');
    
    // Prevent body scroll
    document.body.style.overflow = 'hidden';
}

/**
 * Close modal dialog
 */
function closeModal() {
    const modal = document.getElementById('globalModal');
    modal.classList.remove('show');
    
    // Restore body scroll
    document.body.style.overflow = '';
    
    // Execute callback if exists
    if (modal.dataset.callback) {
        try {
            const callback = new Function('return ' + modal.dataset.callback)();
            if (typeof callback === 'function') {
                callback();
            }
        } catch (e) {
            console.error('Error executing modal callback:', e);
        }
        delete modal.dataset.callback;
    }
}

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    const modal = document.getElementById('globalModal');
    if (e.target === modal) {
        closeModal();
    }
});

// Close modal on ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('globalModal');
        if (modal.classList.contains('show')) {
            closeModal();
        }
    }
});
</script>
