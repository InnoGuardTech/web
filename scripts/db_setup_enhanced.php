<?php
/**
 * ============================================================
 * حراج اليمن - إعداد قاعدة البيانات المحسّن v4.1
 * ============================================================
 * إنشاء الجداول وإضافة بيانات تجريبية متقدمة
 */

require_once __DIR__ . '/../config.php';

try {
    $pdo = getDBConnection();
    
    // ===== جداول قاعدة البيانات =====
    $tables = [
        // جدول المستخدمين
        "CREATE TABLE IF NOT EXISTS users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            phone VARCHAR(20) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE,
            password VARCHAR(255) NOT NULL,
            avatar VARCHAR(255),
            role ENUM('user','seller','admin') DEFAULT 'user',
            rating DECIMAL(3,2) DEFAULT 0,
            ratingCount INT DEFAULT 0,
            isPhoneVerified TINYINT DEFAULT 0,
            isBanned TINYINT DEFAULT 0,
            joinedDate VARCHAR(7),
            createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (role, isBanned),
            FULLTEXT INDEX (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        // جدول الإعلانات
        "CREATE TABLE IF NOT EXISTS ads (
            id INT PRIMARY KEY AUTO_INCREMENT,
            userId INT NOT NULL,
            title VARCHAR(200) NOT NULL,
            slug VARCHAR(250),
            description LONGTEXT,
            category VARCHAR(50) NOT NULL,
            city VARCHAR(50) NOT NULL,
            price BIGINT NOT NULL,
            carBrand VARCHAR(100),
            carYear INT,
            carTransmission VARCHAR(50),
            carMileage INT,
            images JSON,
            specifications JSON,
            status ENUM('active','pending','sold','removed') DEFAULT 'pending',
            views INT DEFAULT 0,
            createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
            INDEX (category, city, status),
            INDEX (price),
            INDEX (createdAt),
            FULLTEXT INDEX (title, description)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        // جدول الرسائل
        "CREATE TABLE IF NOT EXISTS messages (
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
            INDEX (receiverId, isRead),
            INDEX (senderId)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        // جدول المفضلة
        "CREATE TABLE IF NOT EXISTS favorites (
            id INT PRIMARY KEY AUTO_INCREMENT,
            userId INT NOT NULL,
            adId INT NOT NULL,
            createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (adId) REFERENCES ads(id) ON DELETE CASCADE,
            UNIQUE KEY (userId, adId)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        // جدول التقييمات
        "CREATE TABLE IF NOT EXISTS ratings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            fromUserId INT NOT NULL,
            toUserId INT NOT NULL,
            adId INT,
            rating INT CHECK (rating >= 1 AND rating <= 5),
            comment TEXT,
            createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (fromUserId) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (toUserId) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (adId) REFERENCES ads(id) ON DELETE SET NULL,
            INDEX (toUserId)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        // جدول البلاغات
        "CREATE TABLE IF NOT EXISTS reports (
            id INT PRIMARY KEY AUTO_INCREMENT,
            adId INT NOT NULL,
            reporterId INT NOT NULL,
            reason VARCHAR(200) NOT NULL,
            content TEXT,
            status ENUM('pending','resolved','dismissed') DEFAULT 'pending',
            createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            resolvedAt TIMESTAMP NULL,
            FOREIGN KEY (adId) REFERENCES ads(id) ON DELETE CASCADE,
            FOREIGN KEY (reporterId) REFERENCES users(id) ON DELETE CASCADE,
            INDEX (status, createdAt)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        // جدول العمولات
        "CREATE TABLE IF NOT EXISTS commissions (
            id INT PRIMARY KEY AUTO_INCREMENT,
            userId INT NOT NULL,
            adId INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            status ENUM('pending','paid','failed') DEFAULT 'pending',
            createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            paidAt TIMESTAMP NULL,
            FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (adId) REFERENCES ads(id) ON DELETE CASCADE,
            INDEX (status, createdAt)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        // جدول الإشعارات
        "CREATE TABLE IF NOT EXISTS notifications (
            id INT PRIMARY KEY AUTO_INCREMENT,
            userId INT NOT NULL,
            title VARCHAR(200) NOT NULL,
            content TEXT,
            type VARCHAR(50) DEFAULT 'system',
            isRead TINYINT DEFAULT 0,
            createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
            INDEX (userId, isRead)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    ];
    
    foreach ($tables as $sql) {
        $pdo->exec($sql);
    }
    
    echo "✓ تم إنشاء الجداول بنجاح\n";
    
    // ===== البيانات التجريبية =====
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users");
    $stmt->execute();
    
    if ($stmt->fetchColumn() == 0) {
        // إضافة مستخدمين تجريبيين
        $users = [
            ['أحمد الإداري', '777111111', 'admin@haraj.ye', 'Admin@123', 'admin', 5.0, 25],
            ['محمد البائع المتميز', '777222222', null, 'User@123', 'seller', 4.9, 18],
            ['علي الشراء والبيع', '777333333', null, 'User@123', 'seller', 4.8, 12],
            ['سارة المشتري الموثوقة', '777444444', null, 'User@123', 'user', 4.7, 8],
            ['خالد التاجر الفاخر', '777555555', null, 'User@123', 'seller', 5.0, 20],
            ['فاطمة المشترية', '777666666', null, 'User@123', 'user', 4.6, 5],
            ['محمود البائع الجديد', '777777777', null, 'User@123', 'seller', 4.5, 3],
            ['نور المستخدمة', '777888888', null, 'User@123', 'user', 4.4, 2],
        ];
        
        $insertUser = $pdo->prepare("INSERT INTO users (name, phone, email, password, role, rating, ratingCount, isPhoneVerified, joinedDate)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?)");
        
        $joined = date('Y-m');
        foreach ($users as [$name, $phone, $email, $pass, $role, $rating, $count]) {
            $insertUser->execute([$name, $phone, $email, password_hash($pass, PASSWORD_DEFAULT), $role, $rating, $count, $joined]);
        }
        
        echo "✓ تم إضافة 8 مستخدمين تجريبيين\n";
        
        // إضافة إعلانات تجريبية متنوعة
        $ads = [
            // سيارات
            [2, 'تويوتا كامري 2022 فل كامل', 'cars', 'صنعاء', 28000000, 'تويوتا', '2022', 'أوتوماتيك', 15000, 'سيارة بحالة ممتازة، أصلية، صيانة دورية، اللون فضي، فل كامل بدون حوادث. توجد الفحوصات الطبية الكاملة.'],
            [5, 'هيونداي إلنترا 2021 اقتصادية', 'cars', 'عدن', 22000000, 'هيونداي', '2021', 'أوتوماتيك', 25000, 'سيارة عائلية اقتصادية، قير أوتوماتيك، كهرباء كاملة، حالة ممتازة، لون أسود لامع.'],
            [2, 'نيسان ألتيما 2020', 'cars', 'تعز', 20000000, 'نيسان', '2020', 'أوتوماتيك', 35000, 'نيسان ألتيما 2020 فل كامل، لون أبيض، حالة الجديد، صيانة دورية منتظمة.'],
            
            // عقارات
            [3, 'شقة فخمة 4 غرف في حي الصافية', 'realestate', 'صنعاء', 350000, null, null, null, null, 'شقة جديدة 4 غرف وصالة ومطبخ و2 حمام، تشطيب لوكس، إطلالة مميزة، قرب الخدمات والمدارس.'],
            [7, 'فيلا حديثة 5 غرف بحي الروضة', 'realestate', 'صنعاء', 750000, null, null, null, null, 'فيلا حديثة 5 غرف + مجلس + مطبخ + 3 حمامات، حديقة خاصة، موقف سيارة، تشطيب فاخر.'],
            [3, 'أرض سكنية 500 متر بحي الشرق', 'realestate', 'عدن', 150000, null, null, null, null, 'أرض سكنية 500 متر مربع، موقع ممتاز قرب الخدمات، سهل التقسيم.'],
            
            // إلكترونيات
            [5, 'آيفون 14 برو ماكس 256GB', 'electronics', 'عدن', 950000, null, null, null, null, 'بحالة الجديد، مع جميع ملحقاته الأصلية، اللون أزرق، البطارية 100%، الضمان ساري.'],
            [2, 'لاب توب MacBook Pro M2', 'electronics', 'صنعاء', 1850000, null, null, null, null, 'MacBook Pro M2 شاشة 14 بوصة، 16GB RAM، 512GB SSD، استخدام خفيف جداً.'],
            [6, 'تلفاز سامسونج 55 بوصة 4K', 'electronics', 'تعز', 450000, null, null, null, null, 'تلفاز سامسونج 55 بوصة 4K، ذكي، بحالة ممتازة، الضمان سارٍ.'],
            
            // أثاث
            [4, 'غرفة نوم فاخرة خشب أرز', 'furniture', 'صنعاء', 180000, null, null, null, null, 'غرفة نوم فاخرة من خشب الأرز، تشطيب عالي الجودة، 6 قطع، حالة ممتازة.'],
            [3, 'مجموعة صالون حديثة', 'furniture', 'عدن', 120000, null, null, null, null, 'مجموعة صالون حديثة، 3 قطع، قماش فاخر، لون بيج، استخدام خفيف.'],
            
            // حيوانات
            [5, 'بقرة حلوب فريزيان عالية الإنتاج', 'livestock', 'الحديدة', 1200000, null, null, null, null, 'بقرة فريزيان أصلية، إنتاجها 25 لتر يومياً، حامل بشهر سابع، صحتها ممتازة.'],
            [7, 'خيل عربي أصيل', 'livestock', 'صنعاء', 2500000, null, null, null, null, 'خيل عربي أصيل، سلالة نقية، عمره 4 سنوات، مدرب وهادئ.'],
            
            // خدمات
            [8, 'خدمات تصميم جرافيك احترافية', 'services', 'صنعاء', 50000, null, null, null, null, 'خدمات تصميم جرافيك احترافية: شعارات، بروشورات، تصاميم ويب، تصاميم طباعية.'],
            [6, 'خدمات تنظيف المنازل المتخصصة', 'services', 'عدن', 30000, null, null, null, null, 'خدمات تنظيف المنازل والمكاتب بأعلى معايير الجودة، فريق محترف وموثوق.'],
        ];
        
        $insertAd = $pdo->prepare("INSERT INTO ads (userId, title, slug, description, category, city, price, carBrand, carYear, carTransmission, carMileage, images, specifications, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
        
        foreach ($ads as [$uid, $title, $cat, $city, $price, $brand, $year, $trans, $mileage, $desc]) {
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
        
        echo "✓ تم إضافة 16 إعلان تجريبي متنوع\n";
        
        // إضافة تقييمات
        $ratings = [
            [2, 4, 1, 5, 'بائع موثوق وسريع التسليم'],
            [3, 5, 2, 5, 'تعامل احترافي وممتاز'],
            [4, 2, 3, 4, 'السيارة بحالة جيدة جداً'],
            [5, 3, 4, 5, 'خدمة عقارية متميزة'],
        ];
        
        $insertRating = $pdo->prepare("INSERT INTO ratings (fromUserId, toUserId, adId, rating, comment) VALUES (?, ?, ?, ?, ?)");
        foreach ($ratings as [$from, $to, $ad, $rating, $comment]) {
            $insertRating->execute([$from, $to, $ad, $rating, $comment]);
        }
        
        echo "✓ تم إضافة 4 تقييمات\n";
        
        // إضافة إشعارات ترحيبية
        $welcome = $pdo->prepare("INSERT INTO notifications (userId, title, content, type) VALUES (?, ?, ?, 'system')");
        for ($i = 1; $i <= 8; $i++) {
            $welcome->execute([$i, 'مرحباً بك في حراج اليمن! 🇾🇪', 'نرحب بك في أكبر منصة بيع وشراء في اليمن. ابدأ بإضافة أول إعلان لك الآن أو تصفح الإعلانات المتاحة.']);
        }
        
        echo "✓ تم إضافة إشعارات ترحيبية\n";
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
