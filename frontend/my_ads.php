<?php
require_once __DIR__ . '/../backend/config.php';
if (function_exists('secureSession')) { secureSession(); } else { session_start(); }
if (!isset($_SESSION['user_id'])) { header('Location: auth.php?return=my_ads.php'); exit; }
define('PAGE_TITLE', 'إعلاناتي | حراج اليمن');
require __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/icons.php';
?>
<div style="margin-bottom:var(--sp-5);">
    <h1 class="section-title">إعلاناتي</h1>
    <p class="section-subtitle">إدارة كاملة لجميع إعلاناتك</p>
</div>
<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:var(--sp-5);">
    <button class="chip active" data-status="all">جميع الإعلانات</button>
    <button class="chip" data-status="active">نشطة</button>
    <button class="chip" data-status="sold">مباعة</button>
    <button class="chip" data-status="archived">مؤرشفة</button>
</div>
<div id="myAdsList"></div>

<script>
let currentStatus = 'all';
document.querySelectorAll('[data-status]').forEach(btn => {
    btn.onclick = () => {
        document.querySelectorAll('[data-status]').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentStatus = btn.dataset.status;
        loadMyAds();
    };
});
async function loadMyAds() {
    const container = document.getElementById('myAdsList');
    container.innerHTML = '<div class="ads-grid">' + skeletonGrid(4) + '</div>';
    const res = await api('ads&action=my_ads&status=' + currentStatus);
    if (!res.success) { container.innerHTML = `<div class="empty-state"><h3>تعذّر التحميل</h3><p>${res.message}</p></div>`; return; }
    const ads = res.ads || res.data?.ads || res.data || [];
    if (ads.length === 0) {
        container.innerHTML = `<div class="empty-state surface-card" style="padding:60px 20px;"><div style="font-size:60px;opacity:.4;margin-bottom:14px;">📭</div><h3>لا توجد إعلانات</h3><p>ابدأ بنشر إعلانك الأول!</p><a href="post.php" class="btn btn-primary" style="margin-top:14px;">أضف إعلان جديد</a></div>`;
        return;
    }
    container.innerHTML = `<div class="ads-grid">${ads.map(renderMyAdCard).join('')}</div>`;
}
function renderMyAdCard(ad) {
    const imgs = Array.isArray(ad.images) ? ad.images : (typeof ad.images === 'string' ? (JSON.parse(ad.images||'[]')||[]) : []);
    const img = imgs[0] || '';
    const statusLabels = {active:'نشط', sold:'مباع', archived:'مؤرشف', pending:'قيد المراجعة'};
    const statusCls = {active:'status-active', sold:'status-sold', archived:'status-archived', pending:'status-pending'};
    return `<div class="card" style="overflow:hidden;">
        <div style="aspect-ratio:16/10;background:var(--bg-soft);position:relative;">
            ${img ? `<img src="${escapeHtml(img)}" style="width:100%;height:100%;object-fit:cover;">` : ''}
            <span class="status-pill ${statusCls[ad.status] || ''}" style="position:absolute;top:10px;inset-inline-start:10px;">${statusLabels[ad.status] || ad.status}</span>
        </div>
        <div class="card-body">
            <h3 style="font-size:15px;font-weight:700;margin-bottom:6px;line-height:1.4;">${escapeHtml(ad.title)}</h3>
            <div style="color:var(--brand-600);font-weight:800;margin-bottom:8px;">${fmtPrice(ad.price)}</div>
            <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--muted);margin-bottom:14px;">
                <span>👁 ${ad.views || 0} مشاهدة</span>
                <span>${fmtDate(ad.created_at || ad.createdAt)}</span>
            </div>
            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                <a href="ad.php?id=${ad.id}" class="btn btn-secondary btn-sm" style="flex:1;">عرض</a>
                <a href="post.php?edit=${ad.id}" class="btn btn-secondary btn-sm" style="flex:1;">تعديل</a>
                <button class="btn btn-ghost btn-sm" onclick="adActionsMenu(${ad.id}, '${ad.status}', event)">⋯</button>
            </div>
        </div>
    </div>`;
}
function adActionsMenu(id, status, ev) {
    ev.stopPropagation();
    const existing = document.getElementById('adActionMenu');
    if (existing) existing.remove();
    const menu = document.createElement('div');
    menu.id = 'adActionMenu';
    menu.style.cssText = `position:fixed;background:var(--surface);border:1px solid var(--line);border-radius:12px;box-shadow:var(--sh-lg);padding:6px;min-width:200px;z-index:1000;`;
    const items = [];
    if (status === 'active') {
        items.push({txt: '⬆ رفع الإعلان', fn: 'bumpAd'});
        items.push({txt: '✓ تم البيع', fn: 'markSold'});
        items.push({txt: '📦 أرشفة', fn: 'archiveAd'});
    } else if (status === 'archived') {
        items.push({txt: '↻ إعادة تنشيط', fn: 'reactivateAd'});
    }
    items.push({txt: '🗑 حذف', fn: 'deleteAd', cls: 'danger'});
    menu.innerHTML = items.map(it => `<button onclick="${it.fn}(${id});document.getElementById('adActionMenu').remove();" style="display:block;width:100%;text-align:start;padding:10px 14px;font-size:13px;border-radius:8px;${it.cls==='danger'?'color:var(--danger);':''}">${it.txt}</button>`).join('');
    const rect = ev.target.getBoundingClientRect();
    menu.style.top = (rect.bottom + 4) + 'px';
    menu.style.insetInlineStart = (rect.left) + 'px';
    document.body.appendChild(menu);
    setTimeout(() => { document.addEventListener('click', () => menu.remove(), {once: true}); }, 0);
}
async function bumpAd(id) {
    const res = await api('ads&action=bump', { method: 'POST', data: { ad_id: id, adId: id } });
    toast(res.message, res.success ? 'success' : 'error');
    if (res.success) loadMyAds();
}
async function markSold(id) {
    confirmModal('تأكيد البيع', 'هل تم بيع المنتج فعلاً؟', async () => {
        const res = await api('ads&action=mark_sold', { method: 'POST', data: { ad_id: id, adId: id } });
        toast(res.message, res.success ? 'success' : 'error');
        if (res.success) loadMyAds();
    }, 'تأكيد', 'btn-success');
}
async function archiveAd(id) {
    const res = await api('ads&action=archive', { method: 'POST', data: { ad_id: id, adId: id } });
    toast(res.message, res.success ? 'success' : 'error');
    if (res.success) loadMyAds();
}
async function reactivateAd(id) {
    const res = await api('ads&action=reactivate', { method: 'POST', data: { ad_id: id, adId: id } });
    toast(res.message, res.success ? 'success' : 'error');
    if (res.success) loadMyAds();
}
async function deleteAd(id) {
    confirmModal('حذف الإعلان', 'هذا الإجراء نهائي. هل أنت متأكد؟', async () => {
        const res = await api('ads&action=delete', { method: 'POST', data: { ad_id: id, adId: id } });
        toast(res.message, res.success ? 'success' : 'error');
        if (res.success) loadMyAds();
    });
}
loadMyAds();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
