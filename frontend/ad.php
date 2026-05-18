<?php
require_once __DIR__ . '/../config.php';
$adId = (int)($_GET['id'] ?? 0);

// SEO: جلب بيانات أساسية للـ meta tags قبل الـ header
$adData = null;
if ($adId > 0) {
    try {
        $db = getDBConnection();
        $stmt = $db->prepare("SELECT a.id, a.title, a.slug, a.description, a.images, a.category, a.price, a.city, u.name AS userName FROM ads a JOIN users u ON a.userId = u.id WHERE a.id = ? AND a.status != 'deleted' LIMIT 1");
        $stmt->execute([$adId]);
        $adData = $stmt->fetch();
    } catch(Exception $e) {}
}

if ($adData) {
    define('PAGE_TITLE', $adData['title'] . ' - ' . SITE_NAME);
    define('PAGE_DESC', mb_substr(strip_tags($adData['description']), 0, 160));
    define('PAGE_OG_IMAGE', firstImage($adData['images'], $adData['category']));
    define('PAGE_URL', APP_URL . '/frontend/ad.php?id=' . $adId . ($adData['slug'] ? '&slug=' . urlencode($adData['slug']) : ''));
} else {
    define('PAGE_TITLE', 'إعلان غير موجود - ' . SITE_NAME);
}

include __DIR__ . '/includes/header.php';
?>

<?php if ($adData): ?>
<!-- Schema.org Product -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Product",
  "name": <?= json_encode($adData['title'], JSON_UNESCAPED_UNICODE) ?>,
  "description": <?= json_encode(mb_substr(strip_tags($adData['description']), 0, 500), JSON_UNESCAPED_UNICODE) ?>,
  "image": <?= json_encode(firstImage($adData['images'], $adData['category']), JSON_UNESCAPED_UNICODE) ?>,
  "category": <?= json_encode(getCategoryName($adData['category']), JSON_UNESCAPED_UNICODE) ?>,
  "offers": {
    "@type": "Offer",
    "price": <?= (float)$adData['price'] ?: 0 ?>,
    "priceCurrency": "YER",
    "availability": "https://schema.org/InStock",
    "areaServed": <?= json_encode($adData['city'], JSON_UNESCAPED_UNICODE) ?>,
    "seller": {
      "@type": "Person",
      "name": <?= json_encode($adData['userName'], JSON_UNESCAPED_UNICODE) ?>
    }
  }
}
</script>
<?php endif; ?>

<style>
.ad-page-container {
    max-width: var(--container-max);
    margin: 1.25rem auto;
    padding: 0 1rem;
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 1.25rem;
}
@media (max-width: 992px) {
    .ad-page-container { grid-template-columns: 1fr; }
}
.breadcrumbs {
    font-size: 0.82rem;
    color: var(--text-muted);
    margin-bottom: 0.75rem;
    font-weight: 700;
}
.breadcrumbs a { color: var(--primary); text-decoration: none; }
.breadcrumbs a:hover { text-decoration: underline; }

.ad-title-area { margin-bottom: 0.9rem; }
.ad-title { font-size: 1.6rem; font-weight: 900; color: var(--text-main); margin: 0 0 0.5rem; line-height: 1.3; }
.ad-meta-bar {
    display: flex; flex-wrap: wrap; gap: 0.8rem; font-size: 0.8rem; color: var(--text-muted);
    font-weight: 700; padding-bottom: 0.75rem; border-bottom: 1px solid var(--border-color);
}

.gallery-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: var(--radius-lg); overflow: hidden; margin-bottom: 1.25rem; box-shadow: var(--shadow-sm); }
.gallery-main {
    aspect-ratio: 16/10; background: var(--bg-color); position: relative;
    display: flex; align-items: center; justify-content: center; overflow: hidden;
}
.gallery-main img { width: 100%; height: 100%; object-fit: contain; cursor: zoom-in; }
.gallery-nav {
    position: absolute; top: 50%; transform: translateY(-50%);
    background: rgba(0,0,0,0.5); color: white; border: none;
    width: 40px; height: 40px; border-radius: 50%; cursor: pointer;
    font-size: 1.2rem; backdrop-filter: blur(4px);
}
.gallery-nav.prev { right: 12px; }
.gallery-nav.next { left: 12px; }
.gallery-counter {
    position: absolute; top: 12px; right: 12px;
    background: rgba(0,0,0,0.6); color: white;
    padding: 4px 10px; border-radius: var(--radius-full);
    font-size: 0.75rem; font-weight: 700;
}
.thumbnail-row {
    display: flex; gap: 6px; padding: 8px;
    overflow-x: auto; background: var(--bg-color);
    border-top: 1px solid var(--border-color);
}
.thumbnail-row img {
    width: 64px; height: 64px; object-fit: cover;
    border-radius: var(--radius-sm); cursor: pointer;
    border: 2px solid transparent; flex-shrink: 0;
    transition: var(--transition);
}
.thumbnail-row img:hover { border-color: var(--accent); }
.thumbnail-row img.active { border-color: var(--primary); }

.section-block {
    background: var(--card-bg); border: 1px solid var(--border-color);
    border-radius: var(--radius-lg); padding: 1.25rem; margin-bottom: 1rem;
    box-shadow: var(--shadow-xs);
}
.section-block h3 {
    margin: 0 0 0.85rem; font-size: 1.05rem; font-weight: 900;
    color: var(--primary); padding-bottom: 0.5rem; border-bottom: 2px solid var(--accent);
    display: flex; align-items: center; gap: 6px;
}

.specs-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 0.6rem;
}
.spec-item {
    background: var(--bg-color); padding: 0.65rem 0.9rem;
    border-radius: var(--radius-md); border: 1px solid var(--border-color);
}
.spec-item .label { font-size: 0.72rem; color: var(--text-muted); font-weight: 700; }
.spec-item .val { font-size: 0.9rem; color: var(--primary); font-weight: 900; margin-top: 2px; }

.ad-actions-bar {
    display: flex; gap: 0.5rem; flex-wrap: wrap;
    padding: 0.75rem 0; margin-bottom: 0.5rem;
}
.action-btn {
    background: var(--bg-color); border: 1px solid var(--border-color);
    padding: 0.5rem 0.85rem; border-radius: var(--radius-full);
    font-size: 0.82rem; font-weight: 800; cursor: pointer;
    color: var(--text-main); transition: var(--transition);
    display: inline-flex; align-items: center; gap: 5px;
    text-decoration: none;
}
.action-btn:hover { border-color: var(--primary); color: var(--primary); transform: translateY(-1px); }
.action-btn.danger:hover { border-color: var(--danger); color: var(--danger); }
.action-btn.favorited { background: rgba(225,29,72,0.1); color: var(--danger); border-color: var(--danger); }

.share-buttons {
    display: flex; gap: 6px; padding: 8px 0;
}
.share-btn {
    width: 36px; height: 36px; border-radius: 50%;
    display: inline-flex; align-items: center; justify-content: center;
    color: white; font-size: 0.95rem; cursor: pointer; border: none;
    transition: var(--transition);
}
.share-btn:hover { transform: scale(1.1); }
.share-btn.whatsapp { background: #25D366; }
.share-btn.twitter { background: #1DA1F2; }
.share-btn.facebook { background: #1877F2; }
.share-btn.telegram { background: #0088CC; }
.share-btn.copy { background: var(--text-muted); }

/* Seller Sidebar */
.sidebar-block { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 1.25rem; margin-bottom: 1rem; box-shadow: var(--shadow-xs); position: sticky; top: calc(var(--header-height) + 12px); }
.seller-card { text-align: center; }
.seller-avatar { width: 76px; height: 76px; border-radius: 50%; margin: 0 auto 0.6rem; border: 3px solid var(--accent); overflow: hidden; }
.seller-avatar img { width:100%; height:100%; object-fit: cover; }
.seller-name { font-weight: 900; font-size: 1.05rem; margin-bottom: 4px; color: var(--text-main); }
.seller-info { font-size: 0.78rem; color: var(--text-muted); margin-bottom: 0.85rem; }

.price-block { background: linear-gradient(135deg, rgba(13,148,136,0.08), rgba(15,41,66,0.05)); padding: 1.25rem; border-radius: var(--radius-lg); text-align: center; border: 2px solid rgba(13,148,136,0.2); margin-bottom: 1rem; }
.price-value { font-size: 1.85rem; font-weight: 900; color: var(--secondary); margin-bottom: 4px; }

.warning-card { background: rgba(245,158,11,0.08); border: 1px solid rgba(245,158,11,0.3); border-radius: var(--radius-md); padding: 0.85rem; color: #92400E; font-size: 0.78rem; font-weight: 700; line-height: 1.6; }

.comment-box {
    background: var(--bg-color); border: 1px solid var(--border-color);
    border-radius: var(--radius-md); padding: 0.85rem; margin-bottom: 0.6rem;
}
.comment-box.offer-box { background: rgba(245,158,11,0.05); border-color: rgba(245,158,11,0.3); position: relative; }
.comment-box.offer-box::before { content:'💸 عرض'; position: absolute; top:6px; left:8px; background: var(--warning); color: white; padding: 2px 8px; border-radius: var(--radius-full); font-size: 0.65rem; font-weight: 900; }
.comment-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem; font-size: 0.8rem; }
.comment-user { font-weight: 900; color: var(--primary); }
.comment-date { color: var(--text-muted); font-weight: 600; }
.comment-body { font-size: 0.88rem; color: var(--text-main); }

.related-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px,1fr)); gap: 0.75rem; }
.related-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: var(--radius-md); overflow: hidden; text-decoration: none; color: var(--text-main); transition: var(--transition); }
.related-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); border-color: var(--accent); }
.related-card img { width: 100%; aspect-ratio: 4/3; object-fit: cover; }
.related-card .title { padding: 0.5rem 0.7rem 0.25rem; font-size: 0.82rem; font-weight: 800; overflow:hidden; text-overflow: ellipsis; white-space: nowrap; }
.related-card .price { padding: 0 0.7rem 0.5rem; font-size: 0.78rem; font-weight: 800; color: var(--secondary); }

#lightbox { position: fixed; inset: 0; background: rgba(0,0,0,0.95); z-index: 10000; display: none; align-items: center; justify-content: center; cursor: zoom-out; }
#lightbox.show { display: flex; }
#lightbox img { max-width: 95vw; max-height: 95vh; object-fit: contain; }
#lightbox .close { position: absolute; top: 20px; left: 20px; background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.3); width: 44px; height: 44px; border-radius: 50%; cursor: pointer; font-size: 1.2rem; }
</style>

<?php if (!$adData): ?>
<div class="container">
    <div class="premium-card" style="text-align:center; padding:4rem 1rem;">
        <div style="font-size:5rem; opacity:0.3;">❌</div>
        <h2>الإعلان غير موجود</h2>
        <p style="color:var(--text-muted);">قد يكون الإعلان قد حُذف أو الرابط غير صحيح</p>
        <a href="index.php" class="btn-primary">← العودة للصفحة الرئيسية</a>
    </div>
</div>
<?php else: ?>

<div class="ad-page-container">
    <!-- Main Column -->
    <div class="animate-fade-in">
        <div class="breadcrumbs">
            <a href="index.php">الرئيسية</a> ›
            <a href="index.php?cat=<?= htmlspecialchars($adData['category']) ?>"><?= getCategoryName($adData['category']) ?></a> ›
            <span><?= htmlspecialchars(mb_strimwidth($adData['title'], 0, 50, '...')) ?></span>
        </div>

        <div class="ad-title-area">
            <h1 class="ad-title" id="ad-title-text"><?= htmlspecialchars($adData['title']) ?></h1>
            <div class="ad-meta-bar">
                <span id="meta-city">📍 <?= htmlspecialchars($adData['city']) ?></span>
                <span id="meta-author">👤 <?= htmlspecialchars($adData['userName']) ?></span>
                <span id="meta-date">⏱️</span>
                <span id="meta-views">👁️</span>
                <span id="meta-id">🔢 #<?= $adId ?></span>
            </div>
        </div>

        <!-- Actions -->
        <div class="ad-actions-bar">
            <button onclick="toggleFavorite()" id="fav-btn" class="action-btn">🤍 إضافة للمفضلة</button>

            <button onclick="openReportModal()" class="action-btn danger">🚩 إبلاغ</button>

            <button onclick="openShareModal()" class="action-btn">📤 مشاركة</button>

            <button onclick="openQrModal()" class="action-btn">📱 QR</button>

            <div id="owner-actions" style="display:none; gap:6px; flex-wrap:wrap;">
                <a id="edit-link" class="action-btn" style="background:var(--primary); color:white;">✏️ تعديل</a>
                <button onclick="bumpAd()" class="action-btn">🔥 تجديد</button>
                <button onclick="markStatus('sold')" class="action-btn">✓ تم البيع</button>
                <button onclick="markStatus('archived')" class="action-btn">📦 أرشفة</button>
                <button onclick="deleteAd()" class="action-btn danger">🗑️ حذف</button>
            </div>
        </div>

        <!-- Gallery -->
        <div class="gallery-card">
            <div class="gallery-main" id="gallery-main">
                <img id="main-image" src="" alt="">
                <button class="gallery-nav prev" onclick="navGallery(-1)">›</button>
                <button class="gallery-nav next" onclick="navGallery(1)">‹</button>
                <div class="gallery-counter" id="gallery-counter">1 / 1</div>
            </div>
            <div class="thumbnail-row" id="thumbnail-row"></div>
        </div>

        <!-- Description -->
        <div class="section-block">
            <h3>📄 وصف الإعلان</h3>
            <div id="ad-description" style="white-space: pre-wrap; line-height: 1.8;"></div>
        </div>

        <!-- Specs -->
        <div class="section-block" id="specs-block" style="display:none;">
            <h3>📋 المواصفات</h3>
            <div class="specs-grid" id="specs-grid"></div>
        </div>

        <!-- Location -->
        <div class="section-block" id="location-block" style="display:none;">
            <h3>📍 الموقع</h3>
            <div id="location-name" style="margin-bottom: 0.75rem; font-weight: 700;"></div>
            <div id="map-container" style="aspect-ratio: 16/9; border-radius: var(--radius-md); overflow: hidden; border: 1px solid var(--border-color);"></div>
            <a id="map-link" target="_blank" class="btn-outline btn-sm" style="margin-top: 0.5rem;">🗺️ افتح في الخريطة</a>
        </div>

        <!-- Comments / Offers -->
        <div class="section-block">
            <h3>💬 التعليقات والعروض (<span id="comments-count">0</span>)</h3>

            <div id="comments-list" style="max-height: 400px; overflow-y: auto; padding-left: 4px;">
                <div style="text-align:center; padding:1rem; color:var(--text-muted);">لا توجد تعليقات بعد. كن أول من يعلق!</div>
            </div>

            <?php if (isset($_SESSION['user_id'])): ?>
            <form id="comment-form" onsubmit="submitComment(event)" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px dashed var(--border-color);">
                <div style="display:flex; gap:6px; margin-bottom: 0.5rem;">
                    <button type="button" id="tab-comment" class="action-btn" style="flex:1; background:var(--primary); color:white; border-color:var(--primary);" onclick="setCommentMode('comment')">💬 استفسار</button>
                    <button type="button" id="tab-offer" class="action-btn" style="flex:1;" onclick="setCommentMode('offer')">💸 تقديم عرض سعر</button>
                </div>
                <input type="text" id="comment-text" placeholder="اكتب تعليقك هنا..." required maxlength="1000">
                <input type="number" id="comment-offer-amount" class="hidden" placeholder="مبلغ العرض بالريال" min="1">
                <button type="submit" class="btn-primary btn-sm" style="margin-top: 0.5rem;">📤 إرسال</button>
            </form>
            <?php else: ?>
            <div style="text-align:center; padding: 1rem; background: var(--bg-color); border-radius: var(--radius-md); margin-top: 1rem;">
                <a href="auth.php" class="btn-primary btn-sm">سجّل دخول للتعليق</a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Related -->
        <div class="section-block" id="related-block" style="display:none;">
            <h3>🔥 إعلانات مشابهة</h3>
            <div class="related-grid" id="related-list"></div>
        </div>
    </div>

    <!-- Sidebar -->
    <aside>
        <div class="sidebar-block">
            <div class="price-block">
                <div style="font-size:0.78rem; color:var(--text-muted); font-weight:800;">السعر</div>
                <div class="price-value" id="ad-price">...</div>
                <small id="price-note" style="color:var(--text-muted);"></small>
            </div>

            <div class="seller-card">
                <div class="seller-avatar"><img id="seller-avatar" src="" alt=""></div>
                <div class="seller-name" id="seller-name">
                    <a id="seller-link" style="color: inherit;"></a>
                    <span id="seller-verified" style="display:none; font-size:0.7rem; background:var(--success); color:white; padding:2px 6px; border-radius:var(--radius-full); margin-right:4px;">✓ موثق</span>
                </div>
                <div class="seller-info">
                    <span id="seller-rating"></span><br>
                    <span style="font-size:0.72rem;">انضم في <span id="seller-joined"></span></span>
                </div>

                <?php if (isset($_SESSION['user_id'])): ?>
                <button onclick="startChat()" class="btn-chat" style="margin-bottom: 0.5rem;">💬 محادثة خاصة</button>
                <a id="whatsapp-link" class="btn-whatsapp" target="_blank">📱 تواصل عبر واتساب</a>
                <?php else: ?>
                <a href="auth.php" class="btn-chat" style="margin-bottom: 0.5rem;">🔐 سجّل لمراسلة البائع</a>
                <?php endif; ?>
            </div>

            <div class="warning-card" style="margin-top: 1rem;">
                ⚠️ <strong>نصيحة:</strong> لا ترسل المال قبل استلام السلعة. التقِ بالبائع في مكان عام آمن. أبلغ عن أي إعلان مشبوه.
            </div>
        </div>
    </aside>
</div>

<!-- Lightbox -->
<div id="lightbox" onclick="closeLightbox(event)">
    <button class="close" onclick="closeLightbox(event)">×</button>
    <img id="lightbox-img" src="" alt="">
</div>

<script src="assets/js/app.js"></script>
<script>
let ad = null;
let currentImage = 0;
let commentMode = 'comment';
const AD_ID = <?= $adId ?>;
const ME_ID = <?= isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0 ?>;

async function loadAd() {
    try {
        const r = await apiRequest(`ads&id=${AD_ID}`);
        ad = r.data;

        document.getElementById('ad-title-text').textContent = ad.title;
        document.getElementById('meta-city').textContent = '📍 ' + ad.city;
        document.getElementById('meta-author').innerHTML = `👤 <a href="user.php?id=${ad.userId}" style="color:inherit; text-decoration:none;">${escapeHtml(ad.userName)}</a>`;
        document.getElementById('meta-date').textContent = '⏱️ ' + ad.formattedDate;
        document.getElementById('meta-views').textContent = '👁️ ' + ad.views + ' مشاهدة';

        document.getElementById('ad-price').textContent = ad.price;
        if (ad.priceRaw <= 0) document.getElementById('price-note').textContent = 'تواصل مع البائع للتفاوض';

        // Description
        document.getElementById('ad-description').textContent = ad.description;

        // Gallery
        renderGallery(ad.images);

        // Specs
        if (ad.specifications && Object.keys(ad.specifications).length) {
            document.getElementById('specs-block').style.display = 'block';
            const grid = document.getElementById('specs-grid');
            grid.innerHTML = '';
            Object.entries(ad.specifications).forEach(([k, v]) => {
                grid.insertAdjacentHTML('beforeend', `<div class="spec-item"><div class="label">${escapeHtml(k)}</div><div class="val">${escapeHtml(v)}</div></div>`);
            });
        }

        // Seller
        document.getElementById('seller-avatar').src = ad.userAvatar;
        document.getElementById('seller-link').textContent = ad.userName;
        document.getElementById('seller-link').href = 'user.php?id=' + ad.userId;
        document.getElementById('seller-rating').innerHTML = getStarsHTML(ad.userRating) + ' (' + parseFloat(ad.userRating).toFixed(1) + ')';
        document.getElementById('seller-joined').textContent = ad.userJoined;
        if (ad.userVerified) document.getElementById('seller-verified').style.display = 'inline-block';

        const phone = (ad.userPhone || '').replace(/[^0-9]/g, '');
        if (phone) {
            const wa = document.getElementById('whatsapp-link');
            if (wa) wa.href = `https://wa.me/967${phone}?text=${encodeURIComponent('السلام عليكم، بخصوص إعلانك (' + ad.title + ') في حراج اليمن')}`;
        }

        // Favorite state
        if (ad.isFavorited) {
            const btn = document.getElementById('fav-btn');
            btn.innerHTML = '❤️ في المفضلة';
            btn.classList.add('favorited');
        }

        // Owner actions
        if (ad.isOwner) {
            const oa = document.getElementById('owner-actions');
            oa.style.display = 'inline-flex';
            document.getElementById('edit-link').href = `post.php?edit=${ad.id}`;
        }

        // Location & Map
        if (ad.latitude && ad.longitude) {
            document.getElementById('location-block').style.display = 'block';
            document.getElementById('location-name').textContent = ad.locationName || `إحداثيات: ${ad.latitude}, ${ad.longitude}`;
            const mapUrl = `https://www.openstreetmap.org/export/embed.html?bbox=${ad.longitude-0.005}%2C${ad.latitude-0.003}%2C${parseFloat(ad.longitude)+0.005}%2C${parseFloat(ad.latitude)+0.003}&layer=mapnik&marker=${ad.latitude}%2C${ad.longitude}`;
            document.getElementById('map-container').innerHTML = `<iframe src="${mapUrl}" style="width:100%; height:100%; border:0;" loading="lazy"></iframe>`;
            document.getElementById('map-link').href = `https://www.openstreetmap.org/?mlat=${ad.latitude}&mlon=${ad.longitude}#map=16/${ad.latitude}/${ad.longitude}`;
        }

        // Comments
        renderComments(ad.comments);

        // Related
        if (ad.related && ad.related.length) {
            document.getElementById('related-block').style.display = 'block';
            const rl = document.getElementById('related-list');
            rl.innerHTML = ad.related.map(r => `
                <a href="ad.php?id=${r.id}${r.slug ? '&slug='+encodeURIComponent(r.slug) : ''}" class="related-card">
                    <img src="${r.image}" alt="${escapeHtml(r.title)}" loading="lazy">
                    <div class="title">${escapeHtml(r.title)}</div>
                    <div class="price">${r.price}</div>
                </a>
            `).join('');
        }
    } catch (e) {
        document.body.innerHTML = '<div style="text-align:center; padding:5rem; font-family:Cairo;"><h2>الإعلان غير موجود</h2><a href="index.php">العودة</a></div>';
    }
}

function renderGallery(images) {
    const main = document.getElementById('main-image');
    const counter = document.getElementById('gallery-counter');
    const thumbs = document.getElementById('thumbnail-row');
    main.src = images[0];
    main.alt = ad.title;
    main.onclick = () => openLightbox(images[currentImage]);
    counter.textContent = `1 / ${images.length}`;

    thumbs.innerHTML = '';
    images.forEach((img, i) => {
        const t = document.createElement('img');
        t.src = img; t.alt = '';
        if (i === 0) t.classList.add('active');
        t.onclick = () => goToImage(i);
        thumbs.appendChild(t);
    });

    if (images.length < 2) {
        document.querySelectorAll('.gallery-nav').forEach(n => n.style.display = 'none');
    }
}

function navGallery(dir) {
    const total = ad.images.length;
    currentImage = (currentImage + dir + total) % total;
    goToImage(currentImage);
}

function goToImage(idx) {
    currentImage = idx;
    document.getElementById('main-image').src = ad.images[idx];
    document.getElementById('gallery-counter').textContent = `${idx + 1} / ${ad.images.length}`;
    document.querySelectorAll('#thumbnail-row img').forEach((t, i) => t.classList.toggle('active', i === idx));
}

function openLightbox(src) {
    document.getElementById('lightbox-img').src = src;
    document.getElementById('lightbox').classList.add('show');
}
function closeLightbox(e) { if (e.target.id === 'lightbox' || e.target.classList.contains('close')) document.getElementById('lightbox').classList.remove('show'); }

function renderComments(comments) {
    const list = document.getElementById('comments-list');
    document.getElementById('comments-count').textContent = comments.length;
    if (!comments.length) {
        list.innerHTML = '<div style="text-align:center; padding:1rem; color:var(--text-muted);">لا توجد تعليقات بعد.</div>';
        return;
    }
    list.innerHTML = comments.map(c => `
        <div class="comment-box ${c.type === 'offer' ? 'offer-box' : ''}">
            <div class="comment-header">
                <span class="comment-user">${escapeHtml(c.username)}</span>
                <span class="comment-date">${escapeHtml(c.date)}</span>
            </div>
            <div class="comment-body">
                ${escapeHtml(c.content)}
                ${c.offerAmount ? `<div style="margin-top:6px; color:var(--warning); font-weight:900;">💰 العرض: ${c.offerAmount}</div>` : ''}
            </div>
        </div>
    `).join('');
}

function setCommentMode(mode) {
    commentMode = mode;
    const text = document.getElementById('comment-text');
    const offer = document.getElementById('comment-offer-amount');
    const tabC = document.getElementById('tab-comment');
    const tabO = document.getElementById('tab-offer');

    if (mode === 'offer') {
        text.placeholder = 'علّق على عرضك (اختياري)...';
        text.required = false;
        offer.classList.remove('hidden');
        offer.required = true;
        tabC.style.background = 'var(--bg-color)'; tabC.style.color = 'var(--text-main)'; tabC.style.borderColor = 'var(--border-color)';
        tabO.style.background = 'var(--warning)'; tabO.style.color = 'white'; tabO.style.borderColor = 'var(--warning)';
    } else {
        text.placeholder = 'اكتب تعليقك هنا...';
        text.required = true;
        offer.classList.add('hidden');
        offer.required = false;
        tabC.style.background = 'var(--primary)'; tabC.style.color = 'white'; tabC.style.borderColor = 'var(--primary)';
        tabO.style.background = 'var(--bg-color)'; tabO.style.color = 'var(--text-main)'; tabO.style.borderColor = 'var(--border-color)';
    }
}

async function submitComment(e) {
    e.preventDefault();
    const text = document.getElementById('comment-text').value;
    const offer = document.getElementById('comment-offer-amount').value;
    try {
        await apiRequest('ads&action=add_comment', 'POST', {
            ad_id: AD_ID, content: text, type: commentMode,
            offer_amount: commentMode === 'offer' ? offer : null
        });
        showToast(commentMode === 'offer' ? '💸 تم إرسال عرضك' : 'تم نشر تعليقك ✓', 'success');
        document.getElementById('comment-text').value = '';
        document.getElementById('comment-offer-amount').value = '';
        loadAd(); // reload
    } catch (e) {}
}

async function toggleFavorite() {
    if (!ME_ID) { window.location.href = 'auth.php'; return; }
    try {
        const r = await apiRequest('ads&action=toggle_favorite', 'POST', { ad_id: AD_ID });
        const btn = document.getElementById('fav-btn');
        if (r.data.is_favorite) {
            btn.innerHTML = '❤️ في المفضلة';
            btn.classList.add('favorited');
        } else {
            btn.innerHTML = '🤍 إضافة للمفضلة';
            btn.classList.remove('favorited');
        }
    } catch (e) {}
}

async function startChat() {
    try {
        const r = await apiRequest('chat&action=send', 'POST', { ad_id: AD_ID, text: 'مرحباً، أنا مهتم بإعلانك' });
        window.location.href = 'messages.php?thread=' + r.data.threadId;
    } catch (e) {
        if (e.message?.includes('تسجيل')) window.location.href = 'auth.php';
    }
}

async function bumpAd() {
    if (!await confirmModal('سيتم رفع إعلانك إلى أعلى القائمة. متاح مرة كل 24 ساعة.', 'تجديد الإعلان')) return;
    try {
        await apiRequest('ads&action=bump', 'POST', { id: AD_ID });
        showToast('🔥 تم تجديد الإعلان!', 'success');
    } catch (e) {}
}

async function markStatus(status) {
    const labels = {sold:'تم البيع', archived:'الأرشفة', active:'تفعيل الإعلان'};
    if (!await confirmModal(`هل أنت متأكد من ${labels[status]}؟`, 'تأكيد')) return;
    try {
        await apiRequest('ads&action=change_status', 'POST', { id: AD_ID, status });
        showToast('تم تحديث الحالة', 'success');
        setTimeout(() => location.reload(), 800);
    } catch (e) {}
}

async function deleteAd() {
    if (!await confirmModal('سيتم حذف الإعلان نهائياً. هل أنت متأكد؟', 'حذف الإعلان')) return;
    try {
        await apiRequest('ads&action=delete', 'POST', { id: AD_ID });
        showToast('🗑️ تم حذف الإعلان', 'success');
        setTimeout(() => window.location.href = 'my_ads.php', 800);
    } catch (e) {}
}

function openReportModal() {
    if (!ME_ID) { window.location.href = 'auth.php'; return; }
    openModal(`
        <div class="modal-header">
            <h3>🚩 إبلاغ عن الإعلان</h3>
            <button class="modal-close" onclick="closeModal()">×</button>
        </div>
        <form id="report-form" class="modal-body" onsubmit="submitReport(event)">
            <div class="form-group">
                <label>سبب البلاغ</label>
                <select id="report-reason" required>
                    <option value="">-- اختر --</option>
                    <option value="إعلان مكرر">إعلان مكرر</option>
                    <option value="محتوى مسيء">محتوى مسيء</option>
                    <option value="محتوى احتيالي/نصب">محتوى احتيالي / نصب</option>
                    <option value="سلعة مزورة">سلعة مزورة</option>
                    <option value="معلومات كاذبة">معلومات كاذبة</option>
                    <option value="إعلان قديم/منتهي">إعلان قديم / منتهي</option>
                    <option value="أخرى">أخرى</option>
                </select>
            </div>
            <div class="form-group">
                <label>تفاصيل إضافية</label>
                <textarea id="report-details" rows="3" placeholder="اشرح السبب بمزيد من التفصيل..."></textarea>
            </div>
            <div class="modal-footer" style="margin-top:1rem;">
                <button type="button" class="btn-outline" onclick="closeModal()">إلغاء</button>
                <button type="submit" class="btn-danger">🚩 إرسال البلاغ</button>
            </div>
        </form>
    `);
}

async function submitReport(e) {
    e.preventDefault();
    try {
        await apiRequest('reports&action=submit', 'POST', {
            ad_id: AD_ID,
            reason: document.getElementById('report-reason').value,
            details: document.getElementById('report-details').value
        });
        showToast('✓ تم إرسال البلاغ', 'success');
        closeModal();
    } catch (e) {}
}

function openShareModal() {
    const url = window.location.href;
    const text = ad ? ad.title : 'إعلان من حراج اليمن';
    openModal(`
        <div class="modal-header">
            <h3>📤 مشاركة الإعلان</h3>
            <button class="modal-close" onclick="closeModal()">×</button>
        </div>
        <div class="modal-body">
            <div class="share-buttons" style="justify-content:center; margin-bottom: 1rem;">
                <button class="share-btn whatsapp" onclick="shareWhatsApp('${escapeHtml(text)}', '${url}')" title="واتساب">📱</button>
                <button class="share-btn twitter" onclick="shareTwitter('${escapeHtml(text)}', '${url}')" title="X/Twitter">🐦</button>
                <button class="share-btn facebook" onclick="shareFacebook('${url}')" title="فيسبوك">📘</button>
                <button class="share-btn telegram" onclick="shareTelegram('${escapeHtml(text)}', '${url}')" title="تيليجرام">✈️</button>
                <button class="share-btn copy" onclick="copyToClipboard('${url}')" title="نسخ الرابط">📋</button>
            </div>
            <div style="background: var(--bg-color); padding: 0.75rem; border-radius: var(--radius-md); font-size: 0.82rem; word-break: break-all; color: var(--text-muted);">${url}</div>
        </div>
    `);
}

function openQrModal() {
    const url = window.location.href;
    const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(url)}`;
    openModal(`
        <div class="modal-header">
            <h3>📱 رمز QR</h3>
            <button class="modal-close" onclick="closeModal()">×</button>
        </div>
        <div class="modal-body" style="text-align:center;">
            <img src="${qrUrl}" alt="QR Code" style="max-width:300px; margin:0 auto; border:8px solid white; border-radius:var(--radius-md); box-shadow:var(--shadow-md);">
            <p style="color: var(--text-muted); margin: 1rem 0 0; font-size: 0.85rem;">امسح الرمز لفتح الإعلان على هاتفك</p>
        </div>
    `);
}

document.addEventListener('DOMContentLoaded', loadAd);
</script>
<?php endif; ?>
</body>
</html>
