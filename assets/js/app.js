/**
 * Smart Tax Planning Assistance - Global JavaScript Utility Library
 */

// Format numbers into Indian Rupee format (e.g. ₹12,50,000)
function formatInr(amount, decimals = 0) {
    if (isNaN(amount) || amount === null) return '₹0';
    let negative = amount < 0;
    amount = Math.abs(amount);
    
    let parts = amount.toFixed(decimals).split('.');
    let integerPart = parts[0];
    let decimalPart = parts.length > 1 ? '.' + parts[1] : '';

    let lastThree = integerPart.substring(integerPart.length - 3);
    let otherNumbers = integerPart.substring(0, integerPart.length - 3);
    if (otherNumbers !== '') {
        lastThree = ',' + lastThree;
    }
    let res = otherNumbers.replace(/\B(?=(\d{2})+(?!\d))/g, ",") + lastThree;
    return '₹' + (negative ? '-' : '') + res + decimalPart;
}

// Fetch API wrapper with automatic CSRF token and JSON parsing
async function apiFetch(url, options = {}) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    const headers = {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...(csrfToken ? { 'X-CSRF-Token': csrfToken } : {}),
        ...(options.headers || {})
    };

    if (options.body && typeof options.body === 'object' && !(options.body instanceof FormData)) {
        headers['Content-Type'] = 'application/json';
        options.body = JSON.stringify(options.body);
    }

    try {
        const response = await fetch(url, { ...options, headers });
        const data = await response.json().catch(() => null);

        if (!response.ok) {
            const errorMsg = data?.message || `Request failed with status ${response.status}`;
            throw new Error(errorMsg);
        }
        return data;
    } catch (err) {
        console.error('API Error:', err);
        throw err;
    }
}

// Display lightweight feedback alert/toast
function showToast(message, type = 'info') {
    const alertBox = document.createElement('div');
    alertBox.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3 shadow-lg`;
    alertBox.style.zIndex = '9999';
    alertBox.innerHTML = `
        <i class="bi bi-info-circle-fill me-2"></i> ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    document.body.appendChild(alertBox);
    setTimeout(() => {
        alertBox.classList.remove('show');
        setTimeout(() => alertBox.remove(), 300);
    }, 4500);
}

// Mobile sidebar toggle handler
document.addEventListener('DOMContentLoaded', () => {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.app-sidebar');

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('show');
        });

        // Close when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth < 992 && 
                sidebar.classList.contains('show') && 
                !sidebar.contains(e.target) && 
                !sidebarToggle.contains(e.target)) {
                sidebar.classList.remove('show');
            }
        });
    }

    // Auto-initialize Bootstrap tooltips if any
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
});
