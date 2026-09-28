import $ from 'jquery';
import { createIcons, icons } from 'lucide';

window.$ = window.jQuery = $;

// Universal Lucide icon initializer that guarantees icons object is supplied
window.createIcons = function(options = {}) {
    try {
        return createIcons({ icons, ...options });
    } catch (e) {
        console.error('Lucide createIcons error:', e);
    }
};
window.refreshIcons = window.createIcons;
window.lucide = {
    createIcons: window.createIcons,
    icons: icons
};

// Initialize Lucide Icons and Theme on load
document.addEventListener('DOMContentLoaded', () => {
    initTheme();
    window.createIcons();
});


// Theme Management (Dark: night, Light: light)
function initTheme() {
    const savedTheme = localStorage.getItem('theme') || 'night';
    document.documentElement.setAttribute('data-theme', savedTheme);
    updateThemeToggleUI(savedTheme);
}

window.toggleTheme = function() {
    const currentTheme = document.documentElement.getAttribute('data-theme') || 'night';
    const newTheme = currentTheme === 'night' ? 'light' : 'night';
    document.documentElement.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    updateThemeToggleUI(newTheme);
};

function updateThemeToggleUI(theme) {
    const sunIcon = document.getElementById('theme-icon-sun');
    const moonIcon = document.getElementById('theme-icon-moon');
    if (sunIcon && moonIcon) {
        if (theme === 'night') {
            // Night theme active: display Sun icon (click to switch to Light)
            sunIcon.classList.remove('hidden');
            moonIcon.classList.add('hidden');
        } else {
            // Light theme active: display Moon icon (click to switch to Night)
            sunIcon.classList.add('hidden');
            moonIcon.classList.remove('hidden');
        }
    }
}

// User Rule 12: Mandatory Form Submit Disabling & Loading Spinner State
window.submitWithLoader = function(btn) {
    if (!btn) return;
    const form = btn.closest('form');
    if (form && !form.checkValidity()) {
        form.reportValidity();
        return false;
    }
    btn.dataset.originalHtml = btn.innerHTML;
    btn.setAttribute('disabled', 'disabled');
    btn.classList.add('opacity-75', 'cursor-not-allowed');
    btn.innerHTML = '<span class="loading loading-spinner loading-xs me-1"></span> Processing...';
    if (form) {
        form.submit();
    }
    return true;
};

// Toast notification system using DaisyUI alert components
window.showToast = function(message, type = 'info', duration = 4000) {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const alertTypes = {
        success: 'alert-success',
        error: 'alert-error',
        warning: 'alert-warning',
        info: 'alert-info'
    };

    const alertClass = alertTypes[type] || 'alert-info';
    const toastId = 'toast-' + Date.now();

    const toastHtml = `
        <div id="${toastId}" class="alert ${alertClass} shadow-lg transition-all duration-300 transform translate-y-2 opacity-0 flex items-center justify-between py-2.5 px-4 text-sm font-medium">
            <span>${message}</span>
            <button onclick="document.getElementById('${toastId}').remove()" class="btn btn-ghost btn-xs btn-circle ml-2">✕</button>
        </div>
    `;

    $(container).append(toastHtml);
    const toastElem = document.getElementById(toastId);
    setTimeout(() => {
        toastElem.classList.remove('translate-y-2', 'opacity-0');
    }, 10);

    setTimeout(() => {
        if (toastElem) {
            toastElem.classList.add('opacity-0', 'translate-y-2');
            setTimeout(() => toastElem.remove(), 300);
        }
    }, duration);
};
