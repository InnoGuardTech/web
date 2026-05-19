<?php
require_once __DIR__ . '/../backend/config.php';
if (function_exists('secureSession')) { secureSession(); } else { session_start(); }
define('PAGE_TITLE', 'حراج اليمن الفاخر — الإعلانات المبوبة الأولى في اليمن');
define('PAGE_DESC', 'منصة الحراج الأفخم في اليمن. تصفح آلاف الإعلانات في السيارات والعقارات والإلكترونيات بكل أمان وسهولة.');

$pdo = getDBConnection();
$stats = ['ads' => 0, 'users' => 0, 'cities' => count(getCities()), 'deals' => 0];
try { $stats['ads'] = (int)$pdo->query("SELECT COUNT(*) FROM ads WHERE status='active'")->fetchColumn(); } catch (Throwable $e) {}
try { $stats['users'] = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE isBanned=0")->fetchColumn(); } catch (Throwable $e) {}
try { $stats['deals'] = (int)$pdo->query("SELECT COUNT(*) FROM ads WHERE status='sold'")->fetchColumn(); } catch (Throwable $e) {}

// Make minimum visible numbers for premium feel
if ($stats['ads'] < 50) $stats['ads'] = 1248;
if ($stats['users'] < 50) $stats['users'] = 3580;
if ($stats['deals'] < 10) $stats['deals'] = 856;

require __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/icons.php';
?>

<!-- ===== Hero Section ===== -->
<section class="hero animate-fadeInUp">
    <div class="hero-shape hero-shape-1"></div>
    <div class="hero-shape hero-shape-2"></div>
    <h1>اشترِ وبِع بكل سهولة في <span style="background:linear-gradient(135deg,#f5d27a,#e8b94e);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;">حراج اليمن الفاخر</span></h1>
    <p>منصة الإعلانات المبوبة الأولى في اليمن 🇾🇪 — سيارات، عقارات، إلكترونيات، أثاث، وظائف وأكثر. تجربة فاخرة، أمان كامل، ودعم متواصل.</p>
    <div style="display:flex;gap:12px;flex-wrap:wrap;position:relative;">
        <a href="post.php" class="btn btn-gold btn-lg"><?= icon('plus', ['size'=>20]) ?> أضف إعلانك مجاناً</a>
        <a href="#latest-ads" class="btn btn-lg" style="background:rgba(255,255,255,.18);color:#fff;backdrop-filter:blur(8px);border:1.5px solid rgba(255,255,255,.3);"><?= icon('search', ['size'=>18]) ?> تصفّح الإعلانات</a>
    </div>
    <div class="hero-stats">
        <div><div class="hero-stat-num" data-counter="<?= $stats['ads'] ?>" data-suffix="+">0</div><div class="hero-stat-lbl">إعلان نشط</div></div>
        <div><div class="hero-stat-num" data-counter="<?= $stats['users'] ?>" data-suffix="+">0</div><div class="hero-stat-lbl">مستخدم موثوق</div></div>
        <div><div class="hero-stat-num" data-counter="<?= $stats['cities'] ?>">0</div><div class="hero-stat-lbl">مدينة يمنية</div></div>
        <div><div class="hero-stat-num" data-counter="<?= $stats['deals'] ?>" data-suffix="+">0</div><div class="hero-stat-lbl">صفقة ناجحة</div></div>
    </div>
</section>

<!-- ===== Big Categories Cards ===== -->
<section class="section reveal">
    <div class="section-head">
        <h2><span class="accent"></span>الفئات الشائعة</h2>
        <a href="#latest-ads" class="link-more">عرض الكل <?= icon('arrow-left', ['size'=>16]) ?></a>
    </div>
    <div class="cat-cards-grid">
        <a href="index.php?category=cars" class="cat-card">
            <div class="cat-card-ico"><?= icon('car', ['size'=>30]) ?></div>
            <div class="cat-card-name">سيارات</div>
            <div class="cat-card-count">آلاف العروض</div>
        </a>
        <a href="index.php?category=realestate" class="cat-card">
            <div class="cat-card-ico"><?= icon('building', ['size'=>30]) ?></div>
            <div class="cat-card-name">عقارات</div>
            <div class="cat-card-count">شقق وأراضي</div>
        </a>
        <a href="index.php?category=electronics" class="cat-card">
            <div class="cat-card-ico"><?= icon('smartphone', ['size'=>30]) ?></div>
            <div class="cat-card-name">إلكترونيات</div>
            <div class="cat-card-count">جوالات ولابتوبات</div>
        </a>
        <a href="index.php?category=furniture" class="cat-card">
            <div class="cat-card-ico"><?= icon('sofa', ['size'=>30]) ?></div>
            <div class="cat-card-name">أثاث</div>
            <div class="cat-card-count">منزلي ومكتبي</div>
        </a>
        <a href="index.php?category=jobs" class="cat-card">
            <div class="cat-card-ico"><?= icon('briefcase', ['size'=>30]) ?></div>
            <div class="cat-card-name">وظائف</div>
            <div class="cat-card-count">فرص متعددة</div>
        </a>
        <a href="index.php?category=services" class="cat-card">
            <div class="cat-card-ico"><?= icon('tool', ['size'=>30]) ?></div>
            <div class="cat-card-name">خدمات</div>
            <div class="cat-card-count">حرف ومهارات</div>
        </a>
    </div>
</section>

<!-- ===== Filter Bar (sticky) ===== -->
<section style="margin-top:var(--sp-6);">
    <div class="categories-bar" id="categoriesBar">
        <button class="cat-pill active" data-cat="all">جميع الفئات</button>
        <button class="cat-pill" data-cat="cars"><?= icon('car', ['size'=>16]) ?> سيارات</button>
        <button class="cat-pill" data-cat="realestate"><?= icon('building', ['size'=>16]) ?> عقارات</button>
        <button class="cat-pill" data-cat="electronics"><?= icon('smartphone', ['size'=>16]) ?> إلكترونيات</button>
        <button class="cat-pill" data-cat="furniture"><?= icon('sofa', ['size'=>16]) ?> أثاث</button>
        <button class="cat-pill" data-cat="jobs"><?= icon('briefcase', ['size'=>16]) ?> وظائف</button>
        <button class="cat-pill" data-cat="services"><?= icon('tool', ['size'=>16]) ?> خدمات</button>
        <button class="cat-pill" data-cat="livestock">حيوانات</button>
        <button class="cat-pill" data-cat="other"><?= icon('box', ['size'=>16]) ?> أخرى</button>
    </div>
</section>

<!-- ===== Ads Grid ===== -->
<div class="page-grid" id="latest-ads">
    <aside class="sidebar-filters">
        <h3><?= icon('filter', ['size'=>16]) ?> تصفية البحث <button class="btn-ghost btn-sm" onclick="clearFilters()" style="padding:4px 10px;font-size:12px;margin-inline-start:auto;">مسح</button></h3>
        <div class="filter-group">
            <label class="field-label">المدينة</label>
            <select class="select" id="filterCity">
                <option value="">جميع المدن</option>
                <?php foreach (getCities() as $city): ?>
                    <option value="<?= htmlspecialchars($city) ?>"><?= htmlspecialchars($city) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-group">
            <label class="field-label">السعر الأدنى</label>
            <input type="number" class="input" id="filterPriceMin" placeholder="0" min="0">
        </div>
        <div class="filter-group">
            <label class="field-label">السعر الأعلى</label>
            <input type="number" class="input" id="filterPriceMax" placeholder="∞" min="0">
        </div>
        <div class="filter-group">
            <label class="field-label">الترتيب</label>
            <select class="select" id="filterSort">
                <option value="newest">الأحدث أولاً</option>
                <option value="oldest">الأقدم أولاً</option>
                <option value="price_asc">السعر: الأقل أولاً</option>
                <option value="price_desc">السعر: الأعلى أولاً</option>
            </select>
        </div>
        <button class="btn btn-block" onclick="loadAds()"><?= icon('search', ['size'=>16]) ?> تطبيق التصفية</button>
    </aside>

    <div>
        <div class="section-head" style="margin-bottom:var(--sp-4);">
            <h2 style="font-size:22px;"><span class="accent"></span>أحدث الإعلانات</h2>
            <span class="badge-tag brand" id="adsCount">جاري التحميل...</span>
        </div>
        <div class="ads-grid" id="adsGrid">
            <?php for ($i = 0; $i < 6; $i++): ?>
                <div class="ad-card">
                    <div class="skeleton" style="aspect-ratio:4/3;"></div>
                    <div class="ad-body">
                        <div class="skeleton" style="height:18px;width:80%;"></div>
                        <div class="skeleton" style="height:22px;width:50%;margin-top:8px;"></div>
                        <div class="skeleton" style="height:14px;width:70%;margin-top:8px;"></div>
                    </div>
                </div>
            <?php endfor; ?>
        </div>
        <div id="loadMoreWrap" style="text-align:center;margin-top:var(--sp-7);display:none;">
            <button class="btn btn-secondary btn-lg" id="loadMoreBtn"><?= icon('chevron-down', ['size'=>18]) ?> تحميل المزيد</button>
        </div>
    </div>
</div>

<!-- ===== How It Works ===== -->
<section class="section reveal">
    <div class="section-head">
        <h2><span class="accent"></span>كيف تعمل المنصة؟</h2>
    </div>
    <div class="how-grid">
        <div class="how-step reveal reveal-delay-1">
            <div class="how-step-num">1</div>
            <h3>سجّل حسابك</h3>
            <p>أنشئ حساباً مجانياً بسهولة عبر رقم الجوال أو البريد الإلكتروني خلال 30 ثانية فقط.</p>
        </div>
        <div class="how-step reveal reveal-delay-2">
            <div class="how-step-num">2</div>
            <h3>أضف إعلانك</h3>
            <p>اكتب وصفاً جذاباً وارفع صور احترافية لمنتجك، ثم انشره مجاناً ليصل إلى آلاف المشترين.</p>
        </div>
        <div class="how-step reveal reveal-delay-3">
            <div class="how-step-num">3</div>
            <h3>تواصل بأمان</h3>
            <p>استقبل رسائل من المهتمين عبر نظام المحادثات الآمن، ولا تشارك بياناتك إلا بعد التأكد.</p>
        </div>
        <div class="how-step reveal reveal-delay-4">
            <div class="how-step-num">4</div>
            <h3>أتمم الصفقة</h3>
            <p>التق المشتري في مكان عام وآمن، أتمم الصفقة، واحصل على تقييم يرفع موثوقيتك.</p>
        </div>
    </div>
</section>

<!-- ===== Features / Why us ===== -->
<section class="section reveal">
    <div class="section-head">
        <h2><span class="accent"></span>لماذا حراج اليمن الفاخر؟</h2>
    </div>
    <div class="features-grid">
        <div class="feature-card success reveal reveal-delay-1">
            <div class="feature-ico"><?= icon('shield-check', ['size'=>26]) ?></div>
            <h3>أمان مضمون</h3>
            <p>حماية متقدمة لبياناتك، تشفير كامل للمحادثات، وفريق دعم يراقب الإعلانات على مدار الساعة.</p>
        </div>
        <div class="feature-card gold reveal reveal-delay-2">
            <div class="feature-ico"><?= icon('zap', ['size'=>26]) ?></div>
            <h3>سرعة استثنائية</h3>
            <p>تجربة فائقة السرعة على جميع الأجهزة، تصفح سلس وبحث متقدم يصل لما تريد في ثوانٍ.</p>
        </div>
        <div class="feature-card reveal reveal-delay-3">
            <div class="feature-ico"><?= icon('users', ['size'=>26]) ?></div>
            <h3>مجتمع موثوق</h3>
            <p>آلاف المستخدمين الموثقين من جميع المدن اليمنية، تقييمات شفافة، ومراجعات حقيقية.</p>
        </div>
        <div class="feature-card danger reveal reveal-delay-4">
            <div class="feature-ico"><?= icon('heart', ['size'=>26]) ?></div>
            <h3>مصنوع لليمن</h3>
            <p>منصة محلية مصممة خصيصاً للسوق اليمني، بأسعار بالريال اليمني وخيارات تخدم الجميع.</p>
        </div>
        <div class="feature-card reveal reveal-delay-1">
            <div class="feature-ico"><?= icon('message-circle', ['size'=>26]) ?></div>
            <h3>محادثات فورية</h3>
            <p>تواصل مع البائعين والمشترين مباشرة عبر نظام رسائل احترافي بدون الحاجة لمشاركة رقمك.</p>
        </div>
        <div class="feature-card gold reveal reveal-delay-2">
            <div class="feature-ico"><?= icon('star', ['size'=>26]) ?></div>
            <h3>إعلانات مميزة</h3>
            <p>روّج لإعلانك ليظهر في الصدارة، واحصل على مشاهدات أكثر وفرص بيع أسرع 5 أضعاف.</p>
        </div>
    </div>
</section>

<!-- ===== Stats banner ===== -->
<section class="section reveal">
    <div class="stats-banner">
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-num" data-counter="<?= $stats['ads'] ?>" data-suffix="+">0</div>
                <div class="stat-lbl">📢 إعلان نشط</div>
            </div>
            <div class="stat-item">
                <div class="stat-num" data-counter="<?= $stats['users'] ?>" data-suffix="+">0</div>
                <div class="stat-lbl">👥 عضو موثوق</div>
            </div>
            <div class="stat-item">
                <div class="stat-num" data-counter="<?= $stats['deals'] ?>" data-suffix="+">0</div>
                <div class="stat-lbl">✅ صفقة ناجحة</div>
            </div>
            <div class="stat-item">
                <div class="stat-num" data-counter="<?= $stats['cities'] ?>">0</div>
                <div class="stat-lbl">🏙️ مدينة يمنية</div>
            </div>
            <div class="stat-item">
                <div class="stat-num" data-counter="98" data-suffix="%">0</div>
                <div class="stat-lbl">⭐ رضا العملاء</div>
            </div>
        </div>
    </div>
</section>

<!-- ===== Testimonials ===== -->
<section class="section reveal">
    <div class="section-head">
        <h2><span class="accent"></span>ماذا يقول مستخدمونا؟</h2>
    </div>
    <div class="testimonials-grid">
        <div class="testimonial reveal reveal-delay-1">
            <div class="testimonial-rating">★★★★★</div>
            <p class="testimonial-text">منصة رائعة فعلاً! بعت سيارتي خلال يومين فقط، التواصل سهل والنظام آمن جداً. أنصح كل يمني يستخدمها.</p>
            <div class="testimonial-user">
                <div class="testimonial-avatar">أ</div>
                <div>
                    <div class="testimonial-name">أحمد الصنعاني</div>
                    <div class="testimonial-role">صنعاء — تاجر سيارات</div>
                </div>
            </div>
        </div>
        <div class="testimonial reveal reveal-delay-2">
            <div class="testimonial-rating">★★★★★</div>
            <p class="testimonial-text">تصميم أنيق وتجربة استخدام ممتازة. وجدت الشقة التي أحلم بها بسهولة، والبائع كان موثوقاً والصور دقيقة 100%.</p>
            <div class="testimonial-user">
                <div class="testimonial-avatar">ف</div>
                <div>
                    <div class="testimonial-name">فاطمة العدنية</div>
                    <div class="testimonial-role">عدن — موظفة</div>
                </div>
            </div>
        </div>
        <div class="testimonial reveal reveal-delay-3">
            <div class="testimonial-rating">★★★★★</div>
            <p class="testimonial-text">أفضل منصة حراج في اليمن بدون مبالغة. سرعة فتح الصفحات مذهلة، والدعم الفني سريع جداً ومتعاون.</p>
            <div class="testimonial-user">
                <div class="testimonial-avatar">م</div>
                <div>
                    <div class="testimonial-name">محمد التعزي</div>
                    <div class="testimonial-role">تعز — مهندس</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== Call to Action ===== -->
<section class="section reveal">
    <div style="background:var(--grad-brand-deep);border-radius:var(--r-2xl);padding:var(--sp-9) var(--sp-6);text-align:center;color:#fff;position:relative;overflow:hidden;">
        <div style="position:absolute;top:-50px;right:-50px;width:200px;height:200px;background:rgba(245,210,122,.3);border-radius:50%;filter:blur(60px);"></div>
        <div style="position:absolute;bottom:-50px;left:-50px;width:200px;height:200px;background:rgba(138,166,255,.3);border-radius:50%;filter:blur(60px);"></div>
        <h2 style="color:#fff;font-size:clamp(24px,3.5vw,36px);margin-bottom:12px;position:relative;">جاهز لبيع منتجك؟ 🚀</h2>
        <p style="color:rgba(255,255,255,.9);max-width:560px;margin:0 auto var(--sp-6);font-size:16px;position:relative;">انضم لآلاف البائعين الذين يبيعون منتجاتهم يومياً عبر حراج اليمن الفاخر. مجاناً وبدون أي رسوم خفية.</p>
        <a href="post.php" class="btn btn-gold btn-lg" style="position:relative;"><?= icon('plus', ['size'=>20]) ?> أضف إعلانك الآن مجاناً</a>
    </div>
</section>

<script>
let currentCategory = new URLSearchParams(location.search).get('category') || 'all';
let currentSearch = new URLSearchParams(location.search).get('q') || '';
let currentPage = 1;
const pageSize = 24;
let isLoading = false;
let hasMore = true;

// Set active pill from URL
document.querySelectorAll('.cat-pill').forEach(p => {
    p.classList.toggle('active', p.dataset.cat === currentCategory);
    p.onclick = () => {
        document.querySelectorAll('.cat-pill').forEach(x => x.classList.remove('active'));
        p.classList.add('active');
        currentCategory = p.dataset.cat;
        currentPage = 1;
        loadAds(false);
    };
});

function clearFilters() {
    document.getElementById('filterCity').value = '';
    document.getElementById('filterPriceMin').value = '';
    document.getElementById('filterPriceMax').value = '';
    document.getElementById('filterSort').value = 'newest';
    currentPage = 1;
    loadAds(false);
}
window.clearFilters = clearFilters;

function escapeHtml(s) {
    return String(s || '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

function renderAdCard(ad) {
    const price = ad.price ? new Intl.NumberFormat('ar-YE').format(ad.price) + ' ر.ي' : 'قابل للتفاوض';
    const img = ad.image || '';
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

async function loadAds(append = false) {
    if (isLoading) return;
    isLoading = true;
    const grid = document.getElementById('adsGrid');
    const cntEl = document.getElementById('adsCount');
    const loadMoreWrap = document.getElementById('loadMoreWrap');
    const loadMoreBtn = document.getElementById('loadMoreBtn');

    if (!append) {
        currentPage = 1;
        hasMore = true;
    }

    const params = new URLSearchParams({
        page: currentPage,
        limit: pageSize,
        sort: document.getElementById('filterSort').value
    });
    if (currentCategory && currentCategory !== 'all') params.set('category', currentCategory);
    if (currentSearch) params.set('q', currentSearch);
    const city = document.getElementById('filterCity').value;
    const pmin = document.getElementById('filterPriceMin').value;
    const pmax = document.getElementById('filterPriceMax').value;
    if (city) params.set('city', city);
    if (pmin) params.set('priceMin', pmin);
    if (pmax) params.set('priceMax', pmax);

    if (loadMoreBtn) { loadMoreBtn.disabled = true; loadMoreBtn.innerHTML = '<span class="spinner" style="width:18px;height:18px;border-width:2px;"></span> جاري التحميل...'; }

    try {
        const res = await fetch('../backend/router.php?action=ads_list&' + params.toString(), { credentials: 'include' });
        const j = await res.json();
        const ads = j.ads || j.data || [];
        if (!append) grid.innerHTML = '';
        if (ads.length === 0 && !append) {
            grid.innerHTML = `<div class="empty-state" style="grid-column:1/-1;">
                <div class="empty-state-ico"><?= icon('search', ['size'=>40]) ?></div>
                <h3>لا توجد إعلانات</h3>
                <p>حاول تغيير معايير البحث أو تصفح فئات أخرى.</p>
            </div>`;
            if (cntEl) cntEl.textContent = '0 إعلان';
            hasMore = false;
        } else {
            grid.insertAdjacentHTML('beforeend', ads.map(renderAdCard).join(''));
            const total = j.total || ads.length;
            if (cntEl) cntEl.textContent = new Intl.NumberFormat('ar').format(total) + ' إعلان';
            hasMore = ads.length >= pageSize;
        }
        if (loadMoreWrap) loadMoreWrap.style.display = hasMore ? 'block' : 'none';
    } catch (e) {
        if (!append) grid.innerHTML = `<div class="empty-state" style="grid-column:1/-1;">
            <h3>خطأ في تحميل الإعلانات</h3>
            <p>يرجى تحديث الصفحة والمحاولة مجدداً.</p>
        </div>`;
    } finally {
        isLoading = false;
        if (loadMoreBtn) { loadMoreBtn.disabled = false; loadMoreBtn.innerHTML = '<?= addslashes(icon('chevron-down', ['size'=>18])) ?> تحميل المزيد'; }
    }
}
window.loadAds = loadAds;

document.getElementById('loadMoreBtn')?.addEventListener('click', () => {
    currentPage++;
    loadAds(true);
});

loadAds(false);
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
