/* ============================================================================
   حراج اليمن الفاخر — Core JS v4.0
   Smart UX • Scroll reveal • Theme sync • Toast • Counter animation
   ============================================================================ */

const API_BASE = '../backend/router.php';

/* ===== Toast ===== */
function toast(msg, type = 'info', duration = 3500) {
    const c = document.getElementById('toastContainer');
    if (!c) { console.log('[toast]', msg); return; }
    const t = document.createElement('div');
    t.className = 'toast ' + type;
    const icons = {
        success: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>',
        error: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>',
        warning: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
        info: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>'
    };
    t.innerHTML = (icons[type] || icons.info) + '<span>' + msg + '</span>';
    c.appendChild(t);
    setTimeout(() => {
        t.style.animation = 'toastIn .3s reverse';
        setTimeout(() => t.remove(), 300);
    }, duration);
}
window.toast = toast;

/* ===== Theme toggle ===== */
function toggleTheme() {
    const cur = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', cur);
    localStorage.setItem('theme', cur);
    syncThemeIcon();
}
function syncThemeIcon() {
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const l = document.getElementById('themeIconLight');
    const d = document.getElementById('themeIconDark');
    if (l && d) {
        l.style.display = isDark ? '' : 'none';
        d.style.display = isDark ? 'none' : '';
    }
}
window.toggleTheme = toggleTheme;

/* ===== User dropdown ===== */
function toggleUserDropdown() {
    const dd = document.getElementById('userDropdown');
    if (!dd) return;
    dd.style.display = dd.style.display === 'none' || !dd.style.display ? 'block' : 'none';
}
window.toggleUserDropdown = toggleUserDropdown;
document.addEventListener('click', (e) => {
    const dd = document.getElementById('userDropdown');
    const btn = document.getElementById('userMenuBtn');
    if (dd && btn && !dd.contains(e.target) && !btn.contains(e.target)) {
        dd.style.display = 'none';
    }
});

/* ===== Logout ===== */
async function logout() {
    try {
        await api('auth&action=logout', { method: 'POST' });
    } catch (e) {}
    location.href = 'index.php';
}
window.logout = logout;

/* ===== API helper ===== */
async function api(routeAction, options = {}) {
    // routeAction can be "auth&action=login" or just "auth"
    const url = API_BASE + (routeAction.includes('?') ? '&' : '?') + 'route=' + routeAction;
    
    const method = (options.method || 'GET').toUpperCase();
    const opt = {
        method: method,
        credentials: 'include',
        headers: Object.assign({ 
            'Accept': 'application/json',
            'X-CSRF-Token': window.CSRF_TOKEN || ''
        }, options.headers || {})
    };

    if (method !== 'GET') {
        const data = options.data || options.body;
        if (data) {
            if (data instanceof FormData) {
                opt.body = data;
            } else {
                opt.headers['Content-Type'] = 'application/json';
                opt.body = JSON.stringify(data);
            }
        }
    }

    try {
        const r = await fetch(url, opt);
        if (r.status === 419) { // CSRF Error
            const j = await r.json().catch(() => ({}));
            if (j.code === 'CSRF_INVALID') {
                // Refresh page or handle CSRF
                toast('انتهت الجلسة، يرجى تحديث الصفحة', 'error');
            }
            return j;
        }
        const j = await r.json().catch(() => ({}));
        return j;
    } catch (e) {
        console.error('[API Error]', e);
        return { success: false, message: 'خطأ في الاتصال بالخادم' };
    }
}
window.api = api;

/* ===== Confirm dialog ===== */
function confirmAction(message, onConfirm, confirmText = 'تأكيد') {
    if (confirm(message)) onConfirm();
}
window.confirmAction = confirmAction;

/* ===== Format helpers ===== */
function fmtPrice(p) {
    if (!p || p <= 0) return 'قابل للتفاوض';
    return new Intl.NumberFormat('ar-YE').format(p) + ' ر.ي';
}
function fmtDate(d) {
    if (!d) return '';
    const dt = new Date(d);
    const now = new Date();
    const diff = (now - dt) / 1000;
    if (diff < 60) return 'الآن';
    if (diff < 3600) return Math.floor(diff / 60) + ' د';
    if (diff < 86400) return Math.floor(diff / 3600) + ' س';
    if (diff < 604800) return Math.floor(diff / 86400) + ' ي';
    return dt.toLocaleDateString('ar');
}
window.fmtPrice = fmtPrice;
window.fmtDate = fmtDate;

/* ===== Counter animation ===== */
function animateCounter(el, target, duration = 1500) {
    const start = 0;
    const startTime = performance.now();
    function tick(now) {
        const t = Math.min((now - startTime) / duration, 1);
        const eased = 1 - Math.pow(1 - t, 3);
        const v = Math.floor(start + (target - start) * eased);
        el.textContent = new Intl.NumberFormat('ar').format(v) + (el.dataset.suffix || '');
        if (t < 1) requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
}
window.animateCounter = animateCounter;

/* ===== Scroll reveal ===== */
const revealObs = new IntersectionObserver((entries) => {
    entries.forEach(en => {
        if (en.isIntersecting) {
            en.target.classList.add('visible');
            // Counter animation if applicable
            if (en.target.dataset.counter) {
                animateCounter(en.target, parseInt(en.target.dataset.counter, 10));
                delete en.target.dataset.counter;
            }
            revealObs.unobserve(en.target);
        }
    });
}, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

function initReveal() {
    document.querySelectorAll('.reveal,[data-counter]').forEach(el => revealObs.observe(el));
}

/* ===== Header scroll effect ===== */
function initHeaderScroll() {
    const h = document.querySelector('.site-header');
    if (!h) return;
    let last = 0;
    window.addEventListener('scroll', () => {
        const y = window.scrollY;
        if (y > 20) h.classList.add('scrolled');
        else h.classList.remove('scrolled');
        last = y;
    }, { passive: true });
}

/* ===== Smooth anchor scroll ===== */
document.addEventListener('click', (e) => {
    const a = e.target.closest('a[href^="#"]');
    if (!a) return;
    const id = a.getAttribute('href');
    if (id.length > 1) {
        const target = document.querySelector(id);
        if (target) {
            e.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }
});

/* ===== Lazy load images via Intersection Observer ===== */
function initLazyImages() {
    const imgs = document.querySelectorAll('img[data-src]');
    if (!imgs.length) return;
    const obs = new IntersectionObserver((entries) => {
        entries.forEach(en => {
            if (en.isIntersecting) {
                const img = en.target;
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
                obs.unobserve(img);
            }
        });
    }, { rootMargin: '100px' });
    imgs.forEach(i => obs.observe(i));
}

/* ===== Helpers ===== */
function escapeHtml(s) {
    return String(s || '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}
window.escapeHtml = escapeHtml;

function skeletonGrid(count = 6) {
    return Array(count).fill(`
        <div class="ad-card">
            <div class="skeleton" style="aspect-ratio:4/3;"></div>
            <div class="ad-body">
                <div class="skeleton" style="height:18px;width:80%;"></div>
                <div class="skeleton" style="height:22px;width:50%;margin-top:8px;"></div>
                <div class="skeleton" style="height:14px;width:70%;margin-top:8px;"></div>
            </div>
        </div>
    `).join('');
}
window.skeletonGrid = skeletonGrid;

function renderAdCard(ad) {
    const price = ad.price_formatted || (ad.price ? new Intl.NumberFormat('ar-YE').format(ad.price) + ' ر.ي' : 'قابل للتفاوض');
    const img = ad.image || ad.thumbnail || (Array.isArray(ad.images) ? ad.images[0] : null) || '';
    const cat = ad.category || 'other';
    const catLabels = {cars:'سيارات',realestate:'عقارات',electronics:'إلكترونيات',furniture:'أثاث',jobs:'وظائف',services:'خدمات',livestock:'حيوانات',other:'أخرى'};
    return `
        <a href="ad.php?id=${ad.id}" class="ad-card animate-fadeInUp">
            <div class="ad-img">
                ${img ? `<img src="${escapeHtml(img)}" alt="${escapeHtml(ad.title)}" loading="lazy">` : `<div class="ad-img-fallback">📦</div>`}
                <span class="ad-badge">${catLabels[cat] || 'إعلان'}</span>
                ${ad.isFeatured == 1 ? '<span class="ad-badge featured" style="top:auto;bottom:10px;">⭐ مميز</span>' : ''}
            </div>
            <div class="ad-body">
                <div class="ad-title">${escapeHtml(ad.title)}</div>
                <div class="ad-price">${price}</div>
                <div class="ad-meta">
                    <span>📍 ${escapeHtml(ad.city || '—')}</span>
                    <span>👁 ${ad.views || 0}</span>
                </div>
            </div>
        </a>
    `;
}
window.renderAdCard = renderAdCard;

/* ===== Init ===== */
document.addEventListener('DOMContentLoaded', () => {
    syncThemeIcon();
    initHeaderScroll();
    initReveal();
    initLazyImages();
});

/* ===== Service Worker ===== */
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('sw.js').catch(() => {});
    });
}
