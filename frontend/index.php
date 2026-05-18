<?php session_start(); ?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>حراج اليمن - منصة البيع والشراء الأولى في اليمن</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
    <div style="background: linear-gradient(90deg, #0F2942, #1E3E5E); color: white; padding: 12px 20px; text-align: center; font-weight: bold; font-size: 0.95rem; z-index: 9999; position: relative; box-shadow: 0 4px 15px rgba(15, 41, 66, 0.4); border-bottom: 2px solid #C5A059;">
        🛡️ وضع الإدارة مفعل <a href="admin.php" style="color: #FFD700; text-decoration: none; background: rgba(255,255,255,0.1); padding: 4px 12px; border-radius: 20px; margin-right: 15px; font-weight: 900; transition: all 0.3s;">الذهاب للوحة التحكم ⚙️</a>
    </div>
    <?php endif; ?>

    <!-- Header / Top Bar styled like Saudi Haraj -->
    <header class="glass-header">
        <div class="header-container">
            <a href="index.php" class="header-logo">
                <span class="header-logo-badge">حراج</span>
                <span>اليمن</span>
            </a>
            
            <!-- Real Haraj Style Live Search Bar -->
            <div class="header-search">
                <input type="text" id="search-input" placeholder="ابحث عن سيارة، عقار، جهاز، أو أي سلعة..." oninput="debounceSearch()">
                <button onclick="loadAds()">🔍</button>
            </div>
            
            <div class="header-actions" id="user-menu-area">
                <button onclick="toggleTheme()" style="background:none; border:none; cursor:pointer; font-size:1.1rem; color:white;" title="الوضع الداكن">🌓</button>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="post.php" class="btn-gold">+ أضف إعلانك</a>
                    
                    <a href="favorites.php" style="text-decoration:none; font-size:1.2rem;" title="المفضلة">❤️</a>
                    
                    <a href="messages.php" style="text-decoration:none; font-size:1.2rem; position:relative;" title="الرسائل">
                        💬
                        <span id="chat-badge" style="display:none; position:absolute; top:-5px; right:-5px; background:red; color:white; border-radius:50%; font-size:0.6rem; width:15px; height:15px; align-items:center; justify-content:center; font-weight:bold;"></span>
                    </a>
                    
                    <a href="user.php?id=<?php echo $_SESSION['user_id']; ?>" style="text-decoration:none; font-weight:800; font-size:0.85rem;">👤 حسابي</a>
                    
                    <button onclick="logout()" style="background:none; border:none; color:white; font-weight:bold; cursor:pointer; font-size:0.85rem; font-family:inherit;">خروج</button>
                <?php else: ?>
                    <a href="auth.php" class="btn-gold">دخول / تسجيل</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Main Layout Container -->
    <div class="home-container">
        
        <!-- Sidebar Navigation (Car Brands & Main Categories) -->
        <aside class="sidebar-card animate-fade-in">
            <h3 class="sidebar-title">🚗 ماركات السيارات</h3>
            <div class="brand-grid">
                <a href="#" class="brand-item" data-brand="تويوتا" onclick="filterByBrand('تويوتا', event)">
                    <!-- Toyota Styled SVG -->
                    <svg viewBox="0 0 24 24" style="width:28px; height:28px; fill:#e53e3e; margin-bottom:4px;"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8,8-8 8 3.59 8 8-3.59 8-8 8zm0-13c-2.76 0-5 2.24-5 5s2.24 5 5 5 5-2.24 5-5-2.24-5-5-5zm0 8c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3z"/></svg>
                    <span>تويوتا</span>
                </a>
                <a href="#" class="brand-item" data-brand="هيونداي" onclick="filterByBrand('هيونداي', event)">
                    <!-- Hyundai Styled SVG -->
                    <svg viewBox="0 0 24 24" style="width:28px; height:28px; fill:#3182ce; margin-bottom:4px;"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-2 14h-2v-4H9v4H7V7h2v4h6V7h2v10z"/></svg>
                    <span>هيونداي</span>
                </a>
                <a href="#" class="brand-item" data-brand="مرسيدس" onclick="filterByBrand('مرسيدس', event)">
                    <!-- Mercedes Styled SVG -->
                    <svg viewBox="0 0 24 24" style="width:28px; height:28px; fill:#718096; margin-bottom:4px;"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 4.5c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5-1.5-.67-1.5-1.5.67-1.5 1.5-1.5zm0 11c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4z"/></svg>
                    <span>مرسيدس</span>
                </a>
                <a href="#" class="brand-item" data-brand="لكزس" onclick="filterByBrand('لكزس', event)">
                    <!-- Lexus Styled SVG -->
                    <svg viewBox="0 0 24 24" style="width:28px; height:28px; fill:#dd6b20; margin-bottom:4px;"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                    <span>لكزس</span>
                </a>
                <a href="#" class="brand-item" data-brand="نيسان" onclick="filterByBrand('نيسان', event)">
                    <!-- Nissan Styled SVG -->
                    <svg viewBox="0 0 24 24" style="width:28px; height:28px; fill:#4a5568; margin-bottom:4px;"><path d="M12 22C6.477 22 2 17.523 2 12S6.477 2 12 2s10 4.477 10 10-4.477 10-10 10zm0-2a8 8 0 100-16 8 8 0 000 16zm-5-9h10v2H7v-2z"/></svg>
                    <span>نيسان</span>
                </a>
                <a href="#" class="brand-item" data-brand="فورد" onclick="filterByBrand('فورد', event)">
                    <!-- Ford Styled SVG -->
                    <svg viewBox="0 0 24 24" style="width:28px; height:28px; fill:#2b6cb0; margin-bottom:4px;"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm5 11h-4v4h-2v-4H7v-2h4V7h2v4h4v2z"/></svg>
                    <span>فورد</span>
                </a>
            </div>

            <h3 class="sidebar-title">📁 جميع الأقسام</h3>
            <div class="brand-list-all" id="categories-sidebar">
                <!-- Categories will load dynamically -->
            </div>
        </aside>

        <!-- Main Ads Feed -->
        <main class="animate-fade-in">
            
            <!-- Horizontal Scrollable Category Tabs -->
            <div class="filter-tabs" id="categories-tabs">
                <!-- Dynamic categories loaded via JS -->
            </div>

            <!-- Additional filters bar (Cities + Active brand indicator) -->
            <div class="premium-card" style="display:flex; justify-content:space-between; align-items:center; padding:0.65rem 1rem; margin-bottom:1rem; flex-wrap:wrap; gap:0.5rem;">
                <div style="display:flex; align-items:center; gap:0.5rem;">
                    <span style="font-weight:700; font-size:0.8rem; color:var(--text-muted);">📍 تصفية حسب المدينة:</span>
                    <select id="city-select" class="input-premium" style="width: auto; padding: 0.3rem 0.6rem; font-size:0.8rem;" onchange="loadAds()">
                        <option value="الكل">كل مدن اليمن</option>
                    </select>
                </div>
                
                <div id="brand-filter-indicator" style="font-size:0.8rem; font-weight:700; color:var(--primary); display:none;">
                    النشط: ماركة <span id="active-brand-name" style="color:var(--secondary);"></span>
                    <button onclick="clearBrandFilter(event)" style="background:none; border:none; color:red; cursor:pointer; font-weight:bold; margin-right:4px;">[إلغاء]</button>
                </div>
            </div>

            <!-- Ad Rows (Horizontal List Feed) -->
            <div class="ad-list" id="ads-container">
                <div style="text-align:center; padding:4rem; color:var(--text-muted); font-weight:bold;">جاري تحميل الإعلانات...</div>
            </div>
        </main>
    </div>

    <!-- Core App JS Utilities -->
    <script src="assets/js/app.js"></script>
    <script>
        let currentCat = 'all';
        let currentBrand = '';
        let searchTimeout;

        async function init() {
            // Load Cities
            try {
                const cityRes = await apiRequest('cities');
                const citySelect = document.getElementById('city-select');
                cityRes.data.forEach(city => {
                    const opt = document.createElement('option');
                    opt.value = city;
                    opt.textContent = city;
                    citySelect.appendChild(opt);
                });
            } catch (e) {}

            // Load Categories for tabs and sidebar
            try {
                const catRes = await apiRequest('categories');
                const catTabs = document.getElementById('categories-tabs');
                const catSidebar = document.getElementById('categories-sidebar');
                
                catTabs.innerHTML = '';
                catSidebar.innerHTML = '';

                catRes.data.forEach(cat => {
                    // Create horizontal tab button
                    const tabBtn = document.createElement('button');
                    tabBtn.className = `filter-tab-btn ${cat.id === currentCat ? 'active' : ''}`;
                    tabBtn.innerHTML = `<span>${cat.icon}</span> ${cat.name}`;
                    tabBtn.onclick = () => {
                        document.querySelectorAll('.filter-tab-btn').forEach(b => b.classList.remove('active'));
                        tabBtn.classList.add('active');
                        
                        // Also sync active state in sidebar
                        document.querySelectorAll('.brand-list-all-item').forEach(b => b.classList.remove('active'));
                        const sidebarItem = document.getElementById(`side-cat-${cat.id}`);
                        if (sidebarItem) sidebarItem.classList.add('active');

                        currentCat = cat.id;
                        // When switching categories, clear brand filter if not 'cars'
                        if (currentCat !== 'cars') {
                            clearBrandActiveStyles();
                            currentBrand = '';
                            document.getElementById('brand-filter-indicator').style.display = 'none';
                        }
                        loadAds();
                    };
                    catTabs.appendChild(tabBtn);

                    // Create sidebar item
                    const sideItem = document.createElement('a');
                    sideItem.href = '#';
                    sideItem.id = `side-cat-${cat.id}`;
                    sideItem.className = `brand-list-all-item ${cat.id === currentCat ? 'active' : ''}`;
                    sideItem.innerHTML = `<span>${cat.icon}</span> <span>${cat.name}</span>`;
                    sideItem.onclick = (e) => {
                        e.preventDefault();
                        tabBtn.click(); // trigger same behavior as tab
                    };
                    catSidebar.appendChild(sideItem);
                });
            } catch (e) {}

            // Initial load of ads
            loadAds();
        }

        async function loadAds() {
            const container = document.getElementById('ads-container');
            const city = document.getElementById('city-select').value;
            const q = document.getElementById('search-input').value;

            // Beautiful Pulsing Skeleton Loading Cards
            container.innerHTML = `
                <div class="skeleton-row">
                    <div class="skeleton-row-main">
                        <div class="skeleton-thumb"></div>
                        <div class="skeleton-content">
                            <div class="skeleton-title" style="width: 280px;"></div>
                            <div class="skeleton-meta" style="width: 150px;"></div>
                        </div>
                    </div>
                    <div class="skeleton-side">
                        <div class="skeleton-price"></div>
                        <div class="skeleton-city"></div>
                    </div>
                </div>
            `.repeat(4);

            try {
                const endpoint = `ads&cat=${currentCat}&city=${city}&brand=${currentBrand}&q=${encodeURIComponent(q)}`;
                const res = await apiRequest(endpoint);
                const ads = res.data;
                
                container.innerHTML = '';
                
                if (ads.length === 0) {
                    container.innerHTML = '<div style="text-align:center; padding:4rem; color:var(--text-muted); font-weight:bold;">لم يتم العثور على إعلانات تطابق بحثك.</div>';
                    return;
                }

                ads.forEach(ad => {
                    const row = document.createElement('a');
                    row.href = `ad.php?id=${ad.id}`;
                    row.className = 'ad-row animate-fade-in';
                    
                    // Pinned Badge
                    const pinBadge = ad.isPinned == 1 ? '<span class="badge-pinned">📌 إعلان مثبت</span> ' : '';
                    
                    row.innerHTML = `
                        <div class="ad-row-main">
                            <img class="ad-row-thumb" src="${ad.image}" alt="${ad.title}">
                            <div class="ad-row-content">
                                <h3 class="ad-row-title">${pinBadge}${ad.title}</h3>
                                <div class="ad-row-meta">
                                    <div class="ad-row-meta-item">👤 <span>${ad.userName}</span></div>
                                    <div class="ad-row-meta-item">⏱️ <span>${ad.date}</span></div>
                                </div>
                            </div>
                        </div>
                        <div class="ad-row-side">
                            <div class="ad-row-price">${ad.price}</div>
                            <div class="ad-row-city">📍 ${ad.city}</div>
                        </div>
                    `;
                    container.appendChild(row);
                });

            } catch (e) {
                container.innerHTML = '<div style="text-align:center; padding:4rem; color:var(--text-muted); font-weight:bold;">حدث خطأ أثناء تحميل الإعلانات.</div>';
            }
        }

        function filterByBrand(brand, e) {
            e.preventDefault();
            
            // Switch category to cars if not already there
            const carTab = Array.from(document.querySelectorAll('.filter-tab-btn')).find(b => b.textContent.includes('سيارات'));
            if (carTab) {
                carTab.click();
            }

            clearBrandActiveStyles();
            
            // Add active class to clicked brand
            const activeBrand = document.querySelector(`.brand-item[data-brand="${brand}"]`);
            if (activeBrand) activeBrand.classList.add('active');

            currentBrand = brand;
            
            // Show brand filter indicator
            document.getElementById('active-brand-name').innerText = brand;
            document.getElementById('brand-filter-indicator').style.display = 'block';

            loadAds();
        }

        function clearBrandFilter(e) {
            if (e) e.preventDefault();
            clearBrandActiveStyles();
            currentBrand = '';
            document.getElementById('brand-filter-indicator').style.display = 'none';
            loadAds();
        }

        function clearBrandActiveStyles() {
            document.querySelectorAll('.brand-item').forEach(b => b.classList.remove('active'));
        }

        function debounceSearch() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(loadAds, 500);
        }

        async function logout() {
            try {
                await apiRequest('auth', 'POST', { action: 'logout' });
                window.location.reload();
            } catch (e) {}
        }

        document.addEventListener('DOMContentLoaded', init);
    </script>
</body>
</html>
