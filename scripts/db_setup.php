<?php
/**
 * scripts/db_setup.php
 * إعداد قاعدة البيانات الكاملة لمشروع حراج اليمن (v2.0)
 */
require_once __DIR__ . '/../config.php';

try {
    // الاتصال بدون اسم قاعدة بيانات أولاً لإنشائها
    $tempPdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET,
                       DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    $pdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
                   DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    // إسقاط الجداول القديمة بترتيب صحيح (يُستخدم فقط عند الإعداد الأولي)
    $isFreshSetup = (PHP_SAPI === 'cli' && in_array('--fresh', $argv ?? [], true)) ||
                    (isset($_GET['fresh']) && $_GET['fresh'] === 'yes');

    if ($isFreshSetup) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        $tables = ['otp_codes','password_resets','message_reads','typing_status','user_presence',
                   'comments','favorites','message_attachments','messages','chat_threads',
                   'reviews','notifications','reports','commission_transfers','blacklist',
                   'ads','users'];
        foreach ($tables as $t) {
            $pdo->exec("DROP TABLE IF EXISTS `$t`");
        }
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        echo "✓ تم حذف الجداول القديمة\n";
    }

    // ============ USERS ============
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        phone VARCHAR(20) UNIQUE NOT NULL,
        email VARCHAR(150) UNIQUE DEFAULT NULL,
        password VARCHAR(255) NOT NULL,
        avatar VARCHAR(500) DEFAULT NULL,
        bio TEXT DEFAULT NULL,
        role ENUM('user','seller','admin','moderator') DEFAULT 'user',
        rating FLOAT DEFAULT 5.0,
        ratingCount INT DEFAULT 0,
        isBanned TINYINT(1) DEFAULT 0,
        isPhoneVerified TINYINT(1) DEFAULT 0,
        isEmailVerified TINYINT(1) DEFAULT 0,
        lastSeenAt DATETIME DEFAULT NULL,
        deletedAt DATETIME DEFAULT NULL,
        joinedDate VARCHAR(20) NOT NULL,
        createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
        updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_phone (phone),
        INDEX idx_email (email),
        INDEX idx_role_banned (role, isBanned)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // ============ ADS ============
    $pdo->exec("CREATE TABLE IF NOT EXISTS ads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        userId INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(255) DEFAULT NULL,
        description TEXT NOT NULL,
        category VARCHAR(50) NOT NULL,
        city VARCHAR(50) NOT NULL,
        price DECIMAL(15,2) DEFAULT NULL,
        images JSON,
        specifications JSON,
        carBrand VARCHAR(50) DEFAULT NULL,
        carYear VARCHAR(4) DEFAULT NULL,
        carTransmission VARCHAR(20) DEFAULT NULL,
        carMileage INT DEFAULT NULL,
        propertyType VARCHAR(50) DEFAULT NULL,
        propertyRooms VARCHAR(20) DEFAULT NULL,
        propertyContract VARCHAR(50) DEFAULT NULL,
        latitude DECIMAL(10,7) DEFAULT NULL,
        longitude DECIMAL(10,7) DEFAULT NULL,
        locationName VARCHAR(255) DEFAULT NULL,
        views INT DEFAULT 0,
        isPinned TINYINT(1) DEFAULT 0,
        pinnedUntil DATETIME DEFAULT NULL,
        status ENUM('active','sold','archived','pending','rejected','deleted') DEFAULT 'active',
        bumpedAt DATETIME DEFAULT NULL,
        createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
        updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_category_status (category, status),
        INDEX idx_city (city),
        INDEX idx_user (userId),
        INDEX idx_status_pinned (status, isPinned, bumpedAt),
        INDEX idx_price (price),
        FULLTEXT idx_search (title, description)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // ============ COMMENTS / OFFERS ============
    $pdo->exec("CREATE TABLE IF NOT EXISTS comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        adId INT NOT NULL,
        userId INT DEFAULT NULL,
        username VARCHAR(100) NOT NULL,
        content TEXT NOT NULL,
        type ENUM('comment','offer') DEFAULT 'comment',
        offerAmount DECIMAL(15,2) DEFAULT NULL,
        createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (adId) REFERENCES ads(id) ON DELETE CASCADE,
        FOREIGN KEY (userId) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_ad (adId)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // ============ FAVORITES ============
    $pdo->exec("CREATE TABLE IF NOT EXISTS favorites (
        id INT AUTO_INCREMENT PRIMARY KEY,
        userId INT NOT NULL,
        adId INT NOT NULL,
        createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (adId) REFERENCES ads(id) ON DELETE CASCADE,
        UNIQUE KEY unique_fav (userId, adId),
        INDEX idx_user (userId)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // ============ CHAT THREADS ============
    $pdo->exec("CREATE TABLE IF NOT EXISTS chat_threads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        adId INT NOT NULL,
        buyerId INT NOT NULL,
        sellerId INT NOT NULL,
        lastMessageId INT DEFAULT NULL,
        lastMessageAt DATETIME DEFAULT NULL,
        buyerUnread INT DEFAULT 0,
        sellerUnread INT DEFAULT 0,
        deletedBy JSON DEFAULT NULL,
        createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (adId) REFERENCES ads(id) ON DELETE CASCADE,
        FOREIGN KEY (buyerId) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (sellerId) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_thread (adId, buyerId),
        INDEX idx_buyer (buyerId),
        INDEX idx_seller (sellerId)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // ============ MESSAGES ============
    $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        threadId INT NOT NULL,
        senderId INT NOT NULL,
        text TEXT NOT NULL,
        type ENUM('text','image','file','offer','system') DEFAULT 'text',
        attachment VARCHAR(500) DEFAULT NULL,
        isRead TINYINT(1) DEFAULT 0,
        readAt DATETIME DEFAULT NULL,
        isDeleted TINYINT(1) DEFAULT 0,
        createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (threadId) REFERENCES chat_threads(id) ON DELETE CASCADE,
        FOREIGN KEY (senderId) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_thread (threadId, createdAt),
        INDEX idx_sender (senderId)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // ============ TYPING STATUS ============
    $pdo->exec("CREATE TABLE IF NOT EXISTS typing_status (
        threadId INT NOT NULL,
        userId INT NOT NULL,
        updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (threadId, userId),
        FOREIGN KEY (threadId) REFERENCES chat_threads(id) ON DELETE CASCADE,
        FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // ============ USER PRESENCE (Online/Offline) ============
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_presence (
        userId INT PRIMARY KEY,
        status ENUM('online','offline','away') DEFAULT 'offline',
        lastSeenAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // ============ NOTIFICATIONS ============
    $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        userId INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        type ENUM('comment','message','offer','admin','system','favorite','review') DEFAULT 'system',
        link VARCHAR(500) DEFAULT NULL,
        isRead TINYINT(1) DEFAULT 0,
        createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_read (userId, isRead, createdAt)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // ============ REVIEWS ============
    $pdo->exec("CREATE TABLE IF NOT EXISTS reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        targetUserId INT NOT NULL,
        authorUserId INT DEFAULT NULL,
        authorName VARCHAR(100) NOT NULL,
        rating INT DEFAULT 5,
        content TEXT NOT NULL,
        createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (targetUserId) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (authorUserId) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_target (targetUserId)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // ============ REPORTS ============
    $pdo->exec("CREATE TABLE IF NOT EXISTS reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        adId INT DEFAULT NULL,
        userId INT DEFAULT NULL,
        reporterId INT NOT NULL,
        reason VARCHAR(100) NOT NULL,
        details TEXT DEFAULT NULL,
        status ENUM('pending','resolved','dismissed') DEFAULT 'pending',
        resolvedBy INT DEFAULT NULL,
        resolvedAt DATETIME DEFAULT NULL,
        createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (adId) REFERENCES ads(id) ON DELETE CASCADE,
        FOREIGN KEY (reporterId) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_status (status),
        INDEX idx_ad (adId)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // ============ COMMISSION TRANSFERS ============
    $pdo->exec("CREATE TABLE IF NOT EXISTS commission_transfers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        userId INT NOT NULL,
        adId INT DEFAULT NULL,
        amount DECIMAL(15,2) NOT NULL,
        bankName VARCHAR(100) DEFAULT NULL,
        transferDate VARCHAR(20) DEFAULT NULL,
        proofImage VARCHAR(500) DEFAULT NULL,
        notes TEXT DEFAULT NULL,
        status ENUM('pending','approved','rejected') DEFAULT 'pending',
        reviewedBy INT DEFAULT NULL,
        reviewedAt DATETIME DEFAULT NULL,
        createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (adId) REFERENCES ads(id) ON DELETE SET NULL,
        INDEX idx_status (status),
        INDEX idx_user (userId)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // ============ BLACKLIST ============
    $pdo->exec("CREATE TABLE IF NOT EXISTS blacklist (
        id INT AUTO_INCREMENT PRIMARY KEY,
        phone VARCHAR(20) DEFAULT NULL,
        email VARCHAR(150) DEFAULT NULL,
        ip VARCHAR(45) DEFAULT NULL,
        reason TEXT NOT NULL,
        addedBy INT DEFAULT NULL,
        createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_phone (phone),
        INDEX idx_email (email),
        INDEX idx_ip (ip)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // ============ OTP CODES ============
    $pdo->exec("CREATE TABLE IF NOT EXISTS otp_codes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        phone VARCHAR(20) DEFAULT NULL,
        email VARCHAR(150) DEFAULT NULL,
        code VARCHAR(10) NOT NULL,
        purpose ENUM('verify_phone','verify_email','reset_password','login') NOT NULL,
        attempts INT DEFAULT 0,
        isUsed TINYINT(1) DEFAULT 0,
        expiresAt DATETIME NOT NULL,
        createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_phone_purpose (phone, purpose),
        INDEX idx_expires (expiresAt)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // ============ PASSWORD RESETS ============
    $pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        userId INT NOT NULL,
        token VARCHAR(255) NOT NULL UNIQUE,
        expiresAt DATETIME NOT NULL,
        isUsed TINYINT(1) DEFAULT 0,
        createdAt DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_token (token)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // ============ AD VIEW STATS ============
    $pdo->exec("CREATE TABLE IF NOT EXISTS ad_view_stats (
        id INT AUTO_INCREMENT PRIMARY KEY,
        adId INT NOT NULL,
        viewerId INT DEFAULT NULL,
        ip VARCHAR(45) DEFAULT NULL,
        viewedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (adId) REFERENCES ads(id) ON DELETE CASCADE,
        INDEX idx_ad_date (adId, viewedAt)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // ============ بذر البيانات الأولية ============
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $hashed = password_hash('Admin@123', PASSWORD_DEFAULT);
        $hashedUser = password_hash('User@123', PASSWORD_DEFAULT);
        $joined = date('Y-m');

        $insertUser = $pdo->prepare("INSERT INTO users (name, phone, email, password, role, rating, ratingCount, isPhoneVerified, joinedDate)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?)");

        $insertUser->execute(['أحمد الإداري', '777111111', 'admin@haraj.ye', $hashed, 'admin', 5.0, 12, $joined]);
        $insertUser->execute(['محمد بائع', '777222222', null, $hashedUser, 'seller', 4.8, 8, $joined]);
        $insertUser->execute(['علي البيع والشراء', '777333333', null, $hashedUser, 'seller', 5.0, 5, $joined]);
        $insertUser->execute(['سارة المشتري', '777444444', null, $hashedUser, 'user', 4.5, 3, $joined]);
        $insertUser->execute(['خالد التاجر', '777555555', null, $hashedUser, 'seller', 4.9, 15, $joined]);

        echo "✓ تم إضافة 5 مستخدمين تجريبيين\n";

        // إعلانات تجريبية
        $sampleAds = [
            [2, 'تويوتا كامري 2020 فل كامل', 'cars', 'صنعاء', 25000000, 'تويوتا', '2020', 'أوتوماتيك', 45000, 'سيارة بحالة ممتازة، أصلية، صيانة دورية، اللون أبيض، فل كامل بدون حوادث.'],
            [3, 'شقة فخمة للإيجار 4 غرف في حي الصافية', 'realestate', 'صنعاء', 350000, null, null, null, null, 'شقة جديدة 4 غرف وصالة ومطبخ و2 حمام، تشطيب لوكس، إطلالة مميزة، قرب الخدمات.'],
            [2, 'آيفون 14 برو ماكس 256GB', 'electronics', 'عدن', 950000, null, null, null, null, 'بحالة الجديد، مع جميع ملحقاته الأصلية، اللون أزرق، البطارية 100%.'],
            [5, 'هيونداي إلنترا 2018', 'cars', 'تعز', 18500000, 'هيونداي', '2018', 'أوتوماتيك', 80000, 'سيارة عائلية اقتصادية، قير أوتوماتيك، كهرباء كاملة، حالة ممتازة.'],
            [3, 'بقرة حلوب فريزيان عالية الإنتاج', 'livestock', 'الحديدة', 1200000, null, null, null, null, 'بقرة فريزيان أصلية، إنتاجها 25 لتر يومياً، حامل بشهر سابع.'],
            [5, 'لاب توب MacBook Pro M2', 'electronics', 'صنعاء', 1850000, null, null, null, null, 'MacBook Pro M2 شاشة 14 بوصة، 16GB RAM، 512GB SSD، استخدام خفيف.'],
        ];

        $insertAd = $pdo->prepare("INSERT INTO ads (userId, title, slug, description, category, city, price, carBrand, carYear, carTransmission, carMileage, images, specifications, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");

        foreach ($sampleAds as $ad) {
            list($uid, $title, $cat, $city, $price, $brand, $year, $trans, $mileage, $desc) = $ad;
            $slug = preg_replace('/[\x{064B}-\x{065F}\x{0670}]/u', '', $title);
            $slug = preg_replace('/\s+/u', '-', $slug);
            $slug = preg_replace('/[^\p{L}\p{N}\-]+/u', '', $slug);
            $slug = mb_substr(trim($slug, '-'), 0, 50);

            $specs = [];
            if ($brand) $specs = ['الماركة'=>$brand, 'السنة'=>$year, 'القير'=>$trans, 'الممشى'=>$mileage.' كم'];

            $insertAd->execute([$uid, $title, $slug, $desc, $cat, $city, $price,
                                $brand, $year, $trans, $mileage,
                                json_encode([], JSON_UNESCAPED_UNICODE),
                                json_encode($specs, JSON_UNESCAPED_UNICODE)]);
        }
        echo "✓ تم إضافة 6 إعلانات تجريبية\n";

        // إشعار ترحيبي
        $welcome = $pdo->prepare("INSERT INTO notifications (userId, title, content, type) VALUES (?, ?, ?, 'system')");
        for ($i = 1; $i <= 5; $i++) {
            $welcome->execute([$i, 'مرحباً بك في حراج اليمن! 🇾🇪', 'نرحب بك في أكبر منصة بيع وشراء في اليمن. ابدأ بإضافة أول إعلان لك الآن.']);
        }
    }

    if (PHP_SAPI !== 'cli') {
        echo "<h2 style='font-family:Cairo,Arial; text-align:center; color:#0D9488;'>✅ تم إعداد قاعدة البيانات بنجاح!</h2>";
        echo "<p style='text-align:center;'><a href='../frontend/index.php'>الذهاب إلى الموقع</a></p>";
    } else {
        echo "\n✅ تم إعداد قاعدة البيانات بنجاح!\n";
    }

} catch (PDOException $e) {
    echo (PHP_SAPI === 'cli' ? "❌ خطأ: " : "<h3 style='color:red'>❌ خطأ: ") . $e->getMessage();
    exit(1);
}
