<?php
require_once __DIR__ . '/../backend/config.php';
if (function_exists('secureSession')) { secureSession(); } else { session_start(); }
if (!isset($_SESSION['user_id'])) { header('Location: auth.php?return=favorites.php'); exit; }
define('PAGE_TITLE', 'المفضلة | حراج اليمن');
require __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/icons.php';
?>
<div style="margin-bottom:var(--sp-5);">
    <h1 class="section-title">المفضلة</h1>
    <p class="section-subtitle">الإعلانات التي حفظتها</p>
</div>
<div id="favsList" class="ads-grid"></div>
<script>
async function loadFavs() {
    const list = document.getElementById('favsList');
    list.innerHTML = skeletonGrid(4);
    const res = await api('ads&action=favorites');
    if (!res.success) { list.innerHTML = `<div class="empty-state" style="grid-column:1/-1;"><h3>تعذّر التحميل</h3></div>`; return; }
    const ads = res.ads || res.data?.ads || res.data || [];
    if (!ads.length) {
        list.outerHTML = `<div class="empty-state surface-card" style="padding:60px 20px;"><div style="font-size:60px;opacity:.4;">❤️</div><h3>لا توجد عناصر في المفضلة</h3><p>اضغط على القلب في أي إعلان لإضافته هنا</p><a href="index.php" class="btn btn-primary" style="margin-top:14px;">تصفّح الإعلانات</a></div>`;
        return;
    }
    list.innerHTML = ads.map(ad => { ad.is_favorite = true; return renderAdCard(ad); }).join('');
}
loadFavs();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
