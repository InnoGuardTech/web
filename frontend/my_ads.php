<?php
require_once __DIR__ . '/../config.php';
if (!isset($_SESSION['user_id'])) { header('Location: auth.php'); exit; }
define('PAGE_TITLE', 'إعلاناتي - ' . SITE_NAME);
include __DIR__ . '/includes/header.php';
?>

<div class="container animate-fade-in">
    <div class="flex items-center justify-between" style="margin-bottom:1rem; flex-wrap:wrap; gap:0.5rem;">
        <h2 style="margin:0; color:var(--primary); font-weight:900;">📋 إعلاناتي</h2>
        <a href="post.php" class="btn-gold">+ إضافة إعلان</a>
    </div>

    <div class="filter-tabs">
        <button class="filter-tab-btn active" data-status="all" onclick="filterStatus('all', this)">الكل</button>
        <button class="filter-tab-btn" data-status="active" onclick="filterStatus('active', this)">🟢 نشطة</button>
        <button class="filter-tab-btn" data-status="sold" onclick="filterStatus('sold', this)">✓ تم البيع</button>
        <button class="filter-tab-btn" data-status="archived" onclick="filterStatus('archived', this)">📦 المؤرشفة</button>
    </div>

    <div id="ads-list" class="ad-list">
        <div style="text-align:center; padding:3rem; color:var(--text-muted);">جاري التحميل...</div>
    </div>
</div>

<script src="assets/js/app.js"></script>
<script>
let currentStatus = 'all';

async function loadMyAds() {
    const container = document.getElementById('ads-list');
    container.innerHTML = '<div style="text-align:center; padding:3rem; color:var(--text-muted);">جاري التحميل...</div>';
    try {
        const r = await apiRequest('ads&action=my_ads&status=' + currentStatus);
        const ads = r.data;
        if (!ads.length) {
            container.innerHTML = `<div style="text-align:center; padding:4rem; color:var(--text-muted);">
                <div style="font-size:4rem; opacity:0.3;">📭</div>
                <h3>لا توجد إعلانات</h3>
                <a href="post.php" class="btn-primary" style="margin-top:1rem;">+ نشر إعلانك الأول</a>
            </div>`;
            return;
        }
        container.innerHTML = ads.map(a => `
            <div class="ad-row">
                <div class="ad-row-main">
                    <img class="ad-row-thumb" src="${a.image}" alt="" loading="lazy">
                    <div class="ad-row-content">
                        <h3 class="ad-row-title">
                            <a href="ad.php?id=${a.id}${a.slug?'&slug='+encodeURIComponent(a.slug):''}" style="color:inherit; text-decoration:none;">${escapeHtml(a.title)}</a>
                            <span class="status-badge status-${a.status}" style="margin-right:6px;">${a.statusLabel}</span>
                        </h3>
                        <div class="ad-row-meta">
                            <div class="ad-row-meta-item">⏱️ ${a.date}</div>
                            <div class="ad-row-meta-item">👁️ ${a.views} مشاهدة</div>
                            <div class="ad-row-meta-item">📍 ${a.city}</div>
                        </div>
                        <div style="margin-top:0.5rem; display:flex; gap:0.4rem; flex-wrap:wrap;">
                            <a href="ad.php?id=${a.id}" class="btn-outline btn-sm">👁️ عرض</a>
                            <a href="post.php?edit=${a.id}" class="btn-outline btn-sm">✏️ تعديل</a>
                            ${a.status === 'active' ? `<button class="btn-outline btn-sm" onclick="bumpAd(${a.id})">🔥 تجديد</button>` : ''}
                            ${a.status === 'active' ? `<button class="btn-outline btn-sm" onclick="markStatus(${a.id},'sold')">✓ تم البيع</button>` : ''}
                            ${a.status !== 'archived' ? `<button class="btn-outline btn-sm" onclick="markStatus(${a.id},'archived')">📦 أرشفة</button>` : ''}
                            ${a.status !== 'active' ? `<button class="btn-outline btn-sm" onclick="markStatus(${a.id},'active')">⚡ تنشيط</button>` : ''}
                            <button class="btn-outline btn-sm" style="border-color:var(--danger); color:var(--danger);" onclick="deleteAd(${a.id})">🗑️ حذف</button>
                        </div>
                    </div>
                </div>
                <div class="ad-row-side">
                    <div class="ad-row-price">${a.price}</div>
                </div>
            </div>
        `).join('');
    } catch (e) {
        container.innerHTML = '<div style="text-align:center; padding:3rem; color:var(--danger);">فشل التحميل</div>';
    }
}

function filterStatus(status, btn) {
    currentStatus = status;
    document.querySelectorAll('.filter-tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    loadMyAds();
}

async function bumpAd(id) {
    try {
        await apiRequest('ads&action=bump', 'POST', { id });
        showToast('🔥 تم التجديد', 'success');
        loadMyAds();
    } catch (e) {}
}

async function markStatus(id, status) {
    const labels = {sold:'تم البيع', archived:'الأرشفة', active:'التنشيط'};
    if (!await confirmModal(`هل أنت متأكد من ${labels[status]}؟`, 'تأكيد')) return;
    try {
        await apiRequest('ads&action=change_status', 'POST', { id, status });
        showToast('تم تحديث الحالة', 'success');
        loadMyAds();
    } catch (e) {}
}

async function deleteAd(id) {
    if (!await confirmModal('سيتم حذف الإعلان نهائياً!', 'حذف')) return;
    try {
        await apiRequest('ads&action=delete', 'POST', { id });
        showToast('تم الحذف', 'success');
        loadMyAds();
    } catch (e) {}
}

document.addEventListener('DOMContentLoaded', loadMyAds);
</script>
</body>
</html>
