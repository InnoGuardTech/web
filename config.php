<?php
/**
 * ============================================================
 * حراج اليمن - ملف الإعدادات الرئيسي (v2.0)
 * ============================================================
 * - يقرأ من .env بدلاً من Hardcoded
 * - يجلسة آمنة (HttpOnly + SameSite)
 * - دوال مساعدة موحدة
 */

require_once __DIR__ . '/backend/lib/env.php';
require_once __DIR__ . '/backend/lib/security.php';
require_once __DIR__ . '/backend/lib/upload.php';
require_once __DIR__ . '/backend/lib/mailer.php';

// ---------- إعدادات قاعدة البيانات ----------
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_PORT', env('DB_PORT', '3306'));
define('DB_NAME', env('DB_NAME', 'haraj_db'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));
define('DB_CHARSET', env('DB_CHARSET', 'utf8mb4'));

// ---------- إعدادات المنصة ----------
define('SITE_NAME', 'حراج اليمن');
define('SITE_SLOGAN', 'أكبر منصة بيع وشراء في الجمهورية اليمنية');
define('SITE_CURRENCY', 'ريال يمني');
define('SITE_CURRENCY_SHORT', 'ر.ي');
define('COMMISSION_RATE', (float) env('COMMISSION_RATE', 0.01));
define('APP_DEBUG', (bool) env('APP_DEBUG', false));
define('APP_URL', env('APP_URL', 'http://localhost'));

// ---------- خطأ + جلسة آمنة ----------
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ERROR | E_PARSE);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

startSecureSession();

// ---------- المدن اليمنية ----------
define('YEMEN_CITIES', serialize([
    'صنعاء', 'عدن', 'تعز', 'الحديدة', 'إب', 'ذمار', 'المكلا', 'حضرموت',
    'عمران', 'صعدة', 'مأرب', 'البيضاء', 'لحج', 'أبين', 'شبوة', 'الضالع',
    'حجة', 'المحويت', 'ريمة', 'الجوف', 'سقطرى'
]));

// ---------- اتصال DB ----------
function getDBConnection() {
    static $pdo = null;
    if ($pdo) return $pdo;

    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]);
        return $pdo;
    } catch (PDOException $e) {
        // محاولة إنشاء قاعدة البيانات تلقائياً
        try {
            $tempPdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET,
                              DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            // تشغيل setup
            require_once __DIR__ . '/scripts/db_setup.php';

            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false
            ]);
            return $pdo;
        } catch (PDOException $e2) {
            if (APP_DEBUG) {
                die('❌ فشل الاتصال بقاعدة البيانات: ' . $e2->getMessage());
            }
            die('❌ فشل الاتصال بقاعدة البيانات. يرجى المحاولة لاحقاً.');
        }
    }
}

// ---------- مستخدم الجلسة ----------
function getCurrentUser($db = null) {
    if (!isset($_SESSION['user_id'])) return null;
    $db = $db ?: getDBConnection();
    $stmt = $db->prepare("SELECT id, name, phone, email, avatar, role, rating, isBanned, joinedDate, createdAt FROM users WHERE id = ? AND isBanned = 0");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// ---------- Sanitize ----------
function sanitize($input) {
    if (is_array($input)) return array_map('sanitize', $input);
    return htmlspecialchars(strip_tags(trim($input ?? '')), ENT_QUOTES, 'UTF-8');
}

// ---------- تنسيق السعر ----------
function formatPrice($price) {
    if (!$price || $price <= 0) return 'السعر عند التواصل';
    return number_format((float)$price, 0) . ' ' . SITE_CURRENCY_SHORT;
}

// ---------- تنسيق التاريخ العربي ----------
function formatArabicDate($datetime) {
    if (empty($datetime)) return '';
    $timestamp = is_numeric($datetime) ? $datetime : strtotime($datetime);
    $diff = time() - $timestamp;

    if ($diff < 60) return 'الآن';
    if ($diff < 3600) return 'منذ ' . floor($diff / 60) . ' دقيقة';
    if ($diff < 86400) return 'منذ ' . floor($diff / 3600) . ' ساعة';
    if ($diff < 604800) return 'منذ ' . floor($diff / 86400) . ' يوم';

    $months = ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'];
    return date('d', $timestamp) . ' ' . $months[date('n', $timestamp) - 1] . ' ' . date('Y', $timestamp);
}

function nl2br_clean($text) {
    return nl2br(htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8'));
}

function getCities() {
    return unserialize(YEMEN_CITIES);
}

function getCategoryIcon($cat) {
    $icons = [
        'cars'=>'🚗', 'realestate'=>'🏠', 'electronics'=>'📱',
        'livestock'=>'🐏', 'furniture'=>'🪑', 'jobs'=>'💼',
        'services'=>'🔧', 'other'=>'📦'
    ];
    return $icons[$cat] ?? '📦';
}

function getCategoryName($cat) {
    $names = [
        'cars'=>'سيارات','realestate'=>'عقارات','electronics'=>'إلكترونيات',
        'livestock'=>'مواشي وحيوانات','furniture'=>'أثاث ومفروشات',
        'jobs'=>'وظائف','services'=>'خدمات','other'=>'أخرى'
    ];
    return $names[$cat] ?? 'أخرى';
}

// ---------- Slugify للـ SEO URLs ----------
function makeSlug($text, $maxLength = 50) {
    if (empty($text)) return '';
    // إزالة الحركات العربية
    $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}]/u', '', $text);
    // استبدال المسافات والشرطات بشرطة
    $text = preg_replace('/[\s\-_]+/u', '-', $text);
    // إزالة أي شيء غير حروف/أرقام/شرطة (الحفاظ على العربي)
    $text = preg_replace('/[^\p{L}\p{N}\-]+/u', '', $text);
    $text = trim($text, '-');
    if (mb_strlen($text) > $maxLength) {
        $text = mb_substr($text, 0, $maxLength);
        $text = rtrim($text, '-');
    }
    return $text ?: 'ad';
}

// ---------- إنشاء URL إعلان SEO-friendly ----------
function adUrl($adId, $title = '') {
    $slug = makeSlug($title);
    return "ad.php?id=$adId" . ($slug ? "&slug=$slug" : '');
}

// ---------- صورة الأفاتار الافتراضية ----------
function avatarUrl($user) {
    if (!empty($user['avatar'])) return imageUrl($user['avatar']);
    // SVG افتراضي بالأحرف الأولى
    $name = $user['name'] ?? '؟';
    $initial = mb_substr($name, 0, 1);
    $colors = ['#0F2942','#0D9488','#C5A059','#7C3AED','#DC2626','#2563EB'];
    $color = $colors[crc32($name) % count($colors)];
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64"><rect width="64" height="64" fill="' . $color . '"/><text x="50%" y="55%" font-size="32" fill="white" text-anchor="middle" font-family="Cairo,Arial">' . htmlspecialchars($initial) . '</text></svg>';
    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

// ---------- صورة الإعلان الافتراضية ----------
function defaultAdImage($category = 'other') {
    $emoji = getCategoryIcon($category);
    $color = '#0F2942';
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 300"><rect width="400" height="300" fill="' . $color . '"/><text x="50%" y="55%" font-size="100" text-anchor="middle">' . $emoji . '</text></svg>';
    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

// ---------- استخراج أول صورة من قائمة JSON ----------
function firstImage($imagesJson, $category = 'other') {
    if (empty($imagesJson)) return defaultAdImage($category);
    $imgs = is_array($imagesJson) ? $imagesJson : json_decode($imagesJson, true);
    if (empty($imgs) || !is_array($imgs)) return defaultAdImage($category);
    return imageUrl($imgs[0]);
}
