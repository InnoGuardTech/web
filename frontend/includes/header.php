<?php
/**
 * frontend/includes/header.php — Premium Unified Header v3.0
 */
require_once __DIR__ . '/icons.php';

if (!defined('PAGE_TITLE')) define('PAGE_TITLE', defined('SITE_NAME') ? SITE_NAME : 'حراج اليمن الفاخر');
if (!defined('PAGE_DESC'))  define('PAGE_DESC',  defined('SITE_SLOGAN') ? SITE_SLOGAN : 'منصة الحراج الأولى في اليمن');
if (!defined('PAGE_OG_IMAGE')) define('PAGE_OG_IMAGE', '');
if (!defined('PAGE_URL'))   define('PAGE_URL', '');
if (!defined('EXTRA_HEAD')) define('EXTRA_HEAD', '');

$_me = (isset($_SESSION['user_id']) && function_exists('getCurrentUser')) ? getCurrentUser() : null;
$_pageName = basename($_SERVER['PHP_SELF'], '.php');
$_unreadMsgs = 0;
$_unreadNotifs = 0;
if ($_me && function_exists('getDBConnection')) {
    try {
        $_pdo = getDBConnection();
        try {
            $s = $_pdo->prepare("SELECT COALESCE(SUM(CASE WHEN buyerId=:u THEN buyerUnread ELSE sellerUnread END), 0) FROM chat_threads WHERE (buyerId=:u OR sellerId=:u)");
            $s->execute([':u' => $_me['id']]);
            $_unreadMsgs = (int)$s->fetchColumn();
        } catch (Throwable $e) {}
        try {
            $s = $_pdo->prepare("SELECT COUNT(*) FROM notifications WHERE userId=:u AND isRead=0");
            $s->execute([':u' => $_me['id']]);
            $_unreadNotifs = (int)$s->fetchColumn();
        } catch (Throwable $e) {}
    } catch (Throwable $e) {}
}
?><!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#3b6cf6">
    <title><?= htmlspecialchars(PAGE_TITLE) ?></title>
    <meta name="description" content="<?= htmlspecialchars(PAGE_DESC) ?>">
    <meta name="keywords" content="حراج, اليمن, بيع, شراء, سيارات, عقارات, إلكترونيات, صنعاء, عدن, تعز, إعلانات مبوبة">
    <meta name="robots" content="index, follow">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= htmlspecialchars(defined('SITE_NAME') ? SITE_NAME : 'حراج اليمن') ?>">
    <meta property="og:title" content="<?= htmlspecialchars(PAGE_TITLE) ?>">
    <meta property="og:description" content="<?= htmlspecialchars(PAGE_DESC) ?>">
    <?php if (PAGE_URL): ?><meta property="og:url" content="<?= htmlspecialchars(PAGE_URL) ?>"><?php endif; ?>
    <?php if (PAGE_OG_IMAGE): ?><meta property="og:image" content="<?= htmlspecialchars(PAGE_OG_IMAGE) ?>"><?php endif; ?>
    <meta property="og:locale" content="ar_YE">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars(PAGE_TITLE) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars(PAGE_DESC) ?>">
    <?php if (PAGE_OG_IMAGE): ?><meta name="twitter:image" content="<?= htmlspecialchars(PAGE_OG_IMAGE) ?>"><?php endif; ?>

    <link rel="icon" type="image/svg+xml" href='data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><rect width="64" height="64" rx="14" fill="%233b6cf6"/><text x="32" y="42" font-family="Arial" font-size="32" font-weight="900" fill="white" text-anchor="middle">ح</text></svg>'>
    <link rel="apple-touch-icon" href='data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 180 180"><rect width="180" height="180" rx="40" fill="%233b6cf6"/><text x="90" y="120" font-family="Arial" font-size="90" font-weight="900" fill="white" text-anchor="middle">ح</text></svg>'>
    <link rel="manifest" href="manifest.json">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="حراج اليمن">
    <meta name="format-detection" content="telephone=no">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= @filemtime(__DIR__ . '/../assets/css/style.css') ?: time() ?>">
    <link rel="stylesheet" href="assets/css/modern-ui.css?v=<?= @filemtime(__DIR__ . '/../assets/css/modern-ui.css') ?: time() ?>">
    <link rel="stylesheet" href="assets/css/improvements.css?v=<?= @filemtime(__DIR__ . '/../assets/css/improvements.css') ?: time() ?>">
    <?= EXTRA_HEAD ?>
    <script>
        (function() {
            const saved = localStorage.getItem('theme');
            if (saved === 'dark' || (!saved && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
        })();
        window.CURRENT_USER = <?= $_me ? json_encode(['id'=>$_me['id'],'name'=>$_me['name'],'role'=>$_me['role']]) : 'null' ?>;
        window.CSRF_TOKEN = "<?= function_exists('csrfToken') ? csrfToken() : '' ?>";
    </script>
</head>
<body>

<header class="site-header">
    <div class="container header-inner">
        <a href="index.php" class="logo" aria-label="الصفحة الرئيسية">
            <span class="logo-mark"><?= icon('logo', ['size'=>20]) ?></span>
            <span class="logo-text">
                حراج اليمن
                <small>سوق الإعلانات الأول</small>
            </span>
        </a>

        <form class="header-search" method="get" action="index.php" role="search">
            <input type="search" name="q" placeholder="ابحث عن سيارة، عقار، جوال..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" aria-label="بحث">
            <button type="submit" class="search-btn" aria-label="بحث"><?= icon('search', ['size'=>18]) ?></button>
        </form>

        <div class="header-actions">
            <a href="post.php" class="btn-post-ad" title="أضف إعلانًا">
                <?= icon('plus', ['size'=>18]) ?>
                <span>أضف إعلان</span>
            </a>

            <button class="icon-btn" onclick="toggleTheme()" aria-label="تبديل الوضع" title="تبديل الوضع المظلم/الفاتح" id="themeToggleBtn">
                <span id="themeIconLight" style="display:none;"><?= icon('sun', ['size'=>20]) ?></span>
                <span id="themeIconDark"><?= icon('moon', ['size'=>20]) ?></span>
            </button>

            <?php if ($_me): ?>
                <a href="notifications.php" class="icon-btn" aria-label="الإشعارات" title="الإشعارات">
                    <?= icon('bell', ['size'=>20]) ?>
                    <?php if ($_unreadNotifs > 0): ?>
                        <span class="badge"><?= $_unreadNotifs > 9 ? '9+' : $_unreadNotifs ?></span>
                    <?php endif; ?>
                </a>
                <a href="messages.php" class="icon-btn" aria-label="الرسائل" title="الرسائل">
                    <?= icon('message', ['size'=>20]) ?>
                    <?php if ($_unreadMsgs > 0): ?>
                        <span class="badge"><?= $_unreadMsgs > 9 ? '9+' : $_unreadMsgs ?></span>
                    <?php endif; ?>
                </a>

                <div class="user-menu" onclick="toggleUserDropdown()" id="userMenuBtn">
                    <div class="avatar-circle" style="width:32px;height:32px;font-size:13px;">
                        <?= mb_substr($_me['name'] ?? 'م', 0, 1, 'UTF-8') ?>
                    </div>
                    <span class="name"><?= htmlspecialchars(mb_substr($_me['name'] ?? '', 0, 8, 'UTF-8')) ?></span>
                </div>
            <?php else: ?>
                <a href="auth.php" class="btn btn-secondary btn-sm" style="margin-inline-start:6px;">
                    <?= icon('log-in', ['size'=>16]) ?>
                    <span>دخول</span>
                </a>
            <?php endif; ?>
        </div>
    </div>
</header>

<?php if ($_me): ?>
<div id="userDropdown" style="display:none;position:fixed;top:62px;inset-inline-end:20px;background:var(--surface);border:1px solid var(--line);border-radius:14px;box-shadow:var(--sh-lg);padding:8px;min-width:240px;z-index:200;">
    <div style="padding:14px 14px 10px;border-bottom:1px solid var(--line-soft);margin-bottom:6px;">
        <div style="font-weight:700;font-size:14px;"><?= htmlspecialchars($_me['name']) ?></div>
        <div style="font-size:12px;color:var(--muted);margin-top:2px;"><?= htmlspecialchars($_me['phone'] ?? '') ?></div>
    </div>
    <a href="user.php?id=<?= $_me['id'] ?>" class="dropdown-item"><?= icon('user', ['size'=>16]) ?> ملفي الشخصي</a>
    <a href="my_ads.php" class="dropdown-item"><?= icon('list', ['size'=>16]) ?> إعلاناتي</a>
    <a href="favorites.php" class="dropdown-item"><?= icon('heart', ['size'=>16]) ?> المفضلة</a>
    <a href="settings.php" class="dropdown-item"><?= icon('settings', ['size'=>16]) ?> الإعدادات</a>
    <?php if (($_me['role'] ?? '') === 'admin'): ?>
        <a href="admin-enhanced.php" class="dropdown-item"><?= icon('dashboard', ['size'=>16]) ?> لوحة الإدارة</a>
    <?php endif; ?>
    <div style="border-top:1px solid var(--line-soft);margin-top:6px;padding-top:6px;">
        <a href="#" onclick="event.preventDefault();logout()" class="dropdown-item" style="color:var(--danger);"><?= icon('log-out', ['size'=>16]) ?> تسجيل خروج</a>
    </div>
</div>
<style>
.dropdown-item{display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:8px;font-size:13.5px;color:var(--text-soft);transition:all .15s;}
.dropdown-item:hover{background:var(--bg-soft);color:var(--text);}
</style>
<?php endif; ?>

<div class="toast-container" id="toastContainer"></div>

<main class="container" style="padding-top:var(--sp-5);padding-bottom:var(--sp-8);">
