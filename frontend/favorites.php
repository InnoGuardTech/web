<?php
require_once __DIR__ . '/../config.php';
if (!isset($_SESSION['user_id'])) { header('Location: auth.php'); exit; }
define('PAGE_TITLE', 'المفضلة - ' . SITE_NAME);
include __DIR__ . '/includes/header.php';
?>
<div class="container animate-fade-in">
    <h2 style="margin-top:0; margin-bottom:1.5rem; color:var(--primary); font-weight:900;">❤️ إعلاناتي المفضلة</h2>
    <div id="favs" class="ad-list"><div style="text-align:center; padding:3rem; color:var(--text-muted);">جاري التحميل...</div></div>
</div>
<script src="assets/js/app.js"></script>
<script>
async function load() {
    try {
        const r = await apiRequest('ads&action=favorites');
        const c = document.getElementById('favs');
        if (!r.data.length) {
            c.innerHTML = `<div style="text-align:center; padding:4rem; color:var(--text-muted);">
                <div style="font-size:4rem; opacity:0.3;">💔</div>
                <h3>لا توجد إعلانات في المفضلة بعد</h3>
                <a href="index.php" class="btn-primary" style="margin-top:1rem;">تصفّح الإعلانات</a>
            </div>`;
            return;
        }
        c.innerHTML = r.data.map(a => `
            <a href="ad.php?id=${a.id}${a.slug?'&slug='+encodeURIComponent(a.slug):''}" class="ad-row">
                <div class="ad-row-main">
                    <img class="ad-row-thumb" src="${a.image}" alt="" loading="lazy">
                    <div class="ad-row-content">
                        <h3 class="ad-row-title">${a.icon} ${escapeHtml(a.title)}</h3>
                        <div class="ad-row-meta">
                            <div class="ad-row-meta-item">📍 ${a.city}</div>
                            <div class="ad-row-meta-item">⏱️ ${a.date}</div>
                            <div class="ad-row-meta-item">📁 ${a.category}</div>
                        </div>
                    </div>
                </div>
                <div class="ad-row-side"><div class="ad-row-price">${a.price}</div></div>
            </a>`).join('');
    } catch(e) {}
}
document.addEventListener('DOMContentLoaded', load);
</script>
</body></html>
