<?php
require_once __DIR__ . '/../backend/config.php';
if (function_exists('secureSession')) { secureSession(); } else { session_start(); }
define('PAGE_TITLE', 'حراج اليمن الفاخر — الإعلانات المبوبة الأولى في اليمن');
define('PAGE_DESC', 'منصة الحراج الأفخم في اليمن. تصفح آلاف الإعلانات في السيارات والعقارات والإلكترونيات.');

$pdo = getDBConnection();
$stats = ['ads' => 0, 'users' => 0, 'cities' => count(getCities())];
try { $stats['ads'] = (int)$pdo->query("SELECT COUNT(*) FROM ads WHERE status='active'")->fetchColumn(); } catch (Throwable $e) {}
try { $stats['users'] = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE isBanned=0")->fetchColumn(); } catch (Throwable $e) {}

require __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/icons.php';
?>

<section class="hero">
    <h1>اشترِ وبِع بكل سهولة في حراج اليمن الفاخر</h1>
    <p>منصة الإعلانات المبوبة الأولى في اليمن. سيارات، عقارات، إلكترونيات وأكثر — تجربة فاخرة وأمان كامل.</p>
    <div style="display:flex;gap:12px;flex-wrap:wrap;position:relative;">
        <a href="post.php" class="btn btn-gold btn-lg"><?= icon('plus', ['size'=>20]) ?> أضف إعلانك مجاناً</a>
        <a href="#latest-ads" class="btn btn-lg" style="background:rgba(255,255,255,.15);color:#fff;backdrop-filter:blur(6px);border:1px solid rgba(255,255,255,.25);">تصفّح الإعلانات</a>
    </div>
    <div class="hero-stats">
        <div><div class="hero-stat-num"><?= number_format($stats['ads']) ?>+</div><div class="hero-stat-lbl">إعلان نشط</div></div>
        <div><div class="hero-stat-num"><?= number_format($stats['users']) ?>+</div><div class="hero-stat-lbl">مستخدم موثوق</div></div>
        <div><div class="hero-stat-num"><?= $stats['cities'] ?></div><div class="hero-stat-lbl">مدينة يمنية</div></div>
    </div>
</section>

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

<div class="page-grid" id="latest-ads">
    <aside class="sidebar-filters">
        <h3><?= icon('filter', ['size'=>16]) ?> تصفية البحث <button class="btn-ghost btn-sm" onclick="clearFilters()" style="padding:4px 10px;font-size:12px;">مسح</button></h3>
        <div class="filter-group">
            <label class="field-label">المدينة</label>
            <select class="select" id="filterCity">
                <option value="">جميع المدن</option>
                <?php foreach (getCities() as $c): ?><option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="filter-group">
            <label class="field-label">السعر (ر.ي)</label>
            <div class="range-row">
                <input type="number" class="input" id="filterMinPrice" placeholder="من">
                <input type="number" class="input" id="filterMaxPrice" placeholder="إلى">
            </div>
        </div>
        <div class="filter-group" id="yearFilterGroup" style="display:none;">
            <label class="field-label">سنة الصنع</label>
            <div class="range-row">
                <input type="number" class="input" id="filterMinYear" placeholder="من" min="1980" max="2026">
                <input type="number" class="input" id="filterMaxYear" placeholder="إلى" min="1980" max="2026">
            </div>
        </div>
        <div class="filter-group"><button class="btn btn-primary btn-block" onclick="applyFilters()">تطبيق التصفية</button></div>
    </aside>

    <section>
        <div class="toolbar">
            <div class="toolbar-title"><span id="adsTitle">جميع الإعلانات</span> <small id="adsCount"></small></div>
            <div class="toolbar-controls">
                <select class="select" id="sortBy" onchange="applyFilters()" style="height:38px;width:auto;padding:0 32px;">
                    <option value="latest">الأحدث</option>
                    <option value="oldest">الأقدم</option>
                    <option value="cheapest">السعر: من الأقل</option>
                    <option value="expensive">السعر: من الأعلى</option>
                    <option value="popular">الأكثر مشاهدة</option>
                </select>
            </div>
        </div>
        <div class="ads-grid" id="adsGrid"></div>
        <div class="pagination" id="pagination"></div>
    </section>
</div>

<script>
const filters = {
    category: new URLSearchParams(location.search).get('category') || 'all',
    city: new URLSearchParams(location.search).get('city') || '',
    minPrice: '', maxPrice: '', minYear: '', maxYear: '',
    sort: 'latest',
    q: new URLSearchParams(location.search).get('q') || '',
    page: 1
};

document.querySelectorAll('.cat-pill').forEach(btn => {
    if (btn.dataset.cat === filters.category) {
        document.querySelectorAll('.cat-pill').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
    }
    btn.onclick = () => {
        document.querySelectorAll('.cat-pill').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        filters.category = btn.dataset.cat;
        filters.page = 1;
        document.getElementById('yearFilterGroup').style.display = filters.category === 'cars' ? 'block' : 'none';
        loadAds();
    };
});
if (filters.city) document.getElementById('filterCity').value = filters.city;
if (filters.category === 'cars') document.getElementById('yearFilterGroup').style.display = 'block';

function applyFilters() {
    filters.city = document.getElementById('filterCity').value;
    filters.minPrice = document.getElementById('filterMinPrice').value;
    filters.maxPrice = document.getElementById('filterMaxPrice').value;
    filters.minYear = document.getElementById('filterMinYear')?.value || '';
    filters.maxYear = document.getElementById('filterMaxYear')?.value || '';
    filters.sort = document.getElementById('sortBy').value;
    filters.page = 1;
    loadAds();
}
function clearFilters() {
    document.getElementById('filterCity').value = '';
    document.getElementById('filterMinPrice').value = '';
    document.getElementById('filterMaxPrice').value = '';
    if (document.getElementById('filterMinYear')) document.getElementById('filterMinYear').value = '';
    if (document.getElementById('filterMaxYear')) document.getElementById('filterMaxYear').value = '';
    Object.assign(filters, {city:'',minPrice:'',maxPrice:'',minYear:'',maxYear:'',page:1});
    loadAds();
}

async function loadAds() {
    const grid = document.getElementById('adsGrid');
    grid.innerHTML = skeletonGrid(8);
    const params = new URLSearchParams();
    if (filters.category && filters.category !== 'all') params.append('category', filters.category);
    if (filters.city) params.append('city', filters.city);
    if (filters.minPrice) params.append('min_price', filters.minPrice);
    if (filters.maxPrice) params.append('max_price', filters.maxPrice);
    if (filters.minYear) params.append('min_year', filters.minYear);
    if (filters.maxYear) params.append('max_year', filters.maxYear);
    if (filters.q) params.append('q', filters.q);
    params.append('sort', filters.sort);
    params.append('page', filters.page);

    const res = await api('ads&action=list&' + params.toString());
    if (!res.success) {
        grid.innerHTML = `<div class="empty-state" style="grid-column:1/-1;"><h3>تعذّر تحميل الإعلانات</h3><p>${res.message || 'حدث خطأ'}</p></div>`;
        return;
    }
    const ads = res.ads || res.data?.ads || res.data || [];
    const total = res.total || res.data?.total || ads.length;
    document.getElementById('adsCount').textContent = ads.length > 0 ? `(${total} إعلان)` : '';
    if (ads.length === 0) {
        grid.innerHTML = `<div class="empty-state" style="grid-column:1/-1;"><div style="font-size:60px;margin-bottom:12px;opacity:.4;">📭</div><h3>لا توجد إعلانات مطابقة</h3><p>جرّب تغيير الفلاتر أو ابحث في فئة أخرى</p><button class="btn btn-primary" onclick="clearFilters()">مسح الفلاتر</button></div>`;
        document.getElementById('pagination').innerHTML = '';
        return;
    }
    grid.innerHTML = ads.map(renderAdCard).join('');
    renderPagination(total, res.per_page || res.data?.per_page || 20, filters.page);
}

function renderPagination(total, perPage, current) {
    const pages = Math.ceil(total / perPage);
    if (pages <= 1) { document.getElementById('pagination').innerHTML = ''; return; }
    let html = `<button onclick="goPage(${current-1})" ${current<=1?'disabled':''}>السابق</button>`;
    const start = Math.max(1, current - 2);
    const end = Math.min(pages, current + 2);
    if (start > 1) html += `<button onclick="goPage(1)">1</button>${start>2?'<span style="padding:0 6px;color:var(--muted);">...</span>':''}`;
    for (let i = start; i <= end; i++) html += `<button class="${i===current?'active':''}" onclick="goPage(${i})">${i}</button>`;
    if (end < pages) html += `${end<pages-1?'<span style="padding:0 6px;color:var(--muted);">...</span>':''}<button onclick="goPage(${pages})">${pages}</button>`;
    html += `<button onclick="goPage(${current+1})" ${current>=pages?'disabled':''}>التالي</button>`;
    document.getElementById('pagination').innerHTML = html;
}
function goPage(p) { filters.page = p; loadAds(); window.scrollTo({top: document.getElementById('latest-ads').offsetTop - 80, behavior: 'smooth'}); }

loadAds();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
