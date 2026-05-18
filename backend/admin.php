<?php
require_once __DIR__ . '/config.php';
apiHeaders();

$db = getDBConnection();
requireAdmin($db); // Only admins can access these endpoints

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = $input['action'] ?? $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'stats') {
        $stats = [
            'users' => $db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
            'ads' => $db->query("SELECT COUNT(*) FROM ads")->fetchColumn(),
            'reports' => $db->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn(),
            'commissions' => $db->query("SELECT SUM(amount) FROM commission_transfers WHERE status = 'approved'")->fetchColumn() ?: 0
        ];
        jsonSuccess($stats);
    } elseif ($action === 'users') {
        $users = $db->query("SELECT id, name, phone, role, isBanned, joinedDate FROM users ORDER BY id DESC")->fetchAll();
        jsonSuccess($users);
    } elseif ($action === 'ads') {
        $ads = $db->query("
            SELECT a.id, a.title, a.category, a.price, a.city, a.createdAt, u.name as authorName 
            FROM ads a JOIN users u ON a.userId = u.id 
            ORDER BY a.createdAt DESC
        ")->fetchAll();
        jsonSuccess($ads);
    } elseif ($action === 'reports') {
        $reports = $db->query("
            SELECT r.*, a.title as adTitle, u.name as reporterName 
            FROM reports r 
            LEFT JOIN ads a ON r.adId = a.id 
            LEFT JOIN users u ON r.reporterId = u.id 
            ORDER BY r.createdAt DESC
        ")->fetchAll();
        jsonSuccess($reports);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'ban_user') {
        $userId = intval($input['user_id'] ?? 0);
        $db->prepare("UPDATE users SET isBanned = 1 WHERE id = ?")->execute([$userId]);
        jsonSuccess([], 'تم حظر المستخدم بنجاح');
    } elseif ($action === 'unban_user') {
        $userId = intval($input['user_id'] ?? 0);
        $db->prepare("UPDATE users SET isBanned = 0 WHERE id = ?")->execute([$userId]);
        jsonSuccess([], 'تم فك الحظر عن المستخدم');
    } elseif ($action === 'delete_ad') {
        $adId = intval($input['ad_id'] ?? 0);
        
        $stmt = $db->prepare("SELECT image FROM ads WHERE id = ?");
        $stmt->execute([$adId]);
        $ad = $stmt->fetch();
        if ($ad && !empty($ad['image']) && strpos($ad['image'], 'uploads/ads/') !== false) {
            $imgPath = __DIR__ . '/../' . $ad['image'];
            if (file_exists($imgPath)) unlink($imgPath);
        }
        
        $db->prepare("DELETE FROM ads WHERE id = ?")->execute([$adId]);
        jsonSuccess([], 'تم حذف الإعلان بنجاح من قبل الإدارة');
    } elseif ($action === 'resolve_report') {
        $reportId = intval($input['report_id'] ?? 0);
        $db->prepare("UPDATE reports SET status = 'resolved' WHERE id = ?")->execute([$reportId]);
        jsonSuccess([], 'تم حل البلاغ');
    }
}

jsonError('إجراء غير معروف في لوحة الإدارة');
