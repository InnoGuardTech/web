/* حراج اليمن الفاخر — Core JS v3.0 */
const API_BASE = '../backend/router.php';

let _csrfToken = null;
async function getCSRF() {
    if (_csrfToken) return _csrfToken;
    try {
        const r = await fetch(`${API_BASE}?route=csrf`, { credentials: 'same-origin' });
        const d = await r.json();
        _csrfToken = (d.data && (d.data.token || d.data.csrf_token)) || d.token || d.csrf_token;
        return _csrfToken;
    } catch (e) { return ''; }
}

async function api(route, options = {}) {
    const method = (options.method || 'GET').toUpperCase();
    const url = `${API_BASE}?route=${route}`;
    const init = { method, credentials: 'same-origin', headers: { 'Accept': 'application/json' } };
    if (method !== 'GET') {
        const token = await getCSRF();
        if (options.body instanceof FormData) {
            if (token) options.body.append('csrf_token', token);
            init.body = options.body;
        } else {
            init.headers['Content-Type'] = 'application/json';
            init.body = JSON.stringify({ ...(options.data || {}), csrf_token: token });
        }
    }
    try {
        const r = await fetch(url, init);
        const text = await r.text();
        try { return JSON.parse(text); }
        catch (e) { return { success: false, message: 'استجابة غير صالحة من الخادم' }; }
    } catch (e) {
        return { success: false, message: 'تعذّر الاتصال: ' + e.message };
    }
}

function toast(message, type = 'info', duration = 3500) {
    const container = document.getElementById('toastContainer') || (() => {
        const d = document.createElement('div'); d.id = 'toastContainer'; d.className = 'toast-container'; document.body.appendChild(d); return d;
    })();
    const el = document.createElement('div');
    el.className = `toast ${type}`;
    const icons = { success: '✓', error: '×', warning: '!', info: 'i' };
    const color = type === 'error' ? 'danger' : type === 'success' ? 'success' : type === 'warning' ? 'warning' : 'brand-500';
    el.innerHTML = `<span style="width:24px;height:24px;border-radius:50%;background:var(--${color});color:#fff;display:grid;place-items:center;font-weight:700;font-size:13px;flex-shrink:0;">${icons[type]||'i'}</span><span style="flex:1;">${message}</span>`;
    container.appendChild(el);
    setTimeout(() => { el.style.opacity='0'; el.style.transform='translateX(20px)'; setTimeout(()=>el.remove(), 300); }, duration);
}

function toggleTheme() {
    const html = document.documentElement;
    if (html.getAttribute('data-theme') === 'dark') {
        html.removeAttribute('data-theme'); localStorage.setItem('theme', 'light');
    } else {
        html.setAttribute('data-theme', 'dark'); localStorage.setItem('theme', 'dark');
    }
}

function toggleUserDropdown() {
    const dd = document.getElementById('userDropdown');
    if (!dd) return;
    dd.style.display = dd.style.display === 'none' ? 'block' : 'none';
}
document.addEventListener('click', (e) => {
    const dd = document.getElementById('userDropdown'), btn = document.getElementById('userMenuBtn');
    if (dd && btn && !dd.contains(e.target) && !btn.contains(e.target)) dd.style.display = 'none';
});

async function logout() {
    if (!confirm('هل تريد تسجيل الخروج؟')) return;
    await api('auth&action=logout', { method: 'POST' });
    toast('تم تسجيل الخروج', 'success');
    setTimeout(() => location.href = 'index.php', 600);
}

function confirmModal(title, message, onConfirm, confirmText = 'تأكيد', confirmClass = 'btn-danger') {
    const overlay = document.createElement('div');
    overlay.className = 'modal-overlay';
    overlay.innerHTML = `<div class="modal"><div class="modal-header"><h3>${title}</h3></div><div class="modal-body"><p style="color:var(--text-soft);line-height:1.7;">${message}</p></div><div class="modal-footer"><button class="btn btn-ghost" data-cancel>إلغاء</button><button class="btn ${confirmClass}" data-ok>${confirmText}</button></div></div>`;
    document.body.appendChild(overlay);
    overlay.querySelector('[data-cancel]').onclick = () => overlay.remove();
    overlay.querySelector('[data-ok]').onclick = () => { overlay.remove(); onConfirm(); };
    overlay.onclick = (e) => { if (e.target === overlay) overlay.remove(); };
}

function fmtPrice(n) {
    if (!n || isNaN(n)) return 'السعر عند الاتصال';
    return Number(n).toLocaleString('ar-EG') + ' ر.ي';
}
function fmtDate(d) {
    if (!d) return '';
    const date = new Date(d);
    const diff = (Date.now() - date.getTime()) / 1000;
    if (diff < 60) return 'الآن';
    if (diff < 3600) return Math.floor(diff/60) + ' د';
    if (diff < 86400) return Math.floor(diff/3600) + ' س';
    if (diff < 604800) return Math.floor(diff/86400) + ' يوم';
    return date.toLocaleDateString('ar-EG');
}
function escapeHtml(s) {
    if (s == null) return '';
    return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}
function skeletonGrid(count = 8) {
    let html = '';
    for (let i = 0; i < count; i++) {
        html += `<div class="ad-card"><div class="skeleton" style="aspect-ratio:4/3;border-radius:0;"></div><div class="ad-body"><div class="skeleton" style="height:18px;width:80%;"></div><div class="skeleton" style="height:20px;width:50%;margin-top:8px;"></div><div class="skeleton" style="height:14px;width:100%;margin-top:8px;"></div></div></div>`;
    }
    return html;
}

function renderAdCard(ad) {
    const imgs = Array.isArray(ad.images) ? ad.images : (typeof ad.images === 'string' ? (JSON.parse(ad.images||'[]')||[]) : []);
    const img = imgs[0] || ad.image || '';
    const slug = ad.slug ? `&slug=${encodeURIComponent(ad.slug)}` : '';
    const isBumped = ad.bumpedAt && (Date.now() - new Date(ad.bumpedAt).getTime()) < 86400000;
    const created = ad.created_at || ad.createdAt;
    const isNew = created && (Date.now() - new Date(created).getTime()) < 172800000;
    return `<a class="ad-card" href="ad.php?id=${ad.id}${slug}">
        <div class="ad-thumb">
            ${img ? `<img src="${escapeHtml(img)}" alt="${escapeHtml(ad.title)}" loading="lazy" onerror="this.style.display='none'">` : `<div style="width:100%;height:100%;display:grid;place-items:center;color:var(--muted);font-size:13px;">لا توجد صورة</div>`}
            <div class="ad-badges">
                ${isBumped ? '<span class="ad-badge gold">مميز</span>' : ''}
                ${isNew && !isBumped ? '<span class="ad-badge new">جديد</span>' : ''}
            </div>
            <button class="ad-fav ${ad.is_favorite ? 'active' : ''}" onclick="event.preventDefault();event.stopPropagation();toggleFavorite(${ad.id}, this)" aria-label="مفضلة">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="${ad.is_favorite ? 'currentColor' : 'none'}" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78Z"/></svg>
            </button>
        </div>
        <div class="ad-body">
            <h3 class="ad-title">${escapeHtml(ad.title)}</h3>
            <div class="ad-price">${fmtPrice(ad.price)}</div>
            <div class="ad-meta">
                <span>${escapeHtml(ad.city || '')}</span>
                <span>${fmtDate(created)}</span>
            </div>
        </div>
    </a>`;
}

async function toggleFavorite(adId, btn) {
    if (!window.CURRENT_USER) {
        toast('يجب تسجيل الدخول أولاً', 'warning');
        setTimeout(() => location.href = 'auth.php', 1000);
        return;
    }
    const res = await api('ads&action=toggle_favorite', { method: 'POST', data: { ad_id: adId, adId: adId } });
    if (res.success) {
        btn.classList.toggle('active');
        const svg = btn.querySelector('svg');
        if (svg) svg.setAttribute('fill', btn.classList.contains('active') ? 'currentColor' : 'none');
        toast(btn.classList.contains('active') ? 'تمت الإضافة للمفضلة' : 'تم الحذف من المفضلة', 'success', 1500);
    } else toast(res.message || 'حدث خطأ', 'error');
}

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => { navigator.serviceWorker.register('sw.js').catch(()=>{}); });
}
