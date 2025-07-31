/**
 * Toast Notification System
 * Inspired by shadcn/ui and built with plain JavaScript.
 */
document.addEventListener('DOMContentLoaded', function() {
    // Create and inject the toast container into the body
    if (!document.getElementById('toast-container')) {
        const container = document.createElement('div');
        container.id = 'toast-container';
        document.body.appendChild(container);
    }
});

function showToast({ type = 'info', title, message, duration = 5000 }) {
    const container = document.getElementById('toast-container');
    if (!container) {
        console.error('Toast container not found.');
        return;
    }

    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.setAttribute('data-type', type);

    // Map types to titles and icons
    const typeDetails = {
        success: { defaultTitle: 'Success', icon: '✔' },
        error: { defaultTitle: 'Error', icon: '✖' },
        warning: { defaultTitle: 'Warning', icon: '⚠' },
        info: { defaultTitle: 'Info', icon: 'ℹ' }
    };

    const details = typeDetails[type] || typeDetails.info;
    const toastTitle = title || details.defaultTitle;

    toast.innerHTML = `
        <div class="toast-icon">${details.icon}</div>
        <div class="toast-content">
            <h3 class="toast-title">${toastTitle}</h3>
            ${message ? `<p class="toast-message">${message}</p>` : ''}
        </div>
        <button class="toast-close-button">&times;</button>
    `;

    container.appendChild(toast);

    // Animate in
    setTimeout(() => {
        toast.classList.add('show');
    }, 100); // Small delay to allow the element to be in the DOM for transition

    const close = () => {
        toast.classList.remove('show');
        // Remove the element after the transition ends
        toast.addEventListener('transitionend', () => {
            if (toast.parentElement) {
                container.removeChild(toast);
            }
        }, { once: true });
    };

    // Auto-dismiss
    const timer = setTimeout(close, duration);

    // Allow manual closing
    toast.querySelector('.toast-close-button').addEventListener('click', () => {
        clearTimeout(timer);
        close();
    });
}

// Expose the function to the global scope to be used by other scripts
window.showToast = showToast;
