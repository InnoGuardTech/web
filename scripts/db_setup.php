<?php
/**
 * ============================================================
 * حراج اليمن - إعداد قاعدة البيانات MySQL الكامل v3.5
 * ============================================================
 * ملف متخصص لإعداد MySQL مع دعم كامل لجميع الجداول والعلاقات
 * يتضمن جميع الأعمدة المطلوبة للعمليات المختلفة
 */

// تحديد متغيرات البيئة أو استخدام القيم الافتراضية
$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbPort = getenv('DB_PORT') ?: '3306';
$dbName = getenv('DB_NAME') ?: 'haraj_db';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';
$dbCharset = getenv('DB_CHARSET') ?: 'utf8mb4';

echo "🔧 بدء إعداد قاعدة البيانات MySQL الكامل...\n";
echo "📊 البيانات:\n";
echo "  - الخادم: $dbHost:$dbPort\n";
echo "  - قاعدة البيانات: $dbName\n";
echo "  - المستخدم: $dbUser\n";
echo "  - الترميز: $dbCharset\n\n";

try {
    // الاتصال بـ MySQL بدون قاعدة بيانات محددة
    $pdo = new PDO(
        "mysql:host=$dbHost;port=$dbPort;charset=$dbCharset",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES $dbCharset COLLATE ${dbCharset}_unicode_ci"
        ]
    );

    echo "✅ تم الاتصال بـ MySQL بنجاح\n\n";

    // إنشاء قاعدة البيانات
    echo "📝 إنشاء قاعدة البيانات...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET $dbCharset COLLATE ${dbCharset}_unicode_ci");
    $pdo->exec("USE `$dbName`");
    echo "✅ تم إنشاء قاعدة البيانات\n\n";

    // ============ جدول المستخدمين ============
    echo "📝 إنشاء جدول المستخدمين...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            phone VARCHAR(20) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE,
            password VARCHAR(255) NOT NULL,
            avatar VARCHAR(500),
            bio TEXT,
            role ENUM('user','seller','admin') DEFAULT 'seller',
            rating DECIMAL(3,2) DEFAULT 5.0,
            ratingCount INT DEFAULT 0,
            isPhoneVerified TINYINT DEFAULT 0,
            isBanned TINYINT DEFAULT 0,
            joinedDate VARCHAR(7),
            lastSeenAt TIMESTAMP NULL,
            deletedAt TIMESTAMP NULL,
            createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_phone (phone),
            INDEX idx_email (email),
            INDEX idx_role (role),
            INDEX idx_isBanned (isBanned),
            INDEX idx_deletedAt (deletedAt)
        ) ENGINE=InnoDB DEFAULT CHARSET=$dbCharset COLLATE ${dbCharset}_unicode_ci
    ");
    echo "✅ تم إنشاء جدول المستخدمين\n";

    // ============ جدول الإعلانات ============
    echo "📝 إنشاء جدول الإعلانات...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ads (
            id INT PRIMARY KEY AUTO_INCREMENT,
            userId INT NOT NULL,
            title VARCHAR(200) NOT NULL,
            slug VARCHAR(100),
            description TEXT,
            category VARCHAR(50) NOT NULL,
            city VARCHAR(50) NOT NULL,
            price BIGINT,
            carBrand VARCHAR(50),
            carYear INT,
            carTransmission VARCHAR(30),
            carMileage INT,
            images JSON,
            specifications JSON,
            views INT DEFAULT 0,
            isFeatured TINYINT DEFAULT 0,
            status ENUM('active','inactive','sold','expired') DEFAULT 'active',
            createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_userId (userId),
            INDEX idx_category (category),
            INDEX idx_city (city),
            INDEX idx_status (status),
            INDEX idx_isFeatured (isFeatured),
            FULLTEXT INDEX ft_title_desc (title, description)
        ) ENGINE=InnoDB DEFAULT CHARSET=$dbCharset COLLATE ${dbCharset}_unicode_ci
    ");
    echo "✅ تم إنشاء جدول الإعلانات\n";

    // ============ جدول المفضلة ============
    echo "📝 إنشاء جدول المفضلة...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS favorites (
            id INT PRIMARY KEY AUTO_INCREMENT,
            userId INT NOT NULL,
            adId INT NOT NULL,
            createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_ad (userId, adId),
            FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (adId) REFERENCES ads(id) ON DELETE CASCADE,
            INDEX idx_userId (userId)
        ) ENGINE=InnoDB DEFAULT CHARSET=$dbCharset COLLATE ${dbCharset}_unicode_ci
    ");
    echo "✅ تم إنشاء جدول المفضلة\n";

    // ============ جدول الرسائل ============
    echo "📝 إنشاء جدول الرسائل...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS messages (
            id INT PRIMARY KEY AUTO_INCREMENT,
            senderId INT NOT NULL,
            receiverId INT NOT NULL,
            adId INT,
            content TEXT NOT NULL,
            isRead TINYINT DEFAULT 0,
            createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (senderId) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (receiverId) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (adId) REFERENCES ads(id) ON DELETE SET NULL,
            INDEX idx_senderId (senderId),
            INDEX idx_receiverId (receiverId),
            INDEX idx_isRead (isRead),
            INDEX idx_createdAt (createdAt)
        ) ENGINE=InnoDB DEFAULT CHARSET=$dbCharset COLLATE ${dbCharset}_unicode_ci
    ");
    echo "✅ تم إنشاء جدول الرسائل\n";

    // ============ جدول الإشعارات ============
    echo "📝 إنشاء جدول الإشعارات...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS notifications (
            id INT PRIMARY KEY AUTO_INCREMENT,
            userId INT NOT NULL,
            title VARCHAR(200) NOT NULL,
            content TEXT,
            type VARCHAR(50) DEFAULT 'info',
            link VARCHAR(500),
            isRead TINYINT DEFAULT 0,
            createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_userId (userId),
            INDEX idx_isRead (isRead),
            INDEX idx_createdAt (createdAt)
        ) ENGINE=InnoDB DEFAULT CHARSET=$dbCharset COLLATE ${dbCharset}_unicode_ci
    ");
    echo "✅ تم إنشاء جدول الإشعارات\n";

    // ============ جدول التقييمات (Reviews) ============
    echo "📝 إنشاء جدول التقييمات...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS reviews (
            id INT PRIMARY KEY AUTO_INCREMENT,
            authorUserId INT NOT NULL,
            targetUserId INT NOT NULL,
            adId INT,
            rating DECIMAL(3,2) NOT NULL,
            content TEXT,
            authorName VARCHAR(100),
            createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (authorUserId) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (targetUserId) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (adId) REFERENCES ads(id) ON DELETE SET NULL,
            INDEX idx_targetUserId (targetUserId),
            INDEX idx_authorUserId (authorUserId),
            INDEX idx_adId (adId)
        ) ENGINE=InnoDB DEFAULT CHARSET=$dbCharset COLLATE ${dbCharset}_unicode_ci
    ");
    echo "✅ تم إنشاء جدول التقييمات\n";

    // ============ جدول Ratings (للتوافق) ============
    echo "📝 إنشاء جدول Ratings...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ratings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            fromUserId INT NOT NULL,
            toUserId INT NOT NULL,
            adId INT,
            score DECIMAL(3,2) NOT NULL,
            comment TEXT,
            createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (fromUserId) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (toUserId) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (adId) REFERENCES ads(id) ON DELETE SET NULL,
            INDEX idx_toUserId (toUserId),
            INDEX idx_fromUserId (fromUserId)
        ) ENGINE=InnoDB DEFAULT CHARSET=$dbCharset COLLATE ${dbCharset}_unicode_ci
    ");
    echo "✅ تم إنشاء جدول Ratings\n";

    // ============ جدول التقارير ============
    echo "📝 إنشاء جدول التقارير...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS reports (
            id INT PRIMARY KEY AUTO_INCREMENT,
            userId INT NOT NULL,
            adId INT,
            targetUserId INT,
            reason VARCHAR(100) NOT NULL,
            description TEXT,
            status ENUM('pending','reviewed','resolved','rejected') DEFAULT 'pending',
            createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (adId) REFERENCES ads(id) ON DELETE SET NULL,
            FOREIGN KEY (targetUserId) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_status (status),
            INDEX idx_createdAt (createdAt)
        ) ENGINE=InnoDB DEFAULT CHARSET=$dbCharset COLLATE ${dbCharset}_unicode_ci
    ");
    echo "✅ تم إنشاء جدول التقارير\n";

    // ============ جدول رموز OTP ============
    echo "📝 إنشاء جدول رموز OTP...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS otp_codes (
            id INT PRIMARY KEY AUTO_INCREMENT,
            phone VARCHAR(20) NOT NULL,
            code VARCHAR(10) NOT NULL,
            purpose VARCHAR(50) DEFAULT 'verify_phone',
            attempts INT DEFAULT 0,
            isUsed TINYINT DEFAULT 0,
            expiresAt DATETIME NOT NULL,
            createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_phone (phone),
            INDEX idx_expiresAt (expiresAt)
        ) ENGINE=InnoDB DEFAULT CHARSET=$dbCharset COLLATE ${dbCharset}_unicode_ci
    ");
    echo "✅ تم إنشاء جدول رموز OTP\n";

    // ============ جدول القائمة السوداء ============
    echo "📝 إنشاء جدول القائمة السوداء...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS blacklist (
            id INT PRIMARY KEY AUTO_INCREMENT,
            phone VARCHAR(20) UNIQUE,
            email VARCHAR(100) UNIQUE,
            reason TEXT,
            createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_phone (phone),
            INDEX idx_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=$dbCharset COLLATE ${dbCharset}_unicode_ci
    ");
    echo "✅ تم إنشاء جدول القائمة السوداء\n";

    // ============ جدول حضور المستخدمين ============
    echo "📝 إنشاء جدول حضور المستخدمين...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_presence (
            id INT PRIMARY KEY AUTO_INCREMENT,
            userId INT UNIQUE NOT NULL,
            status ENUM('online','offline','away') DEFAULT 'offline',
            lastSeenAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=$dbCharset COLLATE ${dbCharset}_unicode_ci
    ");
    echo "✅ تم إنشاء جدول حضور المستخدمين\n";

    // ============ جدول العمولات ============
    echo "📝 إنشاء جدول العمولات...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS commissions (
            id INT PRIMARY KEY AUTO_INCREMENT,
            adId INT NOT NULL,
            userId INT NOT NULL,
            amount DECIMAL(15,2) NOT NULL,
            status ENUM('pending','paid','failed') DEFAULT 'pending',
            transactionId VARCHAR(100),
            createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            paidAt TIMESTAMP NULL,
            FOREIGN KEY (adId) REFERENCES ads(id) ON DELETE CASCADE,
            FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_userId (userId),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=$dbCharset COLLATE ${dbCharset}_unicode_ci
    ");
    echo "✅ تم إنشاء جدول العمولات\n";

    // ============ جدول فئات الإعلانات ============
    echo "📝 إنشاء جدول فئات الإعلانات...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS categories (
            id INT PRIMARY KEY AUTO_INCREMENT,
            slug VARCHAR(50) UNIQUE NOT NULL,
            name VARCHAR(100) NOT NULL,
            icon VARCHAR(50),
            description TEXT,
            createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_slug (slug)
        ) ENGINE=InnoDB DEFAULT CHARSET=$dbCharset COLLATE ${dbCharset}_unicode_ci
    ");
    echo "✅ تم إنشاء جدول فئات الإعلانات\n";

    // ============ جدول المدن ============
    echo "📝 إنشاء جدول المدن...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS cities (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(100) UNIQUE NOT NULL,
            region VARCHAR(100),
            createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=$dbCharset COLLATE ${dbCharset}_unicode_ci
    ");
    echo "✅ تم إنشاء جدول المدن\n";

    // ============ جدول خيوط الدردشة ============
    echo "📝 إنشاء جدول خيوط الدردشة...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS chat_threads (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user1Id INT NOT NULL,
            user2Id INT NOT NULL,
            adId INT,
            lastMessage TEXT,
            lastMessageAt TIMESTAMP NULL,
            user1Deleted TINYINT DEFAULT 0,
            user2Deleted TINYINT DEFAULT 0,
            createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user1Id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (user2Id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (adId) REFERENCES ads(id) ON DELETE SET NULL,
            UNIQUE KEY unique_thread (user1Id, user2Id),
            INDEX idx_user1Id (user1Id),
            INDEX idx_user2Id (user2Id),
            INDEX idx_adId (adId)
        ) ENGINE=InnoDB DEFAULT CHARSET=$dbCharset COLLATE ${dbCharset}_unicode_ci
    ");
    echo "✅ تم إنشاء جدول خيوط الدردشة\n";

    // ============ جدول حالة الكتابة ============
    echo "📝 إنشاء جدول حالة الكتابة...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS typing_status (
            id INT PRIMARY KEY AUTO_INCREMENT,
            threadId INT NOT NULL,
            userId INT NOT NULL,
            isTyping TINYINT DEFAULT 0,
            updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (threadId) REFERENCES chat_threads(id) ON DELETE CASCADE,
            FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_typing (threadId, userId)
        ) ENGINE=InnoDB DEFAULT CHARSET=$dbCharset COLLATE ${dbCharset}_unicode_ci
    ");
    echo "✅ تم إنشاء جدول حالة الكتابة\n";

    echo "\n✅ تم إنشاء جميع الجداول بنجاح!\n\n";

    // ============ بذر البيانات الأولية ============
    echo "🌱 بدء بذر البيانات الأولية...\n\n";

    // إضافة الفئات
    echo "📝 إضافة فئات الإعلانات...\n";
    $categories = [
        ['cars', 'سيارات', '🚗'],
        ['realestate', 'عقارات', '🏠'],
        ['electronics', 'إلكترونيات', '📱'],
        ['livestock', 'مواشي وحيوانات', '🐏'],
        ['furniture', 'أثاث ومفروشات', '🪑'],
        ['jobs', 'وظائف', '💼'],
        ['services', 'خدمات', '🔧'],
        ['other', 'أخرى', '📦']
    ];
    $catStmt = $pdo->prepare("INSERT IGNORE INTO categories (slug, name, icon) VALUES (?, ?, ?)");
    foreach ($categories as $cat) {
        $catStmt->execute($cat);
    }
    echo "✅ تم إضافة " . count($categories) . " فئات\n";

    // إضافة المدن اليمنية
    echo "📝 إضافة المدن اليمنية...\n";
    $cities = [
        'صنعاء', 'عدن', 'تعز', 'الحديدة', 'إب', 'ذمار', 'المكلا', 'حضرموت',
        'عمران', 'صعدة', 'مأرب', 'البيضاء', 'لحج', 'أبين', 'شبوة', 'الضالع',
        'حجة', 'المحويت', 'ريمة', 'الجوف', 'سقطرى'
    ];
    $cityStmt = $pdo->prepare("INSERT IGNORE INTO cities (name) VALUES (?)");
    foreach ($cities as $city) {
        $cityStmt->execute([$city]);
    }
    echo "✅ تم إضافة " . count($cities) . " مدينة\n\n";

    // التحقق من وجود مستخدمين
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        echo "📝 إضافة مستخدمين تجريبيين...\n";
        
        // إضافة مستخدمين
        $hashed = password_hash('Admin@123', PASSWORD_DEFAULT);
        $hashedUser = password_hash('User@123', PASSWORD_DEFAULT);
        $joined = date('Y-m');
        
        $insertUser = $pdo->prepare("INSERT INTO users (name, phone, email, password, role, rating, ratingCount, isPhoneVerified, joinedDate)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?)");
        
        $users = [
            ['أحمد الإداري', '777111111', 'admin@haraj.ye', $hashed, 'admin', 5.0, 12],
            ['محمد بائع', '777222222', null, $hashedUser, 'seller', 4.8, 8],
            ['علي البيع والشراء', '777333333', null, $hashedUser, 'seller', 5.0, 5],
            ['سارة المشتري', '777444444', null, $hashedUser, 'user', 4.5, 3],
            ['خالد التاجر', '777555555', null, $hashedUser, 'seller', 4.9, 15]
        ];
        
        foreach ($users as $user) {
            $insertUser->execute(array_merge($user, [$joined]));
        }
        echo "✅ تم إضافة " . count($users) . " مستخدمين تجريبيين\n";

        // إضافة إعلانات تجريبية
        echo "📝 إضافة إعلانات تجريبية...\n";
        $sampleAds = [
            [2, 'تويوتا كامري 2020 فل كامل', 'cars', 'صنعاء', 25000000, 'تويوتا', '2020', 'أوتوماتيك', 45000, 'سيارة بحالة ممتازة، أصلية، صيانة دورية، اللون أبيض، فل كامل بدون حوادث.'],
            [3, 'شقة فخمة للإيجار 4 غرف في حي الصافية', 'realestate', 'صنعاء', 350000, null, null, null, null, 'شقة جديدة 4 غرف وصالة ومطبخ و2 حمام، تشطيب لوكس، إطلالة مميزة، قرب الخدمات.'],
            [2, 'آيفون 14 برو ماكس 256GB', 'electronics', 'عدن', 950000, null, null, null, null, 'بحالة الجديد، مع جميع ملحقاته الأصلية، اللون أزرق، البطارية 100%.'],
            [5, 'هيونداي إلنترا 2018', 'cars', 'تعز', 18500000, 'هيونداي', '2018', 'أوتوماتيك', 80000, 'سيارة عائلية اقتصادية، قير أوتوماتيك، كهرباء كاملة، حالة ممتازة.'],
            [3, 'بقرة حلوب فريزيان عالية الإنتاج', 'livestock', 'الحديدة', 1200000, null, null, null, null, 'بقرة فريزيان أصلية، إنتاجها 25 لتر يومياً، حامل بشهر سابع.'],
            [5, 'لاب توب MacBook Pro M2', 'electronics', 'صنعاء', 1850000, null, null, null, null, 'MacBook Pro M2 شاشة 14 بوصة، 16GB RAM، 512GB SSD، استخدام خفيف.']
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
            if ($brand) {
                $specs = ['الماركة'=>$brand, 'السنة'=>$year, 'القير'=>$trans, 'الممشى'=>$mileage.' كم'];
            }
            
            $insertAd->execute([
                $uid, $title, $slug, $desc, $cat, $city, $price,
                $brand, $year, $trans, $mileage,
                json_encode([], JSON_UNESCAPED_UNICODE),
                json_encode($specs, JSON_UNESCAPED_UNICODE)
            ]);
        }
        echo "✅ تم إضافة " . count($sampleAds) . " إعلانات تجريبية\n";

        // إضافة إشعارات ترحيبية
        echo "📝 إضافة إشعارات ترحيبية...\n";
        $welcome = $pdo->prepare("INSERT INTO notifications (userId, title, content, type) VALUES (?, ?, ?, 'system')");
        for ($i = 1; $i <= 5; $i++) {
            $welcome->execute([$i, 'مرحباً بك في حراج اليمن! 🇾🇪', 'نرحب بك في أكبر منصة بيع وشراء في اليمن. ابدأ بإضافة أول إعلان لك الآن.']);
        }
        echo "✅ تم إضافة إشعارات ترحيبية\n";
    } else {
        echo "ℹ️  قاعدة البيانات تحتوي بالفعل على بيانات\n";
    }

    echo "\n✅ تم إعداد قاعدة البيانات MySQL بنجاح!\n";
    echo "📊 يمكنك الآن استخدام قاعدة البيانات في التطبيق\n";
    echo "\n📌 ملاحظات مهمة:\n";
    echo "  - تم إنشاء جميع الجداول والعلاقات\n";
    echo "  - تم إضافة البيانات التجريبية\n";
    echo "  - يمكنك الآن تسجيل الدخول باستخدام البيانات التجريبية\n";

} catch (PDOException $e) {
    echo "❌ خطأ: " . $e->getMessage() . "\n";
    exit(1);
}
