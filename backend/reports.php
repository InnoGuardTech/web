<?php
/**
 * backend/reports.php - نظام البلاغات v2.0
 */
require_once __DIR__ . '/config.php';
apiHeaders();
requireAuth();

$db = getDBConnection();
$me = (int)$_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];
$input = getInput();
$action = $_GET['action'] ?? $input['action'] ?? '';

if ($method === 'GET') {
    if ($action === 'my_reports') {
        $stmt = $db->prepare("SELECT r.*, a.title AS adTitle FROM reports r
                              LEFT JOIN ads a ON r.adId = a.id
                              WHERE r.reporterId = ? ORDER BY r.createdAt DESC LIMIT 50");
        $stmt->execute([$me]);
        jsonSuccess($stmt->fetchAll());
    }
}

if ($method === 'POST') {
    requireCsrf();

    if (in_array($action, ['submit', 'create', ''], true)) {
        $adId    = (int)($input['ad_id'] ?? $input['adId'] ?? 0);
        $reason  = sanitize($input['reason'] ?? '');
        $details = sanitize($input['details'] ?? $input['body'] ?? $input['content'] ?? '');

        if ($adId <= 0 || empty($reason)) jsonError('بيانات ناقصة');
        if (mb_strlen($reason) > 100) jsonError('سبب البلاغ طويل');
        if (mb_strlen($details) > 1000) jsonError('التفاصيل طويلة');

        // فحص وجود الإعلان
        $check = $db->prepare("SELECT userId FROM ads WHERE id = ? AND status != 'deleted'");
        $check->execute([$adId]);
        $ad = $check->fetch();
        if (!$ad) jsonError('الإعلان غير موجود', 404);

        if ($ad['userId'] == $me) jsonError('لا يمكنك الإبلاغ عن إعلانك');

        // فحص البلاغ المكرر
        $dup = $db->prepare("SELECT id FROM reports WHERE adId = ? AND reporterId = ? AND status = 'pending'");
        $dup->execute([$adId, $me]);
        if ($dup->fetchColumn()) jsonError('سبق وأرسلت بلاغاً عن هذا الإعلان وهو قيد المراجعة');

        // Rate limit
        $rl = rateLimit('report:' . $me, 5, 3600); // 5 بلاغات كل ساعة
        if (!$rl['allowed']) jsonError('عدد البلاغات كثير. حاول لاحقاً', 429);

        $db->prepare("INSERT INTO reports (adId, reporterId, reason, details) VALUES (?, ?, ?, ?)")
           ->execute([$adId, $me, $reason, $details ?: null]);

        // إشعار للإدارة
        $admins = $db->query("SELECT id FROM users WHERE role = 'admin'")->fetchAll();
        foreach ($admins as $admin) {
            $db->prepare("INSERT INTO notifications (userId, title, content, type, link)
                          VALUES (?, ?, ?, 'admin', ?)")
               ->execute([$admin['id'], '🚩 بلاغ جديد', "بلاغ جديد على الإعلان #$adId بسبب: $reason", "admin.php"]);
        }

        jsonSuccess([], 'تم إرسال بلاغك بنجاح. ستراجعه الإدارة قريباً.');
    }
}

jsonError('طلب غير صالح');
