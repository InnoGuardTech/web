<?php
// db_setup.php - سكربت التركيب التلقائي لقاعدة بيانات MySQL والبيانات التجريبية للمنصة
require_once '../config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تثبيت قاعدة البيانات - حراج الفاخر</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Cairo', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex items-center justify-center p-6">
    <div class="max-w-2xl w-full bg-white border border-slate-200/80 rounded-3xl p-8 shadow-xl space-y-6 text-right">
        <div class="text-center space-y-2">
            <div class="w-16 h-16 bg-emerald-50 text-emerald-600 rounded-3xl flex items-center justify-center mx-auto shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0v3.75" />
                </svg>
            </div>
            <h1 class="text-2xl font-black text-slate-800 mt-4">معالج تهيئة قاعدة بيانات حراج الفاخر</h1>
            <p class="text-xs text-slate-400 font-bold">سيقوم هذا المعالج بإنشاء قاعدة البيانات `haraj_db` وجميع الجداول والبيانات التجريبية تلقائياً على خادم WampServer.</p>
        </div>

        <div class="border-t border-slate-100 pt-6 space-y-4">
            <h3 class="font-extrabold text-sm text-slate-700">سجل عمليات التثبيت:</h3>
            <div class="bg-slate-900 text-slate-200 p-5 rounded-2xl text-xs font-mono space-y-2 overflow-y-auto max-h-[300px] text-left" dir="ltr">
<?php
try {
    // 1. الاتصال بـ MySQL
    $pdo = getDBConnection();
    echo "<div class='text-emerald-400'>✓ [MySQL Server] Connected successfully to host: " . DB_HOST . "</div>";

    // 2. إنشاء قاعدة البيانات
    $pdo->exec("DROP DATABASE IF EXISTS " . DB_NAME);
    $pdo->exec("CREATE DATABASE " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<div class='text-emerald-400'>✓ [Database] Database `" . DB_NAME . "` dropped and recreated successfully.</div>";

    // إعادة الاتصال وتحديد قاعدة البيانات الجديدة
    $pdo->exec("USE " . DB_NAME);

    // 3. إنشاء الجداول
    
    // جدول المستخدمين
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        phone VARCHAR(255) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        rating DECIMAL(3,2) DEFAULT 5.0,
        joinedDate VARCHAR(255) NOT NULL,
        role VARCHAR(255) DEFAULT 'seller',
        isBanned TINYINT(1) DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "<div class='text-slate-300'>✓ Created table `users`</div>";

    // جدول الإعلانات
    $pdo->exec("CREATE TABLE IF NOT EXISTS ads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        category VARCHAR(255) NOT NULL,
        city VARCHAR(255) NOT NULL,
        price DECIMAL(15,2) NULL,
        images TEXT NOT NULL,
        specifications TEXT NOT NULL,
        userId INT NOT NULL,
        carBrand VARCHAR(255) NULL,
        carYear VARCHAR(255) NULL,
        carTransmission VARCHAR(255) NULL,
        carMileage INT NULL,
        propertyType VARCHAR(255) NULL,
        propertyRooms VARCHAR(255) NULL,
        propertyContract VARCHAR(255) NULL,
        views INT DEFAULT 0,
        isPinned TINYINT DEFAULT 0,
        createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "<div class='text-slate-300'>✓ Created table `ads`</div>";

    // جدول التعليقات
    $pdo->exec("CREATE TABLE IF NOT EXISTS comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        adId INT NOT NULL,
        createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (adId) REFERENCES ads(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "<div class='text-slate-300'>✓ Created table `comments`</div>";

    // جدول غرف المحادثة
    $pdo->exec("CREATE TABLE IF NOT EXISTS chat_threads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        adId INT NOT NULL,
        buyerId INT NOT NULL,
        unreadFor VARCHAR(255) DEFAULT '[]',
        createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (adId) REFERENCES ads(id) ON DELETE CASCADE,
        FOREIGN KEY (buyerId) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "<div class='text-slate-300'>✓ Created table `chat_threads`</div>";

    // جدول الرسائل المتبادلة
    $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        threadId INT NOT NULL,
        senderId INT NOT NULL,
        text TEXT NOT NULL,
        createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (threadId) REFERENCES chat_threads(id) ON DELETE CASCADE,
        FOREIGN KEY (senderId) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "<div class='text-slate-300'>✓ Created table `messages`</div>";

    // جدول الإشعارات
    $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        userId INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        isRead TINYINT(1) DEFAULT 0,
        createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "<div class='text-slate-300'>✓ Created table `notifications`</div>";

    // جدول البلاغات
    $pdo->exec("CREATE TABLE IF NOT EXISTS ad_reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        adId INT NOT NULL,
        adTitle VARCHAR(255) NOT NULL,
        reason VARCHAR(255) NOT NULL,
        reporterName VARCHAR(255) NOT NULL,
        createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (adId) REFERENCES ads(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "<div class='text-slate-300'>✓ Created table `ad_reports`</div>";

    // جدول البلاغات الرسمية (للإدارة)
    $pdo->exec("CREATE TABLE IF NOT EXISTS reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        adId INT NOT NULL,
        reporterId INT NOT NULL,
        reason TEXT NOT NULL,
        status ENUM('pending', 'resolved', 'dismissed') DEFAULT 'pending',
        createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (adId) REFERENCES ads(id) ON DELETE CASCADE,
        FOREIGN KEY (reporterId) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "<div class='text-slate-300'>✓ Created table `reports`</div>";

    // جدول تقييمات المستخدمين
    $pdo->exec("CREATE TABLE IF NOT EXISTS reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        targetUserId INT NOT NULL,
        authorName VARCHAR(255) NOT NULL,
        rating INT NOT NULL,
        content TEXT NOT NULL,
        createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (targetUserId) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "<div class='text-slate-300'>✓ Created table `reviews`</div>";

    // جدول تحويل العمولات
    $pdo->exec("CREATE TABLE IF NOT EXISTS commission_transfers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(255) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        bankName VARCHAR(255) NOT NULL,
        transferDate VARCHAR(255) NOT NULL,
        adNumber VARCHAR(255) NOT NULL,
        status VARCHAR(255) DEFAULT 'pending',
        createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "<div class='text-slate-300'>✓ Created table `commission_transfers`</div>";

    // جدول القائمة السوداء
    $pdo->exec("CREATE TABLE IF NOT EXISTS blacklist (
        id INT AUTO_INCREMENT PRIMARY KEY,
        phone VARCHAR(255) UNIQUE NOT NULL,
        reason TEXT NOT NULL,
        createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "<div class='text-slate-300'>✓ Created table `blacklist`</div>";

    // جدول المفضلة
    $pdo->exec("CREATE TABLE IF NOT EXISTS favorites (
        id INT AUTO_INCREMENT PRIMARY KEY,
        userId INT NOT NULL,
        adId INT NOT NULL,
        createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY user_ad (userId, adId),
        FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (adId) REFERENCES ads(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "<div class='text-slate-300'>✓ Created table `favorites`</div>";

    // 4. زراعة البيانات التجريبية (Seeding)
    
    // التحقق من خلو جدول المستخدمين وزراعته
    $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($userCount == 0) {
        $hashedPassword = password_hash('123456', PASSWORD_DEFAULT);
        
        $users = [
            ['أبو حمزة (الإدارة)', '770000000', $hashedPassword, 5.0, '2023-01', 'admin', 0],
            ['أحمد اليماني', '771234567', $hashedPassword, 4.8, '2024-02', 'seller', 0],
            ['محمد الصنعاني', '732345678', $hashedPassword, 4.9, '2024-03', 'seller', 0],
            ['عمار التعزي', '713456789', $hashedPassword, 4.7, '2024-04', 'seller', 0],
            ['نصاب محترف', '779999999', $hashedPassword, 1.0, '2025-01', 'seller', 1]
        ];

        $stmt = $pdo->prepare("INSERT INTO users (name, phone, password, rating, joinedDate, role, isBanned) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($users as $u) {
            $stmt->execute($u);
        }
        echo "<div class='text-emerald-400'>✓ Successfully seeded users table (including Admin and Banned account)</div>";
    }

    // زراعة الإعلانات
    $adCount = $pdo->query("SELECT COUNT(*) FROM ads")->fetchColumn();
    if ($adCount == 0) {
        $sellerId1 = $pdo->query("SELECT id FROM users WHERE phone='771234567'")->fetchColumn();
        $sellerId2 = $pdo->query("SELECT id FROM users WHERE phone='732345678'")->fetchColumn();
        $sellerId3 = $pdo->query("SELECT id FROM users WHERE phone='713456789'")->fetchColumn();

        $ads = [
            [
                'تويوتا كامري 2024 فل كامل قراندي - أصفار',
                'للبيع تويوتا كامري موديل 2024 فئة قراندي V6، الممشى أصفار (جديد)، اللون أبيض لؤلؤي، ناقل حركة أوتوماتيكي، فتحة سقف بانوراما، مراتب جلد شاشة وكاميرا خلفية، مجمرك وجاهز.',
                'cars',
                'صنعاء',
                18500000.00,
                json_encode(['https://images.unsplash.com/photo-1621007947382-bb3c3994e3fb?w=600&q=80', 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?w=600&q=80']),
                json_encode(['ناقل الحركة' => 'أوتوماتيك', 'الممشى' => '0 كم', 'سنة الصنع' => '2024']),
                $sellerId1, 'تويوتا', '2024', 'auto', 0, null, null, null
            ],
            [
                'مرسيدس S-Class 2023 لارج ممشى قليل',
                'للبيع مرسيدس S500 موديل 2023 فئة لارج، اللون أسود ملكي ومن الداخل بيج ديزاينو، الممشى 12,000 كم فقط. شاشات خلفية، تسخين وتبريد لجميع المراتب، حالة السيارة ممتازة وخالية من الذحل والحوادث تماماً.',
                'cars',
                'عدن',
                65000000.00,
                json_encode(['https://images.unsplash.com/photo-1618843479313-40f8afb4b4d8?w=600&q=80', 'https://images.unsplash.com/photo-1617531653332-bd46c24f2068?w=600&q=80']),
                json_encode(['ناقل الحركة' => 'أوتوماتيك', 'الممشى' => '12,000 كم', 'سنة الصنع' => '2023']),
                $sellerId2, 'مرسيدس', '2023', 'auto', 12000, null, null, null
            ],
            [
                'فيلا فاخرة للبيع في حي حدة بتصميم مودرن راقي',
                'فيلا مودرن للبيع في أرقى أحياء صنعاء (حي حدة)، مساحة 400 متر مربع، تصميم عصري فخم مع حوش كبير وبئر ماء خاص وطاقة شمسية ومطبخ راكب بالكامل. تتكون من دور أرضي استقبال بالكامل، و5 غرف نوم بالدور الأول.',
                'realestate',
                'صنعاء',
                250000000.00,
                json_encode(['https://images.unsplash.com/photo-1613977257363-707ba9348227?w=600&q=80', 'https://images.unsplash.com/photo-1613490493576-7fde63acd811?w=600&q=80']),
                json_encode(['نوع العقار' => 'فيلا', 'عدد الغرف' => '5 غرف فأكثر', 'نوع العقد' => 'بيع']),
                $sellerId3, null, null, null, null, 'villa', '5+', 'sell'
            ],
            [
                'آيفون 15 برو ماكس تيتانيوم طبيعي 256 جيجا جديد',
                'للبيع آيفون 15 برو ماكس السعة 256 جيجابايت، اللون التيتانيوم الطبيعي الجذاب، معلب وجديد لم يفتح (بختم المصنع). ضمان أبل الدولي.',
                'electronics',
                'تعز',
                680000.00,
                json_encode(['https://images.unsplash.com/photo-1510557880182-3d4d3cba35a5?w=600&q=80']),
                json_encode(['الحالة' => 'جديد غير مستخدم', 'اللون' => 'تيتانيوم طبيعي', 'الضمان' => 'سنة']),
                $sellerId1, null, null, null, null, null, null, null
            ],
            [
                'أغنام بلدي للبيع - صحة ممتازة',
                'للبيع أغنام بلدي طيبة ومنقية، صحتها ممتازة وخالية من جميع العيوب والأمراض، تربية بلدي وعلف طيب. البيع لأعلى سوم والتوصيل متوفر بحدود المدينة.',
                'livestock',
                'إب',
                null,
                json_encode(['https://images.unsplash.com/photo-1516467508483-a7212febe31a?w=600&q=80']),
                json_encode(['الحالة' => 'سليم تماماً', 'النوع' => 'أغنام بلدي']),
                $sellerId2, null, null, null, null, null, null, null
            ]
        ];

        $stmt = $pdo->prepare("INSERT INTO ads (title, description, category, city, price, images, specifications, userId, carBrand, carYear, carTransmission, carMileage, propertyType, propertyRooms, propertyContract) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($ads as $a) {
            $stmt->execute($a);
        }
        echo "<div class='text-emerald-400'>✓ Successfully seeded ads table with premium products</div>";
    }

    // زراعة التعليقات
    $commentCount = $pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn();
    if ($commentCount == 0) {
        $adId1 = $pdo->query("SELECT id FROM ads WHERE title LIKE '%كامري%'")->fetchColumn();
        $adId2 = $pdo->query("SELECT id FROM ads WHERE title LIKE '%مرسيدس%'")->fetchColumn();
        
        $comments = [
            ['ياسر اليمني', 'ما شاء الله تبارك الله، الله يرزقك البيعة الطيبة. هل تقبل البدل بسيارة جيب؟', $adId1],
            ['أحمد اليماني (صاحب السلعة)', 'الله يجزاك خير يا غالي، لا للأسف البيع كاش فقط ولا أقبل البدل.', $adId1],
            ['أبو علي', 'السيارة ما شاء الله فخمة جداً، كم حدك فيها من النهاية للصامل؟', $adId2]
        ];

        $stmt = $pdo->prepare("INSERT INTO comments (username, content, adId) VALUES (?, ?, ?)");
        foreach ($comments as $c) {
            $stmt->execute($c);
        }
        echo "<div class='text-emerald-400'>✓ Seeded comments feed with dynamic conversations</div>";
    }

    // زراعة القائمة السوداء
    $blacklistCount = $pdo->query("SELECT COUNT(*) FROM blacklist")->fetchColumn();
    if ($blacklistCount == 0) {
        $blacklist = [
            ['779999999', 'محتال يقوم بطلب تحويل عربون لسيارات وهمية غير متواجدة على أرض الواقع.'],
            ['738888888', 'مستشار وهمي يطلب أرقام الحسابات البنكية بدعوى توثيق المتاجر.']
        ];
        $stmt = $pdo->prepare("INSERT INTO blacklist (phone, reason) VALUES (?, ?)");
        foreach ($blacklist as $b) {
            $stmt->execute($b);
        }
        echo "<div class='text-emerald-400'>✓ Seeded blacklist with mock banned phones</div>";
    }

    // زراعة تقييمات المستخدمين
    $reviewCount = $pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn();
    if ($reviewCount == 0) {
        $sellerId1 = $pdo->query("SELECT id FROM users WHERE phone='771234567'")->fetchColumn();
        
        $reviews = [
            [$sellerId1, 'بسام العدني', 5, 'أنصح بالتعامل معه، رجل صادق وأمين وسريع في نقل ملكية السيارة ولطيف بالتعامل.'],
            [$sellerId1, 'محمد الصنعاني', 4, 'تعامل راقي جداً وسلعة مطابقة للوصف تماماً.']
        ];
        $stmt = $pdo->prepare("INSERT INTO reviews (targetUserId, authorName, rating, content) VALUES (?, ?, ?, ?)");
        foreach ($reviews as $r) {
            $stmt->execute($r);
        }
        echo "<div class='text-emerald-400'>✓ Seeded user reviews and trust ratings</div>";
    }

    echo "<div class='text-yellow-400 font-bold mt-4'>★ ALL DATABASE SETUPS AND SEEDINGS COMPLETED SUCCESSFULLY! ★</div>";

} catch (Exception $e) {
    echo "<div class='text-rose-500 font-bold'>❌ Error: " . $e->getMessage() . "</div>";
    echo "<div class='text-rose-400 font-bold'>Please ensure WampServer's MySQL is running and login credentials match config.php.</div>";
}
?>
            </div>
        </div>

        <div class="bg-emerald-50 border border-emerald-100 rounded-2xl p-4 text-emerald-800 text-xs font-bold text-center space-y-2">
            <div>🎉 مبروك! تم تثبيت وإعداد المنصة للعمل على MySQL وسيرفر Wamp بنجاح.</div>
            <a href="index.php" class="inline-block bg-emerald-600 hover:bg-emerald-700 text-white font-black px-6 py-2.5 rounded-xl transition-all duration-300 shadow-md">
                الانتقال للصفحة الرئيسية للمنصة 🚀
            </a>
        </div>
    </div>
</body>
</html>
