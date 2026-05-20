<?php
/**
 * backend/admin.php - لوحة التحكم v2.0
 */
require_once __DIR__ . '/config.php';
apiHeaders();

$db = getDBConnection();
requireAdmin($db);

$method = $_SERVER['REQUEST_METHOD'];
$input = getInput();
$action = $_GET['action'] ?? $input['action'] ?? '';

if ($method === 'GET') {
    if ($action === 'stats') {
        $stats = [
            'totalUsers'       => (int)$db->query("SELECT COUNT(*) FROM users WHERE deletedAt IS NULL")->fetchColumn(),
            'totalAds'         => (int)$db->query("SELECT COUNT(*) FROM ads WHERE status != 'deleted'")->fetchColumn(),
            'activeAds'        => (int)$db->query("SELECT COUNT(*) FROM ads WHERE status = 'active'")->fetchColumn(),
            'soldAds'          => (int)$db->query("SELECT COUNT(*) FROM ads WHERE status = 'sold'")->fetchColumn(),
            'pendingReports'   => (int)$db->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn(),
            'totalComments'    => (int)$db->query("SELECT COUNT(*) FROM comments")->fetchColumn(),
            'totalMessages'    => (int)$db->query("SELECT COUNT(*) FROM messages")->fetchColumn(),
            'totalCommissions' => (float)($db->query("SELECT COALESCE(SUM(amount),0) FROM commission_transfers WHERE status = 'approved'")->fetchColumn()),
            'pendingCommissions' => (int)$db->query("SELECT COUNT(*) FROM commission_transfers WHERE status = 'pending'")->fetchColumn(),
            'newUsersToday'    => (int)$db->query("SELECT COUNT(*) FROM users WHERE date(createdAt) = date('now')")->fetchColumn(),
            'newAdsToday'      => (int)$db->query("SELECT COUNT(*) FROM ads WHERE date(createdAt) = date('now') AND status != 'removed'")->fetchColumn(),
        ];
        // إحصائيات آخر 7 أيام
        $weeklyStmt = $db->query("SELECT date(createdAt) AS day, COUNT(*) AS c FROM ads WHERE createdAt > date('now', '-7 days') GROUP BY day ORDER BY day");
        $stats['weeklyAds'] = $weeklyStmt->fetchAll();
        jsonSuccess($stats);
    }

    if ($action === 'users' || $action === 'list_users') {
        $search = trim($_GET['q'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 30;
        $offset = ($page - 1) * $perPage;

        $where = "1=1";
        $params = [];
        if (!empty($search)) {
            $where .= " AND (name LIKE ? OR phone LIKE ? OR email LIKE ?)";
            $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
        }
        $countStmt = $db->prepare("SELECT COUNT(*) FROM users WHERE $where");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $db->prepare("SELECT id, name, phone, email, role, isBanned, isPhoneVerified,
                              rating, ratingCount, joinedDate, createdAt
                              FROM users WHERE $where ORDER BY id DESC LIMIT $perPage OFFSET $offset");
        $stmt->execute($params);
        jsonSuccess(['users' => $stmt->fetchAll(), 'total' => $total, 'page' => $page, 'per_page' => $perPage]);
    }

    if ($action === 'ads' || $action === 'list_ads') {
        $status = $_GET['status'] ?? 'all';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 30;
        $offset = ($page - 1) * $perPage;

        $where = "a.status != 'removed'";
        $params = [];
        if ($status !== 'all') {
            $where .= " AND a.status = ?";
            $params[] = $status;
        }
        $countStmt = $db->prepare("SELECT COUNT(*) FROM ads a WHERE $where");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $db->prepare("SELECT a.id, a.title, a.category, a.price, a.city, a.status, a.views, a.createdAt,
                                     u.name AS authorName, u.id AS authorId
                              FROM ads a JOIN users u ON a.userId = u.id
                              WHERE $where ORDER BY a.id DESC LIMIT $perPage OFFSET $offset");
        $stmt->execute($params);
        $ads = array_map(function($a){
            return [
                'id' => (int)$a['id'],
                'title' => $a['title'],
                'category' => getCategoryName($a['category']),
                'price' => formatPrice($a['price']),
                'city' => $a['city'],
                'status' => $a['status'],
                'views' => (int)$a['views'],
                'authorName' => $a['authorName'],
                'authorId' => (int)$a['authorId'],
                'date' => formatArabicDate($a['createdAt'])
            ];
        }, $stmt->fetchAll());
        jsonSuccess(['ads' => $ads, 'total' => $total, 'page' => $page, 'per_page' => $perPage]);
    }

    if ($action === 'reports') {
        $stmt = $db->query("SELECT r.*, a.title AS adTitle, u.name AS reporterName
                            FROM reports r
                            LEFT JOIN ads a ON r.adId = a.id
                            LEFT JOIN users u ON r.reporterId = u.id
                            ORDER BY r.status = 'pending' DESC, r.createdAt DESC LIMIT 100");
        jsonSuccess($stmt->fetchAll());
    }

    if ($action === 'commissions') {
        $stmt = $db->query("SELECT c.*, u.name AS userName, u.phone AS userPhone, a.title AS adTitle
                            FROM commission_transfers c
                            LEFT JOIN users u ON c.userId = u.id
                            LEFT JOIN ads a ON c.adId = a.id
                            ORDER BY c.status = 'pending' DESC, c.createdAt DESC LIMIT 100");
        $list = array_map(function($t){
            return [
                'id' => (int)$t['id'],
                'userName' => $t['userName'],
                'userPhone' => $t['userPhone'],
                'adTitle' => $t['adTitle'],
                'amount' => formatPrice($t['amount']),
                'amountRaw' => (float)$t['amount'],
                'bankName' => $t['bankName'],
                'transferDate' => $t['transferDate'],
                'proofImage' => $t['proofImage'] ? imageUrl($t['proofImage']) : null,
                'status' => $t['status'],
                'notes' => $t['notes'],
                'date' => formatArabicDate($t['createdAt'])
            ];
        }, $stmt->fetchAll());
        jsonSuccess($list);
    }
}

if ($method === 'POST') {
    requireCsrf();

    if ($action === 'ban_user') {
        $userId = (int)($input['user_id'] ?? 0);
        if ($userId == $_SESSION['user_id']) jsonError('لا يمكنك حظر نفسك');
        $db->prepare("UPDATE users SET isBanned = 1 WHERE id = ?")->execute([$userId]);
        jsonSuccess([], 'تم حظر المستخدم');
    }

    if ($action === 'unban_user') {
        $userId = (int)($input['user_id'] ?? 0);
        $db->prepare("UPDATE users SET isBanned = 0 WHERE id = ?")->execute([$userId]);
        jsonSuccess([], 'تم رفع الحظر');
    }

    if ($action === 'change_role') {
        $userId = (int)($input['user_id'] ?? 0);
        $role = $input['role'] ?? '';
        if (!in_array($role, ['user','seller','admin','moderator'])) jsonError('دور غير صالح');
        $db->prepare("UPDATE users SET role = ? WHERE id = ?")->execute([$role, $userId]);
        jsonSuccess([], 'تم تحديث الدور');
    }

    if ($action === 'delete_ad') {
        $adId = (int)($input['ad_id'] ?? 0);
        $stmt = $db->prepare("SELECT images FROM ads WHERE id = ?");
        $stmt->execute([$adId]);
        $ad = $stmt->fetch();
        if ($ad) {
            $images = json_decode($ad['images'] ?? '[]', true) ?: [];
            foreach ($images as $img) deleteUploadedFile($img);
        }
        $db->prepare("DELETE FROM ads WHERE id = ?")->execute([$adId]);
        jsonSuccess([], 'تم حذف الإعلان');
    }

    if ($action === 'toggle_pin') {
        $adId = (int)($input['ad_id'] ?? 0);
        $db->prepare("UPDATE ads SET isPinned = NOT isPinned WHERE id = ?")->execute([$adId]);
        jsonSuccess([], 'تم تحديث التثبيت');
    }

    if ($action === 'resolve_report') {
        $reportId = (int)($input['report_id'] ?? 0);
        $newStatus = in_array($input['status'] ?? 'resolved', ['resolved','dismissed']) ? $input['status'] : 'resolved';
        $db->prepare("UPDATE reports SET status = ?, resolvedBy = ?, resolvedAt = NOW() WHERE id = ?")
           ->execute([$newStatus, $_SESSION['user_id'], $reportId]);
        jsonSuccess([], 'تم تحديث البلاغ');
    }

    if ($action === 'approve_commission') {
        $id = (int)($input['id'] ?? 0);
        $db->prepare("UPDATE commission_transfers SET status = 'approved', reviewedBy = ?, reviewedAt = NOW() WHERE id = ?")
           ->execute([$_SESSION['user_id'], $id]);
        // إشعار للمستخدم
        $info = $db->prepare("SELECT userId, amount FROM commission_transfers WHERE id = ?");
        $info->execute([$id]);
        if ($r = $info->fetch()) {
            $db->prepare("INSERT INTO notifications (userId, title, content, type)
                          VALUES (?, '✅ تم قبول تحويل العمولة', ?, 'admin')")
               ->execute([$r['userId'], 'تم قبول تحويل العمولة بقيمة ' . formatPrice($r['amount']) . '. شكراً لك.']);
        }
        jsonSuccess([], 'تم القبول');
    }

    if ($action === 'reject_commission') {
        $id = (int)($input['id'] ?? 0);
        $db->prepare("UPDATE commission_transfers SET status = 'rejected', reviewedBy = ?, reviewedAt = NOW() WHERE id = ?")
           ->execute([$_SESSION['user_id'], $id]);
        jsonSuccess([], 'تم الرفض');
    }
}

jsonError('إجراء غير معروف');
