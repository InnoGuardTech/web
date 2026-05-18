<?php
// config.php - الإعدادات الأساسية لمنصة حراج اليمن الفاخر
// ============================================================

// بدء الجلسة الآمنة
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// إعدادات قاعدة البيانات MySQL عبر WampServer
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'haraj_db');
define('DB_CHARSET', 'utf8mb4');

// إعدادات المنصة
define('SITE_NAME', 'حراج اليمن');
define('SITE_SLOGAN', 'أكبر منصة بيع وشراء في الجمهورية اليمنية');
define('SITE_CURRENCY', 'ريال يمني');
define('SITE_CURRENCY_SHORT', 'ر.ي');
define('COMMISSION_RATE', 0.01); // 1%

// المدن اليمنية الرئيسية
define('YEMEN_CITIES', serialize([
    'صنعاء', 'عدن', 'تعز', 'الحديدة', 'إب', 'ذمار', 'المكلا', 'حضرموت',
    'عمران', 'صعدة', 'مأرب', 'البيضاء', 'لحج', 'أبين', 'شبوة', 'الضالع'
]));

/**
 * اتصال PDO آمن بقاعدة البيانات
 */
function getDBConnection() {
    try {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        // محاولة اتصال بدون اسم قاعدة بيانات (للتثبيت الأول)
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';charset=' . DB_CHARSET;
            $tempPdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            
            // تحقق إذا كانت القاعدة غير موجودة، قم بإنشائها
            $stmt = $tempPdo->query("SHOW DATABASES LIKE '" . DB_NAME . "'");
            if ($stmt->rowCount() == 0) {
                ob_start();
                require_once __DIR__ . '/scripts/db_setup.php';
                ob_end_clean();
                
                $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
                return new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            }
            return $tempPdo;
        } catch (PDOException $e2) {
            die('❌ فشل الاتصال بخادم MySQL. تأكد من تشغيل WampServer: ' . $e2->getMessage());
        }
    }
}

/**
 * جلب بيانات المستخدم الحالي من الجلسة
 */
function getCurrentUser($db) {
    if (!isset($_SESSION['user_id'])) return null;
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND isBanned = 0");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

/**
 * تنظيف المدخلات من XSS
 */
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input ?? '')), ENT_QUOTES, 'UTF-8');
}

/**
 * تنسيق الأسعار بالريال اليمني
 */
function formatPrice($price) {
    if (!$price || $price <= 0) return 'السعر عند التواصل';
    return number_format($price, 0) . ' ' . SITE_CURRENCY_SHORT;
}

/**
 * تنسيق التاريخ بالعربية
 */
function formatArabicDate($datetime) {
    if (empty($datetime)) return '';
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;

    if ($diff < 60) return 'الآن';
    if ($diff < 3600) return 'منذ ' . floor($diff / 60) . ' دقيقة';
    if ($diff < 86400) return 'منذ ' . floor($diff / 3600) . ' ساعة';
    if ($diff < 604800) return 'منذ ' . floor($diff / 86400) . ' يوم';

    $months = ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'];
    return date('d', $timestamp) . ' ' . $months[date('n', $timestamp) - 1] . ' ' . date('Y', $timestamp);
}

/**
 * تنظيف النص مع الحفاظ على الأسطر
 */
function nl2br_clean($text) {
    return nl2br(htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8'));
}

/**
 * المدن اليمنية كمصفوفة
 */
function getCities() {
    return unserialize(YEMEN_CITIES);
}

/**
 * الحصول على رمز التصنيف
 */
function getCategoryIcon($cat) {
    $icons = [
        'cars' => '🚗', 'realestate' => '🏠', 'electronics' => '📱',
        'livestock' => '🐏', 'furniture' => '🪑', 'jobs' => '💼',
        'services' => '🔧', 'other' => '📦'
    ];
    return $icons[$cat] ?? '📦';
}

/**
 * اسم التصنيف بالعربية
 */
function getCategoryName($cat) {
    $names = [
        'cars' => 'سيارات', 'realestate' => 'عقارات', 'electronics' => 'إلكترونيات',
        'livestock' => 'مواشي وحيوانات', 'furniture' => 'أثاث ومفروشات',
        'jobs' => 'وظائف', 'services' => 'خدمات', 'other' => 'أخرى'
    ];
    return $names[$cat] ?? 'أخرى';
}
