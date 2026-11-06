/**
 * MITS Alumni Portal - Main JavaScript Utilities
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Initialize Bootstrap Tooltips
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

    // 2. Initialize Bootstrap Popovers
    const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
    [...popoverTriggerList].map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl));

    // 3. Auto-dismiss Flash Alerts after 5 seconds
    const autoAlerts = document.querySelectorAll('.flash-messages-container .alert');
    autoAlerts.forEach(alertEl => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alertEl);
            if (bsAlert) {
                bsAlert.close();
            }
        }, 5000);
    });

    // 4. Check Unread Notifications
    checkUnreadNotifications();
    setInterval(checkUnreadNotifications, 45000);
});

/**
 * Check and update notification bell badge
 */
async function checkUnreadNotifications() {
    const badge = document.getElementById('nav-notif-badge');
    if (!badge) return; // Not logged in

    try {
        const res = await fetch(getBaseUrl() + '/api/notifications.php?action=count');
        const data = await res.json();
        if (data.success) {
            if (data.unread > 0) {
                badge.classList.remove('d-none');
                badge.title = `${data.unread} new notifications`;
            } else {
                badge.classList.add('d-none');
            }
        }
    } catch (e) {
        // Silently ignore background polling errors
    }
}

/**
 * Get the current CSRF Token from meta tag
 */
function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

/**
 * Get Base URL from meta tag
 */
function getBaseUrl() {
    const meta = document.querySelector('meta[name="base-url"]');
    return meta ? meta.getAttribute('content') : '';
}

/**
 * Display a dynamic Bootstrap Toast message
 * @param {string} type - 'success', 'danger', 'info', 'warning'
 * @param {string} message - Text or HTML message
 */
function showToast(type, message) {
    const toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) return;

    const toastId = 'toast-' + Date.now();
    const bgClass = type === 'success' ? 'bg-success text-white' :
                    type === 'danger' ? 'bg-danger text-white' :
                    type === 'warning' ? 'bg-warning text-dark' : 'bg-primary text-white';

    const toastHtml = `
        <div id="${toastId}" class="toast align-items-center ${bgClass} border-0 shadow-lg rounded-3" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close ${type !== 'warning' ? 'btn-close-white' : ''} me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;

    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
    const toastEl = document.getElementById(toastId);
    const bsToast = new bootstrap.Toast(toastEl, { delay: 4000 });
    bsToast.show();

    toastEl.addEventListener('hidden.bs.toast', () => {
        toastEl.remove();
    });
}

/**
 * Enhanced fetch wrapper with automatic CSRF token header
 */
async function apiFetch(url, options = {}) {
    const defaultHeaders = {
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': getCsrfToken()
    };

    options.headers = {
        ...defaultHeaders,
        ...(options.headers || {})
    };

    try {
        const response = await fetch(url, options);
        return response;
    } catch (error) {
        console.error('API Fetch Error:', error);
        throw error;
    }
}
