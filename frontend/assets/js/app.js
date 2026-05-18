/**
 * حراج اليمن - Core JavaScript v2.0
 * يحتوي على: API wrapper, Toasts, Theme, CSRF, Modal, Helpers
 */

const API_BASE = '../backend/router.php?route=';

// ===== CSRF Token Cache =====
let CSRF_TOKEN = null;
async function getCsrfToken() {
    if (CSRF_TOKEN) return CSRF_TOKEN;
    try {
        const res = await fetch(API_BASE + 'csrf', { credentials: 'include' });
        const data = await res.json();
        if (data.success) {
            CSRF_TOKEN = data.data.token;
            return CSRF_TOKEN;
        }
    } catch (e) {}
    return null;
}

// ===== API Request Wrapper =====
async function apiRequest(endpoint, method = 'GET', data = null, opts = {}) {
    const options = {
        method,
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' }
    };

    // Auto-attach CSRF for state-changing methods
    if (method !== 'GET' && !opts.skipCsrf) {
        const token = await getCsrfToken();
        if (token) options.headers['X-CSRF-Token'] = token;
    }

    if (data && method !== 'GET') options.body = JSON.stringify(data);

    try {
        const response = await fetch(API_BASE + endpoint, options);
        const result = await response.json();

        if (response.status === 419 || result?.code === 'CSRF_INVALID') {
            CSRF_TOKEN = null;
            const token = await getCsrfToken();
            if (token) {
                options.headers['X-CSRF-Token'] = token;
                const retry = await fetch(API_BASE + endpoint, options);
                return await retry.json();
            }
        }

        if (!response.ok || !result.success) {
            const msg = result.message || 'حدث خطأ غير معروف';
            if (!opts.silent) showToast(msg, 'error');
            const err = new Error(msg);
            err.code = result.code;
            err.status = response.status;
            err.data = result.data;
            throw err;
        }
        return result;
    } catch (error) {
        if (!error.message?.includes('Failed to fetch') && !opts.silent && !error.code) {
            // network errors only
            if (error.name === 'TypeError') showToast('فشل الاتصال بالخادم', 'error');
        }
        throw error;
    }
}

// ===== Toast Notifications =====
function showToast(message, type = 'success', duration = 4000) {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        document.body.appendChild(container);
    }
    const icons = { success: '✅', error: '⚠️', warning: '⚡', info: 'ℹ️' };
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `<span style="font-size:1.1rem;">${icons[type] || icons.info}</span><span>${escapeHtml(message)}</span>`;
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(-50px)';
        toast.style.transition = 'all 0.3s';
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

// ===== Theme Toggle =====
function toggleTheme() {
    const html = document.documentElement;
    const current = html.getAttribute('data-theme');
    if (current === 'dark') {
        html.removeAttribute('data-theme');
        localStorage.setItem('theme', 'light');
    } else {
        html.setAttribute('data-theme', 'dark');
        localStorage.setItem('theme', 'dark');
    }
}

// Apply theme early
(function() {
    const saved = localStorage.getItem('theme');
    if (saved === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
})();

// ===== Confirm Modal =====
function confirmModal(message, title = 'تأكيد العملية') {
    return new Promise((resolve) => {
        let overlay = document.getElementById('confirm-modal');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'confirm-modal';
            overlay.className = 'modal-overlay';
            overlay.innerHTML = `
                <div class="modal-box">
                    <div class="modal-header">
                        <h3 id="cm-title"></h3>
                        <button class="modal-close" onclick="document.getElementById('confirm-modal').classList.remove('show'); window._cmResolve(false);">×</button>
                    </div>
                    <div class="modal-body" id="cm-message"></div>
                    <div class="modal-footer">
                        <button class="btn-outline" onclick="document.getElementById('confirm-modal').classList.remove('show'); window._cmResolve(false);">إلغاء</button>
                        <button class="btn-danger" onclick="document.getElementById('confirm-modal').classList.remove('show'); window._cmResolve(true);">تأكيد</button>
                    </div>
                </div>`;
            document.body.appendChild(overlay);
        }
        document.getElementById('cm-title').textContent = title;
        document.getElementById('cm-message').textContent = message;
        window._cmResolve = resolve;
        overlay.classList.add('show');
    });
}

// ===== Generic Modal =====
function openModal(html) {
    let overlay = document.getElementById('generic-modal');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'generic-modal';
        overlay.className = 'modal-overlay';
        document.body.appendChild(overlay);
    }
    overlay.innerHTML = `<div class="modal-box">${html}</div>`;
    overlay.classList.add('show');
    overlay.onclick = (e) => { if (e.target === overlay) closeModal(); };
}

function closeModal() {
    const overlay = document.getElementById('generic-modal');
    if (overlay) overlay.classList.remove('show');
}

// ===== HTML Escape =====
function escapeHtml(text) {
    if (text == null) return '';
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// ===== Logout =====
async function logout() {
    if (!await confirmModal('هل تريد تسجيل الخروج من حسابك؟', 'تسجيل الخروج')) return;
    try {
        await apiRequest('auth&action=logout', 'POST', {}, { skipCsrf: true });
        showToast('تم تسجيل الخروج', 'success');
        setTimeout(() => window.location.href = 'index.php', 600);
    } catch (e) {}
}

// ===== Update Badges (Notifications + Chat) =====
async function updateBadgeCounts() {
    try {
        const [chatRes, notifRes] = await Promise.all([
            apiRequest('chat&action=unread_count', 'GET', null, { silent: true }).catch(() => null),
            apiRequest('notifications&action=unread_count', 'GET', null, { silent: true }).catch(() => null),
        ]);

        const chatBadge = document.getElementById('chat-badge');
        const notifBadge = document.getElementById('notif-badge');

        if (chatBadge && chatRes?.data) {
            const c = chatRes.data.count || 0;
            chatBadge.textContent = c > 99 ? '99+' : c;
            chatBadge.style.display = c > 0 ? 'inline-flex' : 'none';
        }
        if (notifBadge && notifRes?.data) {
            const c = notifRes.data.count || 0;
            notifBadge.textContent = c > 99 ? '99+' : c;
            notifBadge.style.display = c > 0 ? 'inline-flex' : 'none';
        }
    } catch (e) {}
}

// ===== Presence Heartbeat =====
let presenceInterval = null;
function startPresenceHeartbeat() {
    if (presenceInterval) return;
    const ping = () => apiRequest('presence&action=ping', 'POST', { status: 'online' }, { silent: true, skipCsrf: true }).catch(() => {});
    ping();
    presenceInterval = setInterval(ping, 25000);

    window.addEventListener('beforeunload', () => {
        navigator.sendBeacon && navigator.sendBeacon(API_BASE + 'presence&action=offline');
    });

    // Detect visibility
    document.addEventListener('visibilitychange', () => {
        const status = document.hidden ? 'away' : 'online';
        apiRequest('presence&action=ping', 'POST', { status }, { silent: true, skipCsrf: true }).catch(() => {});
    });
}

// ===== Format Number =====
function formatNumber(n) {
    return new Intl.NumberFormat('ar-EG').format(n);
}

// ===== Debounce =====
function debounce(fn, wait = 400) {
    let t;
    return function() {
        clearTimeout(t);
        const args = arguments, ctx = this;
        t = setTimeout(() => fn.apply(ctx, args), wait);
    };
}

// ===== File to Base64 =====
function fileToBase64(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(reader.result);
        reader.onerror = reject;
        reader.readAsDataURL(file);
    });
}

// ===== Resize image client-side (to reduce upload size) =====
async function resizeImage(file, maxWidth = 1200, quality = 0.85) {
    return new Promise((resolve, reject) => {
        const img = new Image();
        img.onload = () => {
            const canvas = document.createElement('canvas');
            let { width, height } = img;
            if (width > maxWidth) {
                height = Math.round((maxWidth / width) * height);
                width = maxWidth;
            }
            canvas.width = width;
            canvas.height = height;
            canvas.getContext('2d').drawImage(img, 0, 0, width, height);
            resolve(canvas.toDataURL(file.type === 'image/png' ? 'image/png' : 'image/jpeg', quality));
        };
        img.onerror = reject;
        img.src = URL.createObjectURL(file);
    });
}

// ===== Stars HTML =====
function getStarsHTML(rating) {
    const r = parseFloat(rating) || 0;
    const full = Math.floor(r);
    const half = (r - full) >= 0.5;
    let s = '';
    for (let i = 0; i < full; i++) s += '★';
    if (half) s += '⯪';
    for (let i = full + (half ? 1 : 0); i < 5; i++) s += '☆';
    return `<span style="color:#f59e0b; letter-spacing:2px;">${s}</span>`;
}

// ===== Share helpers =====
function shareWhatsApp(text, url) {
    const msg = encodeURIComponent(`${text}\n${url}`);
    window.open(`https://wa.me/?text=${msg}`, '_blank');
}
function shareTwitter(text, url) {
    const msg = encodeURIComponent(`${text}\n${url}`);
    window.open(`https://twitter.com/intent/tweet?text=${msg}`, '_blank');
}
function shareFacebook(url) {
    window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`, '_blank');
}
function shareTelegram(text, url) {
    window.open(`https://t.me/share/url?url=${encodeURIComponent(url)}&text=${encodeURIComponent(text)}`, '_blank');
}
async function copyToClipboard(text) {
    try {
        await navigator.clipboard.writeText(text);
        showToast('تم نسخ الرابط 📋', 'success');
    } catch {
        const ta = document.createElement('textarea');
        ta.value = text;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        ta.remove();
        showToast('تم نسخ الرابط', 'success');
    }
}

// ===== Initialization =====
document.addEventListener('DOMContentLoaded', () => {
    // Load CSRF token
    getCsrfToken();

    // Update badges if user menu exists
    if (document.getElementById('user-menu-area') || document.getElementById('chat-badge')) {
        updateBadgeCounts();
        setInterval(updateBadgeCounts, 30000);
        startPresenceHeartbeat();
    }

    // Sidebar toggle for mobile
    const sidebarToggle = document.getElementById('sidebar-toggle-btn');
    const sidebar = document.querySelector('.sidebar-card');
    if (sidebarToggle && sidebar) {
        let backdrop = document.querySelector('.sidebar-backdrop');
        if (!backdrop) {
            backdrop = document.createElement('div');
            backdrop.className = 'sidebar-backdrop';
            document.body.appendChild(backdrop);
        }
        const closeSidebar = () => {
            sidebar.classList.remove('open');
            backdrop.classList.remove('show');
        };
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            backdrop.classList.toggle('show');
        });
        backdrop.addEventListener('click', closeSidebar);
        // Close on link click
        sidebar.querySelectorAll('a, .brand-item, .brand-list-all-item').forEach(el => {
            el.addEventListener('click', () => setTimeout(closeSidebar, 100));
        });
    }
});

// ===== Mark as global =====
window.apiRequest = apiRequest;
window.showToast = showToast;
window.toggleTheme = toggleTheme;
window.confirmModal = confirmModal;
window.openModal = openModal;
window.closeModal = closeModal;
window.escapeHtml = escapeHtml;
window.logout = logout;
window.getStarsHTML = getStarsHTML;
window.shareWhatsApp = shareWhatsApp;
window.shareTwitter = shareTwitter;
window.shareFacebook = shareFacebook;
window.shareTelegram = shareTelegram;
window.copyToClipboard = copyToClipboard;
window.fileToBase64 = fileToBase64;
window.resizeImage = resizeImage;
window.debounce = debounce;
window.formatNumber = formatNumber;
