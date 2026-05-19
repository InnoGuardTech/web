<?php
require_once __DIR__ . '/../backend/config.php';
if (function_exists('secureSession')) { secureSession(); } else { session_start(); }

$adId = (int)($_GET['id'] ?? 0);
if (!$adId) { header('Location: index.php'); exit; }

$pdo = getDBConnection();
$st = $pdo->prepare("SELECT a.*, u.name as seller_name, u.phone as seller_phone, u.rating as seller_rating, u.createdAt as seller_joined, u.isPhoneVerified as phone_verified
                     FROM ads a JOIN users u ON a.userId=u.id WHERE a.id=:id LIMIT 1");
$st->execute([':id' => $adId]);
$ad = $st->fetch(PDO::FETCH_ASSOC);

if (!$ad) {
    require __DIR__ . '/includes/header.php';
    echo '<div class="empty-state" style="padding:100px 20px;"><h3>الإعلان غير موجود</h3><p>قد يكون محذوفًا أو معطّلاً.</p><a href="index.php" class="btn btn-primary">العودة للرئيسية</a></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

try { $pdo->prepare("UPDATE ads SET views = views + 1 WHERE id = :id")->execute([':id' => $adId]); } catch (Throwable $e) {}

$images = json_decode($ad['images'] ?? '[]', true) ?: [];
$specs = json_decode($ad['specifications'] ?? '{}', true) ?: [];
$firstImage = $images[0] ?? '';

define('PAGE_TITLE', $ad['title'] . ' - ' . SITE_NAME);
define('PAGE_DESC', mb_substr(strip_tags($ad['description'] ?? ''), 0, 160));
define('PAGE_OG_IMAGE', $firstImage);
define('PAGE_URL', 'https://' . ($_SERVER['HTTP_HOST'] ?? '') . $_SERVER['REQUEST_URI']);

$me = isset($_SESSION['user_id']) ? getCurrentUser() : null;
$isOwner = $me && $me['id'] == ($ad['userId'] ?? 0);
$isFav = false;
if ($me) {
    try {
        $f = $pdo->prepare("SELECT 1 FROM favorites WHERE userId=:u AND adId=:a");
        $f->execute([':u' => $me['id'], ':a' => $adId]);
        $isFav = (bool)$f->fetchColumn();
    } catch (Throwable $e) {}
}

$schema = [
    '@context' => 'https://schema.org', '@type' => 'Product',
    'name' => $ad['title'],
    'description' => mb_substr(strip_tags($ad['description'] ?? ''), 0, 300),
    'image' => $firstImage ?: null,
    'offers' => ['@type' => 'Offer', 'price' => $ad['price'], 'priceCurrency' => 'YER',
        'availability' => $ad['status'] === 'active' ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock']
];

require __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/icons.php';
?>
<script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>

<nav style="display:flex;gap:6px;font-size:13px;color:var(--muted);margin-bottom:var(--sp-4);flex-wrap:wrap;">
    <a href="index.php">الرئيسية</a><span>/</span>
    <a href="index.php?category=<?= htmlspecialchars($ad['category']) ?>"><?= htmlspecialchars(getCategoryName($ad['category'])) ?></a><span>/</span>
    <span style="color:var(--text);"><?= htmlspecialchars(mb_substr($ad['title'], 0, 40, 'UTF-8')) ?></span>
</nav>

<div class="ad-detail-grid">
    <div>
        <div class="gallery">
            <div class="gallery-main" id="galleryMain">
                <?php if ($firstImage): ?><img src="<?= htmlspecialchars($firstImage) ?>" alt="<?= htmlspecialchars($ad['title']) ?>" id="mainImage">
                <?php else: ?><div style="width:100%;height:100%;display:grid;place-items:center;background:var(--bg-soft);color:var(--muted);"><?= icon('image', ['size'=>60]) ?></div><?php endif; ?>
                <?php if (count($images) > 1): ?>
                    <button class="gallery-nav prev" onclick="navGallery(-1)"><?= icon('chevron-right', ['size'=>22]) ?></button>
                    <button class="gallery-nav next" onclick="navGallery(1)"><?= icon('chevron-left', ['size'=>22]) ?></button>
                <?php endif; ?>
            </div>
            <?php if (count($images) > 1): ?>
            <div class="gallery-thumbs">
                <?php foreach ($images as $i => $img): ?><img src="<?= htmlspecialchars($img) ?>" data-i="<?= $i ?>" class="<?= $i === 0 ? 'active' : '' ?>" onclick="selectImage(<?= $i ?>)"><?php endforeach; ?>
            </div><?php endif; ?>
        </div>

        <h1 class="ad-detail-title"><?= htmlspecialchars($ad['title']) ?></h1>
        <div class="ad-detail-price"><?= formatPrice($ad['price']) ?></div>

        <div class="ad-detail-meta">
            <span><?= icon('map-pin', ['size'=>16]) ?> <?= htmlspecialchars($ad['city']) ?></span>
            <span><?= icon('clock', ['size'=>16]) ?> <?= formatArabicDate($ad['createdAt']) ?></span>
            <span><?= icon('eye', ['size'=>16]) ?> <?= number_format((int)$ad['views']) ?> مشاهدة</span>
            <span><?= icon('tag', ['size'=>16]) ?> <?= htmlspecialchars(getCategoryName($ad['category'])) ?></span>
        </div>

        <?php if (!empty($specs)): ?>
        <div class="specs-grid">
            <?php foreach ($specs as $k => $v): if (empty($v)) continue; ?>
                <div class="spec-item">
                    <div class="spec-label"><?= htmlspecialchars(translateSpec($k)) ?></div>
                    <div class="spec-value"><?= htmlspecialchars((string)$v) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <h3 style="font-size:17px;font-weight:700;margin-bottom:10px;">الوصف</h3>
        <div class="ad-description"><?= nl2br(htmlspecialchars($ad['description'] ?? 'لا يوجد وصف')) ?></div>

        <div class="card">
            <div class="card-header"><div class="card-title">التعليقات والعروض</div></div>
            <div class="card-body">
                <div id="commentsList"><div class="skeleton" style="height:60px;margin-bottom:8px;"></div></div>
                <?php if ($me && !$isOwner): ?>
                <div style="margin-top:var(--sp-4);">
                    <textarea class="textarea" id="commentText" placeholder="اكتب تعليقًا أو عرض سعر..." rows="3"></textarea>
                    <div style="display:flex;gap:8px;margin-top:8px;align-items:center;">
                        <input type="number" class="input" id="offerAmount" placeholder="عرض سعر (اختياري)" style="width:200px;height:42px;">
                        <button class="btn btn-primary" onclick="addComment()">إرسال</button>
                    </div>
                </div>
                <?php elseif (!$me): ?>
                <p style="color:var(--muted);text-align:center;margin-top:var(--sp-4);"><a href="auth.php" style="color:var(--brand-600);font-weight:600;">سجل دخولك</a> لإضافة تعليق</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <aside>
        <div class="seller-card">
            <div class="seller-head">
                <div class="seller-avatar"><?= mb_substr($ad['seller_name'], 0, 1, 'UTF-8') ?></div>
                <div style="flex:1;">
                    <div class="seller-name">
                        <?= htmlspecialchars($ad['seller_name']) ?>
                        <?php if ($ad['phone_verified']): ?><span style="color:var(--success);" title="موثّق"><?= icon('check-circle', ['size'=>14]) ?></span><?php endif; ?>
                    </div>
                    <div class="seller-meta">
                        <?= icon('star', ['size'=>13]) ?> <?= number_format((float)$ad['seller_rating'], 1) ?> · عضو منذ <?= date('Y', strtotime($ad['seller_joined'])) ?>
                    </div>
                </div>
            </div>

            <a href="user.php?id=<?= $ad['userId'] ?>" class="btn btn-ghost btn-block btn-sm" style="margin-top:14px;">عرض الملف الشخصي</a>

            <div class="seller-actions">
                <?php if ($me && !$isOwner): ?>
                    <a href="https://wa.me/967<?= $ad['seller_phone'] ?>?text=<?= urlencode('مرحبا، أنا مهتم بإعلانك: ' . $ad['title']) ?>" target="_blank" class="btn btn-success"><?= icon('whatsapp', ['size'=>18]) ?> واتساب</a>
                    <button class="btn btn-primary" onclick="startChat()"><?= icon('message', ['size'=>18]) ?> محادثة خاصة</button>
                    <button class="btn btn-secondary" onclick="toggleFav()">
                        <?= icon('heart', ['size'=>18]) ?>
                        <span id="favText"><?= $isFav ? 'في المفضلة' : 'إضافة للمفضلة' ?></span>
                    </button>
                <?php elseif ($isOwner): ?>
                    <a href="post.php?edit=<?= $ad['id'] ?>" class="btn btn-primary"><?= icon('edit', ['size'=>16]) ?> تعديل الإعلان</a>
                    <button class="btn btn-gold" onclick="bumpAd()"><?= icon('trending-up', ['size'=>16]) ?> رفع الإعلان</button>
                    <button class="btn btn-secondary" onclick="markSold()"><?= icon('check', ['size'=>16]) ?> تم البيع</button>
                    <button class="btn btn-secondary" onclick="archiveAd()"><?= icon('archive', ['size'=>16]) ?> أرشفة</button>
                    <button class="btn btn-danger" onclick="deleteAd()"><?= icon('trash', ['size'=>16]) ?> حذف</button>
                <?php else: ?>
                    <a href="auth.php" class="btn btn-primary">سجل دخولك للتواصل</a>
                <?php endif; ?>

                <?php if ($me && !$isOwner): ?>
                <button class="btn btn-ghost" onclick="reportAd()" style="color:var(--danger);"><?= icon('flag', ['size'=>16]) ?> الإبلاغ عن الإعلان</button>
                <?php endif; ?>
            </div>

            <div style="margin-top:var(--sp-5);padding-top:var(--sp-4);border-top:1px solid var(--line-soft);">
                <div style="font-size:13px;font-weight:600;color:var(--text-soft);margin-bottom:10px;">شارك الإعلان</div>
                <div class="share-row">
                    <a href="https://wa.me/?text=<?= urlencode($ad['title'] . ' - ' . PAGE_URL) ?>" target="_blank" class="share-btn" style="color:#25D366;"><?= icon('whatsapp', ['size'=>16]) ?></a>
                    <a href="https://twitter.com/intent/tweet?url=<?= urlencode(PAGE_URL) ?>&text=<?= urlencode($ad['title']) ?>" target="_blank" class="share-btn" style="color:#1DA1F2;"><?= icon('twitter', ['size'=>16]) ?></a>
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode(PAGE_URL) ?>" target="_blank" class="share-btn" style="color:#1877F2;"><?= icon('facebook', ['size'=>16]) ?></a>
                    <a href="https://t.me/share/url?url=<?= urlencode(PAGE_URL) ?>&text=<?= urlencode($ad['title']) ?>" target="_blank" class="share-btn" style="color:#0088cc;"><?= icon('telegram', ['size'=>16]) ?></a>
                    <button onclick="copyLink()" class="share-btn"><?= icon('paperclip', ['size'=>16]) ?></button>
                </div>
            </div>

            <div style="margin-top:var(--sp-4);text-align:center;">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=<?= urlencode(PAGE_URL) ?>" alt="QR" style="border-radius:8px;border:1px solid var(--line);padding:6px;background:#fff;">
                <div style="font-size:11px;color:var(--muted);margin-top:6px;">مسح للمشاركة</div>
            </div>
        </div>
    </aside>
</div>

<script>
const AD_ID = <?= (int)$ad['id'] ?>;
const images = <?= json_encode($images) ?>;
let currentImg = 0;

function selectImage(i) {
    currentImg = i;
    const img = document.getElementById('mainImage');
    if (img) img.src = images[i];
    document.querySelectorAll('.gallery-thumbs img').forEach((el, idx) => el.classList.toggle('active', idx === i));
}
function navGallery(dir) { currentImg = (currentImg + dir + images.length) % images.length; selectImage(currentImg); }

async function toggleFav() {
    const res = await api('ads&action=toggle_favorite', { method: 'POST', data: { ad_id: AD_ID, adId: AD_ID } });
    if (res.success) {
        toast(res.is_favorite || res.data?.is_favorite ? 'تمت الإضافة للمفضلة' : 'تم الحذف من المفضلة', 'success');
        const txt = document.getElementById('favText');
        if (txt) txt.textContent = (res.is_favorite || res.data?.is_favorite) ? 'في المفضلة' : 'إضافة للمفضلة';
    } else toast(res.message, 'error');
}
async function startChat() {
    const msg = prompt('اكتب أول رسالة:', 'مرحبا، أنا مهتم بإعلانك');
    if (!msg) return;
    const res = await api('chat&action=send', { method: 'POST', data: { ad_id: AD_ID, adId: AD_ID, message: msg, body: msg } });
    if (res.success) location.href = 'messages.php?thread=' + (res.threadId || res.data?.threadId || res.thread_id || '');
    else toast(res.message, 'error');
}
function copyLink() { navigator.clipboard.writeText(location.href).then(() => toast('تم نسخ الرابط', 'success')); }
async function bumpAd() {
    confirmModal('رفع الإعلان', 'سيظهر إعلانك في المقدمة. متاح مرة كل 24 ساعة.', async () => {
        const res = await api('ads&action=bump', { method: 'POST', data: { ad_id: AD_ID, adId: AD_ID } });
        toast(res.message, res.success ? 'success' : 'error');
    }, 'رفع الآن', 'btn-primary');
}
async function markSold() {
    confirmModal('تأكيد البيع', 'سيتم تغيير حالة الإعلان إلى "مباع".', async () => {
        const res = await api('ads&action=mark_sold', { method: 'POST', data: { ad_id: AD_ID, adId: AD_ID } });
        toast(res.message, res.success ? 'success' : 'error');
        if (res.success) setTimeout(() => location.reload(), 800);
    }, 'تأكيد', 'btn-success');
}
async function archiveAd() {
    confirmModal('أرشفة الإعلان', 'سيتم إخفاء الإعلان مؤقتاً. يمكنك استرجاعه لاحقاً.', async () => {
        const res = await api('ads&action=archive', { method: 'POST', data: { ad_id: AD_ID, adId: AD_ID } });
        toast(res.message, res.success ? 'success' : 'error');
        if (res.success) setTimeout(() => location.href = 'my_ads.php', 800);
    });
}
async function deleteAd() {
    confirmModal('حذف الإعلان', 'لا يمكن التراجع. هل أنت متأكد؟', async () => {
        const res = await api('ads&action=delete', { method: 'POST', data: { ad_id: AD_ID, adId: AD_ID } });
        toast(res.message, res.success ? 'success' : 'error');
        if (res.success) setTimeout(() => location.href = 'my_ads.php', 800);
    });
}
async function reportAd() {
    const reason = prompt('سبب الإبلاغ:');
    if (!reason || reason.length < 5) return toast('السبب قصير جداً', 'warning');
    const res = await api('reports&action=create', { method: 'POST', data: { ad_id: AD_ID, adId: AD_ID, reason, body: reason } });
    toast(res.message, res.success ? 'success' : 'error');
}
async function loadComments() {
    const res = await api('ads&action=comments&ad_id=' + AD_ID);
    const list = document.getElementById('commentsList');
    const items = res.comments || res.data?.comments || [];
    if (!items.length) { list.innerHTML = '<p style="color:var(--muted);text-align:center;padding:20px;">لا توجد تعليقات بعد. كن أول من يعلق!</p>'; return; }
    list.innerHTML = items.map(c => `<div style="padding:12px 0;border-bottom:1px solid var(--line-soft);"><div style="display:flex;justify-content:space-between;margin-bottom:6px;"><strong style="font-size:13px;">${escapeHtml(c.user_name || c.userName || '')}</strong><span style="font-size:12px;color:var(--muted);">${fmtDate(c.created_at || c.createdAt)}</span></div><p style="font-size:14px;line-height:1.6;color:var(--text-soft);">${escapeHtml(c.body || c.content || '')}</p>${c.offer_amount || c.offerAmount ? `<div style="display:inline-block;margin-top:8px;padding:4px 12px;border-radius:20px;background:rgba(214,179,93,.15);color:var(--gold-600);font-weight:700;font-size:13px;">عرض: ${fmtPrice(c.offer_amount || c.offerAmount)}</div>` : ''}</div>`).join('');
}
async function addComment() {
    const body = document.getElementById('commentText').value.trim();
    const offer = document.getElementById('offerAmount').value;
    if (body.length < 2) return toast('التعليق قصير', 'warning');
    const res = await api('ads&action=add_comment', { method: 'POST', data: { ad_id: AD_ID, adId: AD_ID, body, content: body, offer_amount: offer, offerAmount: offer } });
    if (res.success) { document.getElementById('commentText').value = ''; document.getElementById('offerAmount').value = ''; toast('تم إضافة التعليق', 'success'); loadComments(); }
    else toast(res.message, 'error');
}
loadComments();
</script>

<?php
function translateSpec($key) {
    static $tr = ['brand'=>'الماركة','model'=>'الموديل','year'=>'سنة الصنع','mileage'=>'العداد','transmission'=>'ناقل الحركة','fuel'=>'الوقود','color'=>'اللون','property_type'=>'نوع العقار','rooms'=>'الغرف','bathrooms'=>'الحمامات','area'=>'المساحة','floor'=>'الطابق','condition'=>'الحالة','warranty'=>'الضمان','storage'=>'السعة','ram'=>'الذاكرة'];
    return $tr[$key] ?? $key;
}
require __DIR__ . '/includes/footer.php';
?>
