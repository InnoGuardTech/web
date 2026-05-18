<?php
/**
 * frontend/includes/header.php - الرأس المشترك بين الصفحات
 */
if (!defined('PAGE_TITLE')) define('PAGE_TITLE', SITE_NAME);
if (!defined('PAGE_DESC')) define('PAGE_DESC', SITE_SLOGAN);
if (!defined('PAGE_OG_IMAGE')) define('PAGE_OG_IMAGE', '');
if (!defined('PAGE_URL')) define('PAGE_URL', '');
if (!defined('EXTRA_CSS')) define('EXTRA_CSS', '');
?><!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="theme-color" content="#0F2942">

    <title><?= htmlspecialchars(PAGE_TITLE) ?></title>
    <meta name="description" content="<?= htmlspecialchars(PAGE_DESC) ?>">
    <meta name="keywords" content="حراج, اليمن, بيع, شراء, سيارات, عقارات, إلكترونيات, صنعاء, عدن, تعز, مبوبة, إعلانات">
    <meta name="author" content="<?= SITE_NAME ?>">
    <meta name="robots" content="index, follow">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= SITE_NAME ?>">
    <meta property="og:title" content="<?= htmlspecialchars(PAGE_TITLE) ?>">
    <meta property="og:description" content="<?= htmlspecialchars(PAGE_DESC) ?>">
    <?php if (PAGE_URL): ?><meta property="og:url" content="<?= htmlspecialchars(PAGE_URL) ?>"><?php endif; ?>
    <?php if (PAGE_OG_IMAGE): ?><meta property="og:image" content="<?= htmlspecialchars(PAGE_OG_IMAGE) ?>"><?php endif; ?>
    <meta property="og:locale" content="ar_YE">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars(PAGE_TITLE) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars(PAGE_DESC) ?>">
    <?php if (PAGE_OG_IMAGE): ?><meta name="twitter:image" content="<?= htmlspecialchars(PAGE_OG_IMAGE) ?>"><?php endif; ?>

    <!-- Favicon SVG -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Crect width='100' height='100' rx='20' fill='%230F2942'/%3E%3Ctext x='50%25' y='62%25' font-size='54' text-anchor='middle' fill='%23C5A059' font-family='Cairo,Arial' font-weight='900'%3Eح%3C/text%3E%3C/svg%3E">

    <!-- Preconnect -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <?= EXTRA_CSS ?>
</head>
<body>

<?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
<div class="admin-banner">
    🛡️ أنت في وضع الإدارة <a href="admin.php">الذهاب للوحة التحكم ⚙️</a>
</div>
<?php endif; ?>

<header class="glass-header">
    <div class="header-container">
        <?php if (defined('SHOW_SIDEBAR_TOGGLE') && SHOW_SIDEBAR_TOGGLE): ?>
        <button id="sidebar-toggle-btn" class="sidebar-toggle-btn" aria-label="القائمة">☰</button>
        <?php endif; ?>

        <a href="index.php" class="header-logo" aria-label="<?= SITE_NAME ?>">
            <span class="header-logo-badge">حراج</span>
            <span>اليمن</span>
        </a>

        <?php if (!defined('HIDE_SEARCH') || !HIDE_SEARCH): ?>
        <div class="header-search">
            <input type="text" id="header-search-input" placeholder="ابحث عن سيارة، عقار، جهاز..." aria-label="بحث">
            <button onclick="window.location.href='index.php?q='+encodeURIComponent(document.getElementById('header-search-input').value)" aria-label="بحث">🔍</button>
        </div>
        <?php endif; ?>

        <div class="header-actions" id="user-menu-area">
            <button onclick="toggleTheme()" class="header-icon-btn" title="تبديل الوضع" aria-label="تبديل الوضع">🌓</button>

            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="post.php" class="btn-gold">+ أضف إعلان</a>

                <a href="notifications.php" class="header-icon-btn" title="الإشعارات" aria-label="الإشعارات">
                    🔔<span id="notif-badge" class="header-badge" style="display:none;">0</span>
                </a>

                <a href="messages.php" class="header-icon-btn" title="الرسائل" aria-label="الرسائل">
                    💬<span id="chat-badge" class="header-badge" style="display:none;">0</span>
                </a>

                <a href="favorites.php" class="header-icon-btn" title="المفضلة">❤️</a>

                <div style="position:relative;" id="user-menu-wrap">
                    <button id="user-menu-btn" class="header-icon-btn" title="حسابي" aria-label="حسابي">👤</button>
                    <div id="user-menu-dropdown" style="display:none; position:absolute; top:calc(100% + 8px); left:0; min-width:200px; background:var(--card-bg); border:1px solid var(--border-color); border-radius:var(--radius-lg); box-shadow:var(--shadow-xl); overflow:hidden; z-index:1001;">
                        <a href="user.php?id=<?= (int)$_SESSION['user_id'] ?>" style="display:block; padding:0.75rem 1rem; color:var(--text-main); font-weight:700; font-size:0.85rem; border-bottom:1px solid var(--border-color);">👤 ملفي الشخصي</a>
                        <a href="my_ads.php" style="display:block; padding:0.75rem 1rem; color:var(--text-main); font-weight:700; font-size:0.85rem; border-bottom:1px solid var(--border-color);">📋 إعلاناتي</a>
                        <a href="settings.php" style="display:block; padding:0.75rem 1rem; color:var(--text-main); font-weight:700; font-size:0.85rem; border-bottom:1px solid var(--border-color);">⚙️ الإعدادات</a>
                        <a href="commission.php" style="display:block; padding:0.75rem 1rem; color:var(--text-main); font-weight:700; font-size:0.85rem; border-bottom:1px solid var(--border-color);">💰 دفع العمولة</a>
                        <button onclick="logout()" style="display:block; width:100%; text-align:right; padding:0.75rem 1rem; color:var(--danger); font-weight:800; font-size:0.85rem; background:none; border:none; cursor:pointer; font-family:inherit;">🚪 تسجيل الخروج</button>
                    </div>
                </div>
                <script>
                    (function(){
                        const btn = document.getElementById('user-menu-btn');
                        const dd = document.getElementById('user-menu-dropdown');
                        if (!btn) return;
                        btn.addEventListener('click', (e) => { e.stopPropagation(); dd.style.display = dd.style.display === 'block' ? 'none' : 'block'; });
                        document.addEventListener('click', () => dd.style.display = 'none');
                    })();
                </script>
            <?php else: ?>
                <a href="auth.php" class="btn-gold">دخول / تسجيل</a>
            <?php endif; ?>
        </div>
    </div>
</header>
