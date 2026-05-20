<?php
require_once __DIR__ . '/../config.php';

try {
    $pdo = getDBConnection();
    
    $tables = [
        "CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(100) NOT NULL,
            phone VARCHAR(20) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE,
            password VARCHAR(255) NOT NULL,
            avatar VARCHAR(255),
            role VARCHAR(20) DEFAULT 'user',
            rating DECIMAL(3,2) DEFAULT 0,
            ratingCount INT DEFAULT 0,
            isPhoneVerified TINYINT DEFAULT 0,
            isBanned TINYINT DEFAULT 0,
            joinedDate VARCHAR(7),
            createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        "CREATE TABLE IF NOT EXISTS ads (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
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
            images TEXT,
            specifications TEXT,
            status VARCHAR(20) DEFAULT 'pending',
            views INT DEFAULT 0,
            createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE
        )",
        
        "CREATE TABLE IF NOT EXISTS messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            senderId INT NOT NULL,
            receiverId INT NOT NULL,
            adId INT,
            content TEXT NOT NULL,
            isRead TINYINT DEFAULT 0,
            createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (senderId) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (receiverId) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (adId) REFERENCES ads(id) ON DELETE SET NULL
        )",
        
        "CREATE TABLE IF NOT EXISTS favorites (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            userId INT NOT NULL,
            adId INT NOT NULL,
            createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (adId) REFERENCES ads(id) ON DELETE CASCADE,
            UNIQUE(userId, adId)
        )",
        
        "CREATE TABLE IF NOT EXISTS ratings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            fromUserId INT NOT NULL,
            toUserId INT NOT NULL,
            adId INT,
            rating INT,
            comment TEXT,
            createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (fromUserId) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (toUserId) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (adId) REFERENCES ads(id) ON DELETE SET NULL
        )",
        
        "CREATE TABLE IF NOT EXISTS reports (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            adId INT NOT NULL,
            reporterId INT NOT NULL,
            reason VARCHAR(200) NOT NULL,
            content TEXT,
            status VARCHAR(20) DEFAULT 'pending',
            createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            resolvedAt TIMESTAMP NULL,
            FOREIGN KEY (adId) REFERENCES ads(id) ON DELETE CASCADE,
            FOREIGN KEY (reporterId) REFERENCES users(id) ON DELETE CASCADE
        )",
        
        "CREATE TABLE IF NOT EXISTS commissions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            userId INT NOT NULL,
            adId INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            status VARCHAR(20) DEFAULT 'pending',
            createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            paidAt TIMESTAMP NULL,
            FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (adId) REFERENCES ads(id) ON DELETE CASCADE
        )",
        
        "CREATE TABLE IF NOT EXISTS notifications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            userId INT NOT NULL,
            title VARCHAR(200) NOT NULL,
            content TEXT,
            type VARCHAR(50) DEFAULT 'system',
            isRead TINYINT DEFAULT 0,
            createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE
        )"
    ];
    
    foreach ($tables as $sql) {
        $pdo->exec($sql);
    }
    
    echo "✓ تم إنشاء الجداول بنجاح\n";
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users");
    $stmt->execute();
    
    if ($stmt->fetchColumn() == 0) {
        $users = [
            ['أحمد الإداري', '777111111', 'admin@haraj.ye', 'Admin@123', 'admin', 5.0, 25],
            ['محمد البائع المتميز', '777222222', null, 'User@123', 'seller', 4.9, 18],
            ['علي الشراء والبيع', '777333333', null, 'User@123', 'seller', 4.8, 12],
            ['سارة المشتري الموثوقة', '777444444', null, 'User@123', 'user', 4.7, 8],
        ];
        
        $insertUser = $pdo->prepare("INSERT INTO users (name, phone, email, password, role, rating, ratingCount, isPhoneVerified, joinedDate)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?)");
        
        $joined = date('Y-m');
        foreach ($users as [$name, $phone, $email, $pass, $role, $rating, $count]) {
            $insertUser->execute([$name, $phone, $email, password_hash($pass, PASSWORD_DEFAULT), $role, $rating, $count, $joined]);
        }
        
        echo "✓ تم إضافة مستخدمين تجريبيين\n";
        
        $ads = [
            [2, 'تويوتا كامري 2022 فل كامل', 'cars', 'صنعاء', 28000000, 'تويوتا', '2022', 'أوتوماتيك', 15000, 'سيارة بحالة ممتازة'],
            [3, 'شقة فخمة 4 غرف في حي الصافية', 'realestate', 'صنعاء', 350000, null, null, null, null, 'شقة جديدة 4 غرف'],
            [2, 'آيفون 14 برو ماكس 256GB', 'electronics', 'عدن', 950000, null, null, null, null, 'بحالة الجديد'],
        ];
        
        $insertAd = $pdo->prepare("INSERT INTO ads (userId, title, slug, description, category, city, price, carBrand, carYear, carTransmission, carMileage, images, specifications, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
        
        foreach ($ads as [$uid, $title, $cat, $city, $price, $brand, $year, $trans, $mileage, $desc]) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
            $insertAd->execute([$uid, $title, $slug, $desc, $cat, $city, $price, $brand, $year, $trans, $mileage, '[]', '[]']);
        }
        
        echo "✓ تم إضافة إعلانات تجريبية\n";
    }
    
    echo "\n✅ تم إعداد قاعدة البيانات بنجاح!\n";
} catch (Exception $e) {
    die("❌ خطأ: " . $e->getMessage());
}
