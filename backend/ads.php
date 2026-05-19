<?php
/**
 * backend/ads.php - APIs الإعلانات v2.0
 * ✅ Pagination
 * ✅ Sorting (newest/oldest/cheapest/expensive/popular)
 * ✅ Price range filter
 * ✅ Year filter for cars
 * ✅ Advanced search (FULLTEXT)
 * ✅ Edit/Archive/Bump/Mark as Sold
 * ✅ رفع ملفات حقيقي بدلاً من Base64 (مع توافق خلفي)
 */
require_once __DIR__ . '/config.php';
apiHeaders();

$db = getDBConnection();
$method = $_SERVER['REQUEST_METHOD'];
$input = getInput();
$action = $_GET['action'] ?? $input['action'] ?? '';

// ============================================================
// GET: عرض الإعلانات
// ============================================================
if ($method === 'GET') {

    // ---- جلب إعلان واحد ----
    if (!empty($_GET['id'])) {
        $adId = (int)$_GET['id'];

        $stmt = $db->prepare("
            SELECT a.*, u.name AS userName, u.rating AS userRating, u.phone AS userPhone,
                   u.avatar AS userAvatar, u.joinedDate AS userJoined, u.isPhoneVerified
            FROM ads a JOIN users u ON a.userId = u.id
            WHERE a.id = ? AND a.status != 'deleted'
        ");
        $stmt->execute([$adId]);
        $ad = $stmt->fetch();

        if (!$ad) jsonError('الإعلان غير موجود', 404);

        // زيادة المشاهدات (مرة لكل IP في اليوم)
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $viewerId = $_SESSION['user_id'] ?? null;
        $checkView = $db->prepare("SELECT COUNT(*) FROM ad_view_stats WHERE adId = ? AND (ip = ? OR viewerId = ?) AND viewedAt > DATE_SUB(NOW(), INTERVAL 1 DAY)");
        $checkView->execute([$adId, $ip, $viewerId]);
        if ($checkView->fetchColumn() == 0) {
            $db->prepare("UPDATE ads SET views = views + 1 WHERE id = ?")->execute([$adId]);
            $db->prepare("INSERT INTO ad_view_stats (adId, viewerId, ip) VALUES (?, ?, ?)")
               ->execute([$adId, $viewerId, $ip]);
            $ad['views']++;
        }

        // التعليقات
        $cStmt = $db->prepare("SELECT * FROM comments WHERE adId = ? ORDER BY createdAt DESC LIMIT 50");
        $cStmt->execute([$adId]);
        $comments = array_map(function($c){
            return [
                'id' => $c['id'],
                'username' => $c['username'],
                'content' => $c['content'],
                'type' => $c['type'],
                'offerAmount' => $c['offerAmount'] ? formatPrice($c['offerAmount']) : null,
                'date' => formatArabicDate($c['createdAt'])
            ];
        }, $cStmt->fetchAll());

        // المفضلة (هل أضافه المستخدم الحالي؟)
        $isFav = false;
        if (isset($_SESSION['user_id'])) {
            $favStmt = $db->prepare("SELECT 1 FROM favorites WHERE userId = ? AND adId = ?");
            $favStmt->execute([$_SESSION['user_id'], $adId]);
            $isFav = (bool)$favStmt->fetchColumn();
        }

        // عدد المفضّلين
        $favCountStmt = $db->prepare("SELECT COUNT(*) FROM favorites WHERE adId = ?");
        $favCountStmt->execute([$adId]);
        $favCount = $favCountStmt->fetchColumn();

        // إعلانات مشابهة
        $relStmt = $db->prepare("SELECT id, title, slug, price, city, images, category, createdAt
                                 FROM ads
                                 WHERE category = ? AND id != ? AND status = 'active'
                                 ORDER BY createdAt DESC LIMIT 6");
        $relStmt->execute([$ad['category'], $adId]);
        $related = array_map(function($r){
            return [
                'id' => $r['id'],
                'title' => $r['title'],
                'slug' => $r['slug'],
                'price' => formatPrice($r['price']),
                'city' => $r['city'],
                'image' => firstImage($r['images'], $r['category']),
                'date' => formatArabicDate($r['createdAt'])
            ];
        }, $relStmt->fetchAll());

        // معالجة الصور
        $images = json_decode($ad['images'] ?? '[]', true) ?: [];
        $imagesUrls = array_map('imageUrl', $images);
        if (empty($imagesUrls)) $imagesUrls = [defaultAdImage($ad['category'])];

        $specs = json_decode($ad['specifications'] ?? '{}', true) ?: [];

        jsonSuccess([
            'id'             => (int)$ad['id'],
            'userId'         => (int)$ad['userId'],
            'userName'       => $ad['userName'],
            'userPhone'      => $ad['userPhone'],
            'userRating'     => (float)$ad['userRating'],
            'userJoined'     => $ad['userJoined'],
            'userAvatar'     => avatarUrl(['name'=>$ad['userName'],'avatar'=>$ad['userAvatar']]),
            'userVerified'   => (bool)$ad['isPhoneVerified'],
            'title'          => $ad['title'],
            'slug'           => $ad['slug'],
            'description'    => $ad['description'],
            'category'       => $ad['category'],
            'categoryName'   => getCategoryName($ad['category']),
            'categoryIcon'   => getCategoryIcon($ad['category']),
            'city'           => $ad['city'],
            'price'          => formatPrice($ad['price']),
            'priceRaw'       => (float)$ad['price'],
            'images'         => $imagesUrls,
            'specifications' => $specs,
            'carBrand'       => $ad['carBrand'],
            'carYear'        => $ad['carYear'],
            'latitude'       => $ad['latitude'],
            'longitude'      => $ad['longitude'],
            'locationName'   => $ad['locationName'],
            'views'          => (int)$ad['views'],
            'isPinned'       => (bool)$ad['isPinned'],
            'status'         => $ad['status'],
            'isFavorited'    => $isFav,
            'favCount'       => (int)$favCount,
            'comments'       => $comments,
            'related'        => $related,
            'formattedDate'  => formatArabicDate($ad['createdAt']),
            'createdAt'      => $ad['createdAt'],
            'isOwner'        => (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $ad['userId'])
        ]);
    }

    // ---- المفضلة ----
    if ($action === 'favorites') {
        requireAuth();
        $stmt = $db->prepare("
            SELECT a.id, a.title, a.slug, a.category, a.city, a.price, a.images, a.createdAt
            FROM favorites f JOIN ads a ON f.adId = a.id
            WHERE f.userId = ? AND a.status = 'active'
            ORDER BY f.createdAt DESC
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $favs = array_map(function($a){
            return [
                'id' => (int)$a['id'],
                'title' => $a['title'],
                'slug' => $a['slug'],
                'category' => getCategoryName($a['category']),
                'icon' => getCategoryIcon($a['category']),
                'city' => $a['city'],
                'price' => formatPrice($a['price']),
                'image' => firstImage($a['images'], $a['category']),
                'date' => formatArabicDate($a['createdAt'])
            ];
        }, $stmt->fetchAll());
        jsonSuccess($favs);
    }

    // ---- إعلاناتي ----
    if ($action === 'my_ads') {
        requireAuth();
        $statusFilter = $_GET['status'] ?? 'all'; // all, active, sold, archived
        $where = "userId = ? AND status != 'deleted'";
        $params = [$_SESSION['user_id']];
        if ($statusFilter !== 'all') {
            $where .= " AND status = ?";
            $params[] = $statusFilter;
        }
        $stmt = $db->prepare("SELECT id, title, slug, price, city, images, status, views, category, createdAt
                              FROM ads WHERE $where ORDER BY createdAt DESC");
        $stmt->execute($params);

        $statusLabels = ['active'=>'نشط','sold'=>'تم البيع','archived'=>'مؤرشف','pending'=>'قيد المراجعة','rejected'=>'مرفوض'];
        $ads = array_map(function($a) use ($statusLabels){
            return [
                'id' => (int)$a['id'],
                'title' => $a['title'],
                'slug' => $a['slug'],
                'price' => formatPrice($a['price']),
                'city' => $a['city'],
                'image' => firstImage($a['images'], $a['category']),
                'views' => (int)$a['views'],
                'status' => $a['status'],
                'statusLabel' => $statusLabels[$a['status']] ?? $a['status'],
                'date' => formatArabicDate($a['createdAt'])
            ];
        }, $stmt->fetchAll());
        jsonSuccess($ads);
    }

    // ---- جلب إعلان للتعديل (لمالكه فقط) ----
    if ($action === 'edit_data') {
        requireAuth();
        $adId = (int)($_GET['id'] ?? 0);
        $stmt = $db->prepare("SELECT * FROM ads WHERE id = ? AND userId = ?");
        $stmt->execute([$adId, $_SESSION['user_id']]);
        $ad = $stmt->fetch();
        if (!$ad) jsonError('الإعلان غير موجود أو ليس ملكك', 404);

        $ad['images'] = json_decode($ad['images'] ?? '[]', true) ?: [];
        $ad['specifications'] = json_decode($ad['specifications'] ?? '{}', true) ?: [];
        $ad['imagesUrls'] = array_map('imageUrl', $ad['images']);
        jsonSuccess($ad);
    }

    // ---- القائمة الرئيسية مع كل الفلاتر ----
    if ($action === '' || $action === 'list' || empty($action)) {
        $cat       = $_GET['category'] ?? $_GET['cat'] ?? 'all';
        $city      = $_GET['city'] ?? 'الكل';
        $brand     = $_GET['brand'] ?? '';
        $q         = trim($_GET['q'] ?? '');
        $minPrice  = (float)($_GET['min_price'] ?? 0);
        $maxPrice  = (float)($_GET['max_price'] ?? 0);
        $minYear   = (int)($_GET['min_year'] ?? 0);
        $maxYear   = (int)($_GET['max_year'] ?? 0);
        $sort      = $_GET['sort'] ?? 'newest';
        // Sort aliasing: latest=newest, expensive=price_desc, cheapest=price_asc, popular=views
        $sortMap = ['latest'=>'newest','newest'=>'newest','oldest'=>'oldest','cheapest'=>'price_asc','expensive'=>'price_desc','popular'=>'views'];
        $sort = $sortMap[$sort] ?? $sort;
        $page      = max(1, (int)($_GET['page'] ?? 1));
        $perPage   = min(50, max(5, (int)($_GET['per_page'] ?? 20)));
        $offset    = ($page - 1) * $perPage;

        $where  = ["a.status = 'active'"];
        $params = [];

        if ($cat !== 'all' && $cat !== '') {
            $where[] = "a.category = ?";
            $params[] = $cat;
        }
        if ($city !== 'الكل' && $city !== '') {
            $where[] = "a.city = ?";
            $params[] = $city;
        }
        if (!empty($brand)) {
            $where[] = "a.carBrand = ?";
            $params[] = $brand;
        }
        if ($minPrice > 0) {
            $where[] = "a.price >= ?";
            $params[] = $minPrice;
        }
        if ($maxPrice > 0) {
            $where[] = "a.price <= ?";
            $params[] = $maxPrice;
        }
        if ($minYear > 0) {
            $where[] = "CAST(a.carYear AS UNSIGNED) >= ?";
            $params[] = $minYear;
        }
        if ($maxYear > 0) {
            $where[] = "CAST(a.carYear AS UNSIGNED) <= ?";
            $params[] = $maxYear;
        }
        if (!empty($q)) {
            // بحث FULLTEXT + LIKE احتياطي
            $where[] = "(MATCH(a.title, a.description) AGAINST (? IN NATURAL LANGUAGE MODE) OR a.title LIKE ? OR a.description LIKE ?)";
            $params[] = $q;
            $params[] = "%$q%";
            $params[] = "%$q%";
        }

        $whereSQL = implode(' AND ', $where);

        // ترتيب
        $orderMap = [
            'newest'    => 'a.isPinned DESC, COALESCE(a.bumpedAt, a.createdAt) DESC',
            'oldest'    => 'a.createdAt ASC',
            'cheapest'  => 'a.price ASC, a.createdAt DESC',
            'expensive' => 'a.price DESC, a.createdAt DESC',
            'popular'   => 'a.views DESC, a.createdAt DESC'
        ];
        $orderBy = $orderMap[$sort] ?? $orderMap['newest'];

        // العدد الكلي
        $countStmt = $db->prepare("SELECT COUNT(*) FROM ads a WHERE $whereSQL");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        // الصفوف
        $sql = "SELECT a.id, a.title, a.slug, a.category, a.city, a.price, a.images,
                       a.views, a.isPinned, a.createdAt, a.bumpedAt,
                       u.name AS userName, u.id AS userId, u.isPhoneVerified
                FROM ads a JOIN users u ON a.userId = u.id
                WHERE $whereSQL
                ORDER BY $orderBy
                LIMIT $perPage OFFSET $offset";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $ads = array_map(function($a){
            return [
                'id'           => (int)$a['id'],
                'title'        => $a['title'],
                'slug'         => $a['slug'],
                'category'     => $a['category'],
                'categoryName' => getCategoryName($a['category']),
                'icon'         => getCategoryIcon($a['category']),
                'city'         => $a['city'],
                'price'        => formatPrice($a['price']),
                'priceRaw'     => (float)$a['price'],
                'image'        => firstImage($a['images'], $a['category']),
                'userName'     => $a['userName'],
                'userId'       => (int)$a['userId'],
                'verified'     => (bool)$a['isPhoneVerified'],
                'views'        => (int)$a['views'],
                'isPinned'     => (bool)$a['isPinned'],
                'date'         => formatArabicDate($a['bumpedAt'] ?: $a['createdAt'])
            ];
        }, $rows);

        jsonSuccess([
            'ads'        => $ads,
            'total'      => $total,
            'page'       => $page,
            'per_page'   => $perPage,
            'total_pages'=> (int)ceil($total / $perPage),
            'has_more'   => ($page * $perPage) < $total
        ]);
    }
}

// ============================================================
// POST: إضافة/تعديل/تفاعل
// ============================================================
if ($method === 'POST') {

    // ---- إضافة تعليق ----
    if ($action === 'add_comment') {
        requireAuth();
        requireCsrf();
        $adId = (int)($input['id'] ?? $input['ad_id'] ?? $input['adId'] ?? 0);
        $content = sanitize($input['content'] ?? '');
        $type = $input['type'] ?? 'comment';
        $offerAmount = isset($input['offer_amount']) ? (float)$input['offer_amount'] : null;

        if ($adId <= 0 || empty($content)) jsonError('بيانات ناقصة');
        if (mb_strlen($content) > 1000) jsonError('التعليق طويل جداً');

        $check = $db->prepare("SELECT userId, title FROM ads WHERE id = ? AND status = 'active'");
        $check->execute([$adId]);
        $ad = $check->fetch();
        if (!$ad) jsonError('الإعلان غير موجود', 404);

        $userName = $_SESSION['user_name'] ?? 'مستخدم';
        $stmt = $db->prepare("INSERT INTO comments (adId, userId, username, content, type, offerAmount)
                              VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$adId, $_SESSION['user_id'], $userName, $content, $type, $offerAmount]);

        // إشعار للمالك (إن لم يكن هو نفسه)
        if ($ad['userId'] != $_SESSION['user_id']) {
            $notifTitle = $type === 'offer' ? '💸 عرض جديد على إعلانك' : '💬 تعليق جديد على إعلانك';
            $notifContent = "قام {$userName} بـ " . ($type === 'offer' ? 'تقديم عرض' : 'التعليق') . " على \"{$ad['title']}\"";
            $db->prepare("INSERT INTO notifications (userId, title, content, type, link)
                          VALUES (?, ?, ?, ?, ?)")
               ->execute([$ad['userId'], $notifTitle, $notifContent, $type === 'offer' ? 'offer' : 'comment', "ad.php?id=$adId"]);
        }

        jsonSuccess([], $type === 'offer' ? 'تم إرسال عرضك ✓' : 'تم نشر تعليقك ✓');
    }

    // ---- تبديل المفضلة ----
    if ($action === 'toggle_favorite') {
        requireAuth();
        requireCsrf();
        $adId = (int)($input['id'] ?? $input['ad_id'] ?? $input['adId'] ?? 0);
        if ($adId <= 0) jsonError('معرّف غير صالح');

        $check = $db->prepare("SELECT id FROM favorites WHERE userId = ? AND adId = ?");
        $check->execute([$_SESSION['user_id'], $adId]);
        $existing = $check->fetchColumn();

        if ($existing) {
            $db->prepare("DELETE FROM favorites WHERE id = ?")->execute([$existing]);
            jsonSuccess(['is_favorite' => false], 'تمت إزالة الإعلان من المفضلة');
        } else {
            $db->prepare("INSERT INTO favorites (userId, adId) VALUES (?, ?)")
               ->execute([$_SESSION['user_id'], $adId]);
            jsonSuccess(['is_favorite' => true], '❤️ تمت إضافة الإعلان للمفضلة');
        }
    }

    // ---- إنشاء إعلان جديد ----
    if ($action === 'create') {
        requireAuth();
        requireCsrf();

        $title       = sanitize($input['title'] ?? '');
        $description = sanitize($input['description'] ?? '');
        $category    = sanitize($input['category'] ?? '');
        $city        = sanitize($input['city'] ?? '');
        $price       = isset($input['price']) ? (float)$input['price'] : null;
        $images      = $input['images'] ?? [];
        $latitude    = isset($input['latitude']) ? (float)$input['latitude'] : null;
        $longitude   = isset($input['longitude']) ? (float)$input['longitude'] : null;
        $locationName = sanitize($input['location_name'] ?? '');

        if (empty($title) || mb_strlen($title) < 5) jsonError('العنوان قصير جداً (5 أحرف على الأقل)');
        if (mb_strlen($title) > 255) jsonError('العنوان طويل جداً');
        if (empty($description) || mb_strlen($description) < 10) jsonError('الوصف قصير جداً (10 أحرف على الأقل)');
        if (empty($category) || empty($city)) jsonError('القسم والمدينة مطلوبان');

        // معالجة الصور (تقبل Base64 أو مسارات جاهزة)
        $imagePaths = [];
        if (is_array($images)) {
            $maxImages = (int)env('MAX_IMAGES_PER_AD', 8);
            $images = array_slice($images, 0, $maxImages);
            foreach ($images as $img) {
                $r = uploadBase64Image($img, 'ads');
                if ($r['success']) $imagePaths[] = $r['path'];
            }
        }

        // مواصفات
        $specs = [];
        $carBrand = $carYear = $carTrans = $carMileage = null;
        $propType = $propRooms = $propContract = null;

        if ($category === 'cars') {
            $carBrand = sanitize($input['carBrand'] ?? '');
            $carYear  = sanitize($input['carYear'] ?? '');
            $carTrans = sanitize($input['carTransmission'] ?? '');
            $carMileage = (int)($input['carMileage'] ?? 0) ?: null;
            if ($carBrand)   $specs['الماركة'] = $carBrand;
            if ($carYear)    $specs['السنة'] = $carYear;
            if ($carTrans)   $specs['ناقل الحركة'] = $carTrans;
            if ($carMileage) $specs['الممشى'] = number_format($carMileage) . ' كم';
        } elseif ($category === 'realestate') {
            $propType = sanitize($input['propertyType'] ?? '');
            $propRooms = sanitize($input['propertyRooms'] ?? '');
            $propContract = sanitize($input['propertyContract'] ?? '');
            if ($propType)     $specs['النوع'] = $propType;
            if ($propRooms)    $specs['الغرف'] = $propRooms;
            if ($propContract) $specs['نوع العقد'] = $propContract;
        }

        // إضافة مواصفات مخصصة
        if (isset($input['custom_specs']) && is_array($input['custom_specs'])) {
            foreach ($input['custom_specs'] as $key => $val) {
                $k = sanitize($key);
                $v = sanitize($val);
                if (!empty($k) && !empty($v)) $specs[$k] = $v;
            }
        }

        $slug = makeSlug($title);

        $stmt = $db->prepare("INSERT INTO ads (userId, title, slug, description, category, city, price,
                              images, specifications, carBrand, carYear, carTransmission, carMileage,
                              propertyType, propertyRooms, propertyContract, latitude, longitude, locationName, status)
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
        $stmt->execute([
            $_SESSION['user_id'], $title, $slug, $description, $category, $city, $price,
            json_encode($imagePaths, JSON_UNESCAPED_UNICODE),
            json_encode($specs, JSON_UNESCAPED_UNICODE),
            $carBrand, $carYear, $carTrans, $carMileage,
            $propType, $propRooms, $propContract,
            $latitude, $longitude, $locationName ?: null
        ]);

        $adId = $db->lastInsertId();
        jsonSuccess(['id' => (int)$adId, 'slug' => $slug, 'url' => "../frontend/ad.php?id=$adId&slug=$slug"], 'تم نشر إعلانك بنجاح ✓');
    }

    // ---- تحديث إعلان ----
    if ($action === 'update') {
        requireAuth();
        requireCsrf();
        $adId = (int)($input['id'] ?? $input['ad_id'] ?? $input['adId'] ?? 0);

        $check = $db->prepare("SELECT userId, images, status FROM ads WHERE id = ?");
        $check->execute([$adId]);
        $ad = $check->fetch();
        if (!$ad) jsonError('الإعلان غير موجود', 404);
        if ($ad['userId'] != $_SESSION['user_id'] && ($_SESSION['user_role'] ?? '') !== 'admin') {
            jsonError('ليس لديك صلاحية تعديل هذا الإعلان', 403);
        }

        $title       = sanitize($input['title'] ?? '');
        $description = sanitize($input['description'] ?? '');
        $category    = sanitize($input['category'] ?? '');
        $city        = sanitize($input['city'] ?? '');
        $price       = isset($input['price']) ? (float)$input['price'] : null;
        $images      = $input['images'] ?? [];

        if (empty($title) || mb_strlen($title) < 5) jsonError('العنوان قصير جداً');
        if (empty($description)) jsonError('الوصف مطلوب');

        // معالجة الصور: قد تكون مزيج من مسارات قديمة و Base64 جديدة
        $oldImages = json_decode($ad['images'] ?? '[]', true) ?: [];
        $newImagePaths = [];
        if (is_array($images)) {
            $maxImages = (int)env('MAX_IMAGES_PER_AD', 8);
            $images = array_slice($images, 0, $maxImages);
            foreach ($images as $img) {
                $r = uploadBase64Image($img, 'ads');
                if ($r['success']) $newImagePaths[] = $r['path'];
            }
        }

        // حذف الصور القديمة التي لم تعد مستخدمة
        foreach ($oldImages as $oldImg) {
            if (!in_array($oldImg, $newImagePaths)) {
                deleteUploadedFile($oldImg);
            }
        }

        $specs = [];
        $carBrand = $carYear = $carTrans = $carMileage = null;
        if ($category === 'cars') {
            $carBrand = sanitize($input['carBrand'] ?? '');
            $carYear  = sanitize($input['carYear'] ?? '');
            $carTrans = sanitize($input['carTransmission'] ?? '');
            $carMileage = (int)($input['carMileage'] ?? 0) ?: null;
            if ($carBrand)   $specs['الماركة'] = $carBrand;
            if ($carYear)    $specs['السنة'] = $carYear;
            if ($carTrans)   $specs['ناقل الحركة'] = $carTrans;
            if ($carMileage) $specs['الممشى'] = number_format($carMileage) . ' كم';
        }

        $slug = makeSlug($title);

        $stmt = $db->prepare("UPDATE ads SET title = ?, slug = ?, description = ?, category = ?, city = ?,
                              price = ?, images = ?, specifications = ?, carBrand = ?, carYear = ?,
                              carTransmission = ?, carMileage = ? WHERE id = ?");
        $stmt->execute([$title, $slug, $description, $category, $city, $price,
                        json_encode($newImagePaths, JSON_UNESCAPED_UNICODE),
                        json_encode($specs, JSON_UNESCAPED_UNICODE),
                        $carBrand, $carYear, $carTrans, $carMileage, $adId]);

        jsonSuccess(['id' => $adId, 'slug' => $slug], 'تم تحديث الإعلان ✓');
    }

    // ---- تغيير حالة الإعلان (sold/archived/active) ----
    // aliasing: mark_sold / archive / reactivate
    if (in_array($action, ['mark_sold','archive','reactivate'], true)) {
        $statusMap = ['mark_sold' => 'sold', 'archive' => 'archived', 'reactivate' => 'active'];
        $input['status'] = $statusMap[$action];
        $action = 'change_status';
    }
    if ($action === 'change_status') {
        requireAuth();
        requireCsrf();
        $adId = (int)($input['id'] ?? $input['ad_id'] ?? $input['adId'] ?? 0);
        $newStatus = $input['status'] ?? '';
        $allowed = ['active','sold','archived'];
        if (!in_array($newStatus, $allowed)) jsonError('حالة غير صالحة');

        $check = $db->prepare("SELECT userId FROM ads WHERE id = ?");
        $check->execute([$adId]);
        $ad = $check->fetch();
        if (!$ad || $ad['userId'] != $_SESSION['user_id']) jsonError('غير مسموح', 403);

        $db->prepare("UPDATE ads SET status = ? WHERE id = ?")->execute([$newStatus, $adId]);

        $messages = [
            'active'   => 'تم إعادة تفعيل الإعلان',
            'sold'     => '✓ تم تعليم الإعلان كمبيع',
            'archived' => '📦 تم أرشفة الإعلان'
        ];
        jsonSuccess(['status' => $newStatus], $messages[$newStatus]);
    }

    // ---- Bump (إعادة نشر/تجديد) ----
    if ($action === 'bump') {
        requireAuth();
        requireCsrf();
        $adId = (int)($input['id'] ?? $input['ad_id'] ?? $input['adId'] ?? 0);
        $check = $db->prepare("SELECT userId, bumpedAt FROM ads WHERE id = ?");
        $check->execute([$adId]);
        $ad = $check->fetch();
        if (!$ad || $ad['userId'] != $_SESSION['user_id']) jsonError('غير مسموح', 403);

        // السماح بـ bump مرة واحدة كل 24 ساعة
        if ($ad['bumpedAt'] && (time() - strtotime($ad['bumpedAt'])) < 86400) {
            $waitH = ceil((86400 - (time() - strtotime($ad['bumpedAt']))) / 3600);
            jsonError("يمكن تجديد الإعلان بعد $waitH ساعة");
        }

        $db->prepare("UPDATE ads SET bumpedAt = NOW(), status = 'active' WHERE id = ?")->execute([$adId]);
        jsonSuccess([], '🔥 تم تجديد الإعلان ورفعه إلى الأعلى');
    }

    // ---- حذف إعلان ----
    if ($action === 'delete') {
        requireAuth();
        requireCsrf();
        $adId = (int)($input['id'] ?? $input['ad_id'] ?? $input['adId'] ?? 0);

        $check = $db->prepare("SELECT userId, images FROM ads WHERE id = ?");
        $check->execute([$adId]);
        $ad = $check->fetch();
        if (!$ad) jsonError('الإعلان غير موجود', 404);

        $isOwner = $ad['userId'] == $_SESSION['user_id'];
        $isAdmin = ($_SESSION['user_role'] ?? '') === 'admin';
        if (!$isOwner && !$isAdmin) jsonError('غير مسموح', 403);

        // حذف الصور
        $images = json_decode($ad['images'] ?? '[]', true) ?: [];
        foreach ($images as $img) deleteUploadedFile($img);

        $db->prepare("DELETE FROM ads WHERE id = ?")->execute([$adId]);
        jsonSuccess([], '🗑️ تم حذف الإعلان');
    }
}

jsonError('إجراء غير معروف');
