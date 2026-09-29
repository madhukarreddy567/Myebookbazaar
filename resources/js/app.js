import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

/* ------------------------------------------------------------------
   Theme — manual toggle stored in localStorage, otherwise follows
   prefers-color-scheme. The <html> data-theme attribute drives the
   CSS variables in resources/css/tokens.css. An inline script in the
   layout <head> applies the stored value before first paint.
   ------------------------------------------------------------------ */
(function () {
    const KEY = 'meb-theme';

    function current() {
        try {
            const stored = localStorage.getItem(KEY);
            if (stored === 'dark' || stored === 'light') return stored;
        } catch (e) { /* storage unavailable */ }
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    function apply(theme) {
        document.documentElement.dataset.theme = theme;
        try { localStorage.setItem(KEY, theme); } catch (e) { /* ignore */ }
        document.dispatchEvent(new CustomEvent('meb:theme', { detail: theme }));
    }

    window.mebTheme = {
        current,
        apply,
        toggle() { apply(current() === 'dark' ? 'light' : 'dark'); },
    };
})();

/* ------------------------------------------------------------------
   Toasts — window.toast(message, type) appends a toast to the stack.
   ------------------------------------------------------------------ */
window.toast = function (message, type = 'info') {
    const stack = document.getElementById('toast-stack');
    if (!stack) return;
    const icons = {
        success: '<path d="M20 6 9 17l-5-5"/>',
        danger: '<path d="M12 9v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/>',
        info: '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4m0-4h.01"/>',
    };
    const el = document.createElement('div');
    el.className = 'toast toast-' + type;
    el.setAttribute('role', 'status');
    el.innerHTML =
        '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" ' +
        'stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">' +
        (icons[type] || icons.info) + '</svg><span></span>';
    el.querySelector('span').textContent = message;
    stack.appendChild(el);
    setTimeout(() => {
        el.style.transition = 'opacity 250ms, transform 250ms';
        el.style.opacity = '0';
        el.style.transform = 'translateY(8px)';
        setTimeout(() => el.remove(), 260);
    }, 4200);
};
