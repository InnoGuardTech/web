<?php
/**
 * backend/user.php - بروفايل المستخدمين v2.0
 */
require_once __DIR__ . '/config.php';
apiHeaders();

$db = getDBConnection();
$method = $_SERVER['REQUEST_METHOD'];
$input = getInput();
$action = $_GET['action'] ?? $input['action'] ?? '';

if ($method === 'GET') {
    if ($action === 'profile' || $action === '') {
        $userId = (int)($_GET['id'] ?? 0);
        $stmt = $db->prepare("SELECT u.id, u.name, u.avatar, u.bio, u.joinedDate, u.rating, u.ratingCount,
                                     u.isPhoneVerified, u.role, p.status, p.lastSeenAt
                              FROM users u LEFT JOIN user_presence p ON p.userId = u.id
                              WHERE u.id = ? AND u.isBanned = 0 AND u.deletedAt IS NULL");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        if (!$user) jsonError('المستخدم غير موجود', 404);

        $user['avatar_url'] = avatarUrl($user);

        // إحصائيات
        $statsStmt = $db->prepare("SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN status='active' THEN 1 ELSE 0 END) AS active,
            SUM(CASE WHEN status='sold' THEN 1 ELSE 0 END) AS sold,
            SUM(views) AS totalViews
            FROM ads WHERE userId = ? AND status != 'deleted'");
        $statsStmt->execute([$userId]);
        $stats = $statsStmt->fetch();

        // إعلانات
        $adsStmt = $db->prepare("SELECT id, title, slug, price, city, images, category, createdAt, status
                                 FROM ads WHERE userId = ? AND status IN ('active','sold')
                                 ORDER BY status = 'active' DESC, createdAt DESC LIMIT 24");
        $adsStmt->execute([$userId]);
        $ads = array_map(function($a){
            return [
                'id' => (int)$a['id'],
                'title' => $a['title'],
                'slug' => $a['slug'],
                'price' => formatPrice($a['price']),
                'city' => $a['city'],
                'image' => firstImage($a['images'], $a['category']),
                'date' => formatArabicDate($a['createdAt']),
                'status' => $a['status']
            ];
        }, $adsStmt->fetchAll());

        // التقييمات
        $rStmt = $db->prepare("SELECT r.*, u.avatar AS authorAvatar
                               FROM reviews r LEFT JOIN users u ON r.authorUserId = u.id
                               WHERE r.targetUserId = ? ORDER BY r.createdAt DESC LIMIT 30");
        $rStmt->execute([$userId]);
        $reviews = array_map(function($r){
            return [
                'id' => (int)$r['id'],
                'author' => $r['authorName'],
                'authorAvatar' => avatarUrl(['name'=>$r['authorName'],'avatar'=>$r['authorAvatar']]),
                'rating' => (int)$r['rating'],
                'content' => $r['content'],
                'date' => formatArabicDate($r['createdAt'])
            ];
        }, $rStmt->fetchAll());

        unset($user['phone']); // لا تكشف الهاتف
        jsonSuccess([
            'user' => $user,
            'stats' => [
                'total' => (int)$stats['total'],
                'active' => (int)$stats['active'],
                'sold' => (int)$stats['sold'],
                'views' => (int)$stats['totalViews']
            ],
            'ads' => $ads,
            'reviews' => $reviews
        ]);
    }
}

if ($method === 'POST') {
    requireAuth();
    requireCsrf();
    if ($action === 'add_review') {
        $targetId = (int)($input['target_id'] ?? 0);
        $rating   = max(1, min(5, (int)($input['rating'] ?? 5)));
        $content  = sanitize($input['content'] ?? '');

        if ($targetId == $_SESSION['user_id']) jsonError('لا يمكنك تقييم نفسك');
        if (empty($content)) jsonError('اكتب نص التقييم');
        if (mb_strlen($content) < 5) jsonError('التقييم قصير جداً');

        // فحص التقييم المكرر
        $dup = $db->prepare("SELECT id FROM reviews WHERE authorUserId = ? AND targetUserId = ?");
        $dup->execute([$_SESSION['user_id'], $targetId]);
        if ($dup->fetchColumn()) jsonError('سبق وقيّمت هذا المستخدم');

        $authorName = $_SESSION['user_name'] ?? 'مستخدم';
        $db->prepare("INSERT INTO reviews (targetUserId, authorUserId, authorName, rating, content)
                      VALUES (?, ?, ?, ?, ?)")
           ->execute([$targetId, $_SESSION['user_id'], $authorName, $rating, $content]);

        // إعادة حساب متوسط التقييم
        $avg = $db->prepare("SELECT AVG(rating) AS avg_r, COUNT(*) AS cnt FROM reviews WHERE targetUserId = ?");
        $avg->execute([$targetId]);
        $stats = $avg->fetch();
        $db->prepare("UPDATE users SET rating = ?, ratingCount = ? WHERE id = ?")
           ->execute([round($stats['avg_r'], 2), $stats['cnt'], $targetId]);

        // إشعار
        $db->prepare("INSERT INTO notifications (userId, title, content, type)
                      VALUES (?, ?, ?, 'review')")
           ->execute([$targetId, '⭐ تقييم جديد', "قيمك $authorName بـ $rating نجوم"]);

        jsonSuccess([], 'تم إضافة تقييمك');
    }
}

jsonError('طلب غير صالح');
