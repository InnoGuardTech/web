<?php
require_once __DIR__ . '/../config.php';

define('PAGE_TITLE', SITE_NAME . ' - ' . SITE_SLOGAN);
define('PAGE_DESC', 'أكبر منصة للبيع والشراء في اليمن. تصفّح آلاف الإعلانات في السيارات، العقارات، الإلكترونيات، والمزيد.');
define('SHOW_SIDEBAR_TOGGLE', true);
define('PAGE_URL', APP_URL . '/frontend/index.php');

include __DIR__ . '/includes/header.php';
?>

<div class="home-container">
    <!-- Sidebar -->
    <aside class="sidebar-card animate-fade-in" id="main-sidebar">
        <h3 class="sidebar-title">🚗 ماركات السيارات</h3>
        <div class="brand-grid">
            <?php
            $brands = [
                ['تويوتا','🚙','#e53e3e'],
                ['هيونداي','🚗','#3182ce'],
                ['مرسيدس','🏎️','#718096'],
                ['لكزس','🚙','#dd6b20'],
                ['نيسان','🚗','#4a5568'],
                ['فورد','🚙','#2b6cb0'],
                ['كيا','🚗','#805ad5'],
                ['شيفروليه','🏎️','#d69e2e'],
                ['BMW','🚗','#2c5282']
            ];
            foreach ($brands as $b) {
                echo '<a href="#" class="brand-item" data-brand="' . htmlspecialchars($b[0]) . '" onclick="filterByBrand(\'' . htmlspecialchars($b[0], ENT_QUOTES) . '\', event)">';
                echo '<span class="brand-logo">' . $b[1] . '</span>';
                echo '<span>' . htmlspecialchars($b[0]) . '</span>';
                echo '</a>';
            }
            ?>
        </div>

        <h3 class="sidebar-title">📁 جميع الأقسام</h3>
        <div class="brand-list-all" id="categories-sidebar">
            <div style="padding:1rem; text-align:center; color:var(--text-muted);">جاري التحميل...</div>
        </div>

        <h3 class="sidebar-title" style="margin-top: 1rem;">🏙️ المدن</h3>
        <div id="cities-sidebar" style="display:flex; flex-wrap:wrap; gap:6px;">
            <div style="padding:0.5rem; color:var(--text-muted); font-size:0.8rem;">جاري التحميل...</div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="animate-fade-in">

        <!-- Category Tabs -->
        <div class="filter-tabs" id="categories-tabs">
            <!-- Dynamic -->
        </div>

        <!-- Advanced Filters -->
        <div class="advanced-filters">
            <div class="filter-chip">
                <span>📍</span>
                <select id="city-select" onchange="loadAds()" aria-label="المدينة">
                    <option value="الكل">كل المدن</option>
                </select>
            </div>

            <div class="filter-chip">
                <span>↕️</span>
                <select id="sort-select" onchange="loadAds()" aria-label="الترتيب">
                    <option value="newest">الأحدث</option>
                    <option value="popular">الأكثر مشاهدة</option>
                    <option value="cheapest">الأرخص</option>
                    <option value="expensive">الأغلى</option>
                    <option value="oldest">الأقدم</option>
                </select>
            </div>

            <div class="filter-chip">
                <span>💰 من</span>
                <input type="number" id="min-price" placeholder="0" min="0" oninput="debouncedReload()" aria-label="أدنى سعر">
            </div>

            <div class="filter-chip">
                <span>💰 إلى</span>
                <input type="number" id="max-price" placeholder="∞" min="0" oninput="debouncedReload()" aria-label="أعلى سعر">
            </div>

            <div class="filter-chip" id="year-filter" style="display:none;">
                <span>📅 من سنة</span>
                <input type="number" id="min-year" placeholder="1990" min="1980" max="2030" oninput="debouncedReload()">
            </div>
            <div class="filter-chip" id="year-filter-max" style="display:none;">
                <span>إلى</span>
                <input type="number" id="max-year" placeholder="2026" min="1980" max="2030" oninput="debouncedReload()">
            </div>

            <button onclick="resetFilters()" style="background:transparent; border:1px solid var(--border-color); padding:0.4rem 0.8rem; border-radius:var(--radius-full); font-size:0.78rem; cursor:pointer; color:var(--text-muted); font-weight:700;">🔄 إعادة تعيين</button>

            <div style="margin-right:auto; display:flex; gap:6px;">
                <button onclick="setView('list')" id="view-list-btn" class="view-btn active" title="عرض قائمة">≡</button>
                <button onclick="setView('grid')" id="view-grid-btn" class="view-btn" title="عرض شبكي">▦</button>
            </div>
        </div>

        <div id="active-brand-indicator" style="display:none; margin-bottom:0.75rem;">
            <span class="status-badge" style="background:var(--accent); color:var(--primary); padding:6px 12px; font-size:0.8rem;">
                🚗 الماركة: <strong id="active-brand-name"></strong>
                <button onclick="clearBrandFilter()" style="background:none; border:none; color:var(--danger); cursor:pointer; font-weight:900; margin-right:6px;">×</button>
            </span>
        </div>

        <!-- Ads Container -->
        <div class="ad-list" id="ads-container">
            <div class="skeleton-row">
                <div class="skeleton-row-main">
                    <div class="skeleton-thumb"></div>
                    <div class="skeleton-content">
                        <div class="skeleton-title"></div>
                        <div class="skeleton-meta"></div>
                    </div>
                </div>
                <div class="skeleton-side">
                    <div class="skeleton-price"></div>
                    <div class="skeleton-city"></div>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div id="pagination" style="display:flex; justify-content:center; gap:6px; margin: 2rem 0; flex-wrap:wrap;"></div>
    </main>
</div>

<style>
.view-btn {
    background: var(--bg-color);
    border: 1px solid var(--border-color);
    padding: 0.35rem 0.65rem;
    border-radius: var(--radius-md);
    font-size: 1rem;
    cursor: pointer;
    color: var(--text-muted);
    font-weight: 900;
    transition: var(--transition);
}
.view-btn.active {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}
.page-btn {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    padding: 0.45rem 0.85rem;
    border-radius: var(--radius-md);
    font-weight: 800;
    cursor: pointer;
    font-size: 0.85rem;
    color: var(--text-main);
    transition: var(--transition);
}
.page-btn:hover { border-color: var(--primary); color: var(--primary); }
.page-btn.active { background: var(--primary); color: white; border-color: var(--primary); }
.page-btn:disabled { opacity: 0.4; cursor: not-allowed; }
</style>

<script src="assets/js/app.js"></script>
<script>
    let state = {
        cat: '<?= htmlspecialchars($_GET['cat'] ?? 'all') ?>',
        city: '<?= htmlspecialchars($_GET['city'] ?? 'الكل') ?>',
        brand: '',
        q: '<?= htmlspecialchars($_GET['q'] ?? '') ?>',
        sort: 'newest',
        page: 1,
        view: localStorage.getItem('adsView') || 'list'
    };

    const debouncedReload = debounce(() => { state.page = 1; loadAds(); }, 500);

    async function init() {
        // Cities
        try {
            const cityRes = await apiRequest('cities');
            const sel = document.getElementById('city-select');
            const sidebar = document.getElementById('cities-sidebar');
            sidebar.innerHTML = '';
            cityRes.data.forEach(city => {
                const opt = document.createElement('option');
                opt.value = city; opt.textContent = city;
                sel.appendChild(opt);

                const chip = document.createElement('a');
                chip.href = '#';
                chip.style.cssText = 'background:var(--bg-color); padding:4px 10px; border-radius:var(--radius-full); font-size:0.75rem; font-weight:700; color:var(--text-main); text-decoration:none; border:1px solid var(--border-color);';
                chip.textContent = city;
                chip.onclick = (e) => { e.preventDefault(); sel.value = city; state.city = city; state.page = 1; loadAds(); };
                sidebar.appendChild(chip);
            });
            if (state.city !== 'الكل') sel.value = state.city;
        } catch (e) {}

        // Categories
        try {
            const catRes = await apiRequest('categories');
            const tabs = document.getElementById('categories-tabs');
            const sidebar = document.getElementById('categories-sidebar');
            tabs.innerHTML = ''; sidebar.innerHTML = '';

            catRes.data.forEach(cat => {
                const tabBtn = document.createElement('button');
                tabBtn.className = `filter-tab-btn ${cat.id === state.cat ? 'active' : ''}`;
                tabBtn.innerHTML = `<span>${cat.icon}</span> ${cat.name}`;
                tabBtn.onclick = () => selectCategory(cat.id);
                tabs.appendChild(tabBtn);

                const side = document.createElement('a');
                side.href = '#';
                side.className = `brand-list-all-item ${cat.id === state.cat ? 'active' : ''}`;
                side.dataset.cat = cat.id;
                side.innerHTML = `<span>${cat.icon}</span><span>${cat.name}</span>`;
                side.onclick = (e) => { e.preventDefault(); selectCategory(cat.id); };
                sidebar.appendChild(side);
            });
        } catch (e) {}

        // initial search query
        if (state.q) document.getElementById('header-search-input').value = state.q;

        loadAds();
    }

    function selectCategory(catId) {
        document.querySelectorAll('.filter-tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.brand-list-all-item').forEach(b => b.classList.remove('active'));
        document.querySelectorAll(`[data-cat="${catId}"]`).forEach(b => b.classList.add('active'));
        // tabs by text
        document.querySelectorAll('.filter-tab-btn').forEach(b => {
            if (b.textContent.trim().endsWith(getCategoryName(catId))) b.classList.add('active');
        });
        state.cat = catId;
        state.page = 1;

        // Show/hide year filter for cars
        const yearFilter = document.getElementById('year-filter');
        const yearMax = document.getElementById('year-filter-max');
        if (catId === 'cars') {
            yearFilter.style.display = 'flex';
            yearMax.style.display = 'flex';
        } else {
            yearFilter.style.display = 'none';
            yearMax.style.display = 'none';
            state.brand = '';
            document.getElementById('active-brand-indicator').style.display = 'none';
        }
        loadAds();
    }

    function getCategoryName(id) {
        const map = {all:'الكل',cars:'حراج السيارات',realestate:'عقارات',electronics:'أجهزة وإلكترونيات',
                     livestock:'مواشي وحيوانات',furniture:'أثاث ومفروشات',jobs:'وظائف',services:'خدمات',other:'أخرى'};
        return map[id] || id;
    }

    async function loadAds() {
        const container = document.getElementById('ads-container');

        // Build query
        const city = document.getElementById('city-select').value;
        const sort = document.getElementById('sort-select').value;
        const q = document.getElementById('header-search-input')?.value || state.q;
        const minPrice = document.getElementById('min-price').value;
        const maxPrice = document.getElementById('max-price').value;
        const minYear = document.getElementById('min-year')?.value || '';
        const maxYear = document.getElementById('max-year')?.value || '';

        state.city = city;
        state.sort = sort;
        state.q = q;

        // Skeleton
        let skel = '';
        for (let i = 0; i < 4; i++) skel += `
            <div class="skeleton-row">
                <div class="skeleton-row-main">
                    <div class="skeleton-thumb"></div>
                    <div class="skeleton-content">
                        <div class="skeleton-title" style="width:70%;"></div>
                        <div class="skeleton-meta" style="width:50%;"></div>
                    </div>
                </div>
                <div class="skeleton-side">
                    <div class="skeleton-price"></div>
                    <div class="skeleton-city"></div>
                </div>
            </div>`;
        container.innerHTML = skel;

        try {
            const params = new URLSearchParams({
                cat: state.cat, city, brand: state.brand, q,
                sort, page: state.page, per_page: 20
            });
            if (minPrice) params.set('min_price', minPrice);
            if (maxPrice) params.set('max_price', maxPrice);
            if (minYear) params.set('min_year', minYear);
            if (maxYear) params.set('max_year', maxYear);

            const res = await apiRequest('ads&' + params.toString());
            const ads = res.data.ads;
            const total = res.data.total;
            const totalPages = res.data.total_pages;

            container.innerHTML = '';
            container.className = state.view === 'grid' ? 'ads-grid' : 'ad-list';

            if (ads.length === 0) {
                container.innerHTML = `
                    <div style="text-align:center; padding:4rem 1rem; color:var(--text-muted);">
                        <div style="font-size:4rem; opacity:0.3;">🔍</div>
                        <h3 style="margin:1rem 0 0.5rem; color:var(--text-main);">لا توجد نتائج</h3>
                        <p>جرّب تغيير معايير البحث أو الفلاتر</p>
                        <button onclick="resetFilters()" class="btn-outline" style="margin-top:1rem;">إعادة تعيين الفلاتر</button>
                    </div>`;
                document.getElementById('pagination').innerHTML = '';
                return;
            }

            ads.forEach(ad => {
                const pinBadge = ad.isPinned ? '<span class="badge-pinned">📌 مثبت</span>' : '';
                const verifiedBadge = ad.verified ? '<span class="badge-verified">✓ موثق</span>' : '';

                let cardHtml;
                if (state.view === 'grid') {
                    cardHtml = `
                        <a href="ad.php?id=${ad.id}${ad.slug ? '&slug='+encodeURIComponent(ad.slug) : ''}" class="ad-card animate-fade-in">
                            <img class="ad-card-img" src="${ad.image}" alt="${escapeHtml(ad.title)}" loading="lazy">
                            <div class="ad-card-body">
                                <h3 class="ad-card-title">${pinBadge} ${escapeHtml(ad.title)}</h3>
                                <div class="ad-card-price">${ad.price}</div>
                                <div class="ad-card-meta">
                                    <span>📍 ${escapeHtml(ad.city)}</span>
                                    <span>⏱️ ${escapeHtml(ad.date)}</span>
                                </div>
                            </div>
                        </a>`;
                } else {
                    cardHtml = `
                        <a href="ad.php?id=${ad.id}${ad.slug ? '&slug='+encodeURIComponent(ad.slug) : ''}" class="ad-row animate-fade-in">
                            <div class="ad-row-main">
                                <img class="ad-row-thumb" src="${ad.image}" alt="${escapeHtml(ad.title)}" loading="lazy">
                                <div class="ad-row-content">
                                    <h3 class="ad-row-title">${pinBadge} ${escapeHtml(ad.title)}</h3>
                                    <div class="ad-row-meta">
                                        <div class="ad-row-meta-item">👤 ${verifiedBadge}<span>${escapeHtml(ad.userName)}</span></div>
                                        <div class="ad-row-meta-item">⏱️ <span>${escapeHtml(ad.date)}</span></div>
                                        <div class="ad-row-meta-item">👁️ <span>${ad.views || 0}</span></div>
                                    </div>
                                </div>
                            </div>
                            <div class="ad-row-side">
                                <div class="ad-row-price">${ad.price}</div>
                                <div class="ad-row-city">📍 ${escapeHtml(ad.city)}</div>
                            </div>
                        </a>`;
                }
                container.insertAdjacentHTML('beforeend', cardHtml);
            });

            renderPagination(totalPages, state.page, total);

        } catch (e) {
            container.innerHTML = `<div style="text-align:center; padding:3rem; color:var(--danger); font-weight:bold;">حدث خطأ أثناء التحميل: ${escapeHtml(e.message)}</div>`;
        }
    }

    function renderPagination(totalPages, current, total) {
        const pag = document.getElementById('pagination');
        if (totalPages <= 1) {
            pag.innerHTML = `<div style="color:var(--text-muted); font-size:0.85rem;">إجمالي ${total} إعلان</div>`;
            return;
        }

        let html = `<button class="page-btn" onclick="goToPage(${current - 1})" ${current === 1 ? 'disabled' : ''}>‹ السابق</button>`;

        const showPages = [];
        showPages.push(1);
        for (let i = current - 1; i <= current + 1; i++) {
            if (i > 1 && i < totalPages) showPages.push(i);
        }
        if (totalPages > 1) showPages.push(totalPages);
        const unique = [...new Set(showPages)].sort((a,b) => a-b);

        let prev = 0;
        unique.forEach(p => {
            if (prev && p - prev > 1) html += `<span style="padding:0 6px; color:var(--text-muted);">...</span>`;
            html += `<button class="page-btn ${p === current ? 'active' : ''}" onclick="goToPage(${p})">${p}</button>`;
            prev = p;
        });

        html += `<button class="page-btn" onclick="goToPage(${current + 1})" ${current === totalPages ? 'disabled' : ''}>التالي ›</button>`;
        html += `<div style="width:100%; text-align:center; font-size:0.8rem; color:var(--text-muted); margin-top:0.5rem;">صفحة ${current} من ${totalPages} — إجمالي ${total} إعلان</div>`;
        pag.innerHTML = html;
    }

    function goToPage(p) {
        if (p < 1) return;
        state.page = p;
        loadAds();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function filterByBrand(brand, e) {
        if (e) e.preventDefault();
        document.querySelectorAll('.brand-item').forEach(b => b.classList.remove('active'));
        const activeBrand = document.querySelector(`.brand-item[data-brand="${brand}"]`);
        if (activeBrand) activeBrand.classList.add('active');

        // Switch to cars
        selectCategory('cars');

        state.brand = brand;
        document.getElementById('active-brand-name').textContent = brand;
        document.getElementById('active-brand-indicator').style.display = 'block';

        state.page = 1;
        loadAds();
    }

    function clearBrandFilter() {
        document.querySelectorAll('.brand-item').forEach(b => b.classList.remove('active'));
        state.brand = '';
        document.getElementById('active-brand-indicator').style.display = 'none';
        state.page = 1;
        loadAds();
    }

    function resetFilters() {
        document.getElementById('city-select').value = 'الكل';
        document.getElementById('sort-select').value = 'newest';
        document.getElementById('min-price').value = '';
        document.getElementById('max-price').value = '';
        const my = document.getElementById('min-year'); if (my) my.value = '';
        const myx = document.getElementById('max-year'); if (myx) myx.value = '';
        document.getElementById('header-search-input').value = '';
        state = { ...state, city:'الكل', sort:'newest', q:'', brand:'', page:1 };
        document.getElementById('active-brand-indicator').style.display = 'none';
        loadAds();
    }

    function setView(v) {
        state.view = v;
        localStorage.setItem('adsView', v);
        document.getElementById('view-list-btn').classList.toggle('active', v === 'list');
        document.getElementById('view-grid-btn').classList.toggle('active', v === 'grid');
        loadAds();
    }

    // Search input debounced
    const searchInput = document.getElementById('header-search-input');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(() => { state.page = 1; loadAds(); }, 500));
        searchInput.addEventListener('keydown', e => {
            if (e.key === 'Enter') { state.page = 1; loadAds(); }
        });
    }

    // Init view button
    setView(state.view);

    document.addEventListener('DOMContentLoaded', init);
</script>
</body>
</html>
