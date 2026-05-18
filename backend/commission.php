<?php
/**
 * backend/commission.php - نظام العمولات v2.0
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
    if ($action === 'my_transfers' || $action === '') {
        $stmt = $db->prepare("SELECT c.*, a.title AS adTitle FROM commission_transfers c
                              LEFT JOIN ads a ON c.adId = a.id
                              WHERE c.userId = ? ORDER BY c.createdAt DESC LIMIT 50");
        $stmt->execute([$me]);
        $list = array_map(function($t){
            $statusLabels = ['pending'=>'⏳ قيد المراجعة','approved'=>'✅ مقبول','rejected'=>'❌ مرفوض'];
            return [
                'id' => (int)$t['id'],
                'amount' => formatPrice($t['amount']),
                'bankName' => $t['bankName'],
                'adId' => $t['adId'] ? (int)$t['adId'] : null,
                'adTitle' => $t['adTitle'],
                'transferDate' => $t['transferDate'],
                'proofImage' => $t['proofImage'] ? imageUrl($t['proofImage']) : null,
                'status' => $t['status'],
                'statusLabel' => $statusLabels[$t['status']] ?? $t['status'],
                'date' => formatArabicDate($t['createdAt'])
            ];
        }, $stmt->fetchAll());
        jsonSuccess($list);
    }
}

if ($method === 'POST') {
    requireCsrf();
    if ($action === 'submit') {
        $amount   = (float)($input['amount'] ?? 0);
        $bankName = sanitize($input['bank_name'] ?? '');
        $transferDate = sanitize($input['transfer_date'] ?? '');
        $adId     = (int)($input['ad_id'] ?? 0) ?: null;
        $notes    = sanitize($input['notes'] ?? '');
        $proof    = $input['proof_image'] ?? '';

        if ($amount <= 0) jsonError('المبلغ غير صحيح');
        if (empty($bankName)) jsonError('اختر البنك');
        if (empty($transferDate)) jsonError('أدخل تاريخ التحويل');
        if (empty($proof)) jsonError('يرجى رفع صورة إثبات التحويل');

        $upload = uploadBase64Image($proof, 'commission');
        if (!$upload['success']) jsonError($upload['message']);

        $db->prepare("INSERT INTO commission_transfers (userId, adId, amount, bankName, transferDate, proofImage, notes)
                      VALUES (?, ?, ?, ?, ?, ?, ?)")
           ->execute([$me, $adId, $amount, $bankName, $transferDate, $upload['path'], $notes ?: null]);

        // إشعار للإدارة
        $admins = $db->query("SELECT id FROM users WHERE role = 'admin'")->fetchAll();
        $userName = $_SESSION['user_name'] ?? 'مستخدم';
        foreach ($admins as $admin) {
            $db->prepare("INSERT INTO notifications (userId, title, content, type, link)
                          VALUES (?, ?, ?, 'admin', ?)")
               ->execute([$admin['id'], '💰 تحويل عمولة جديد', "أرسل $userName تحويل عمولة بقيمة " . formatPrice($amount), "admin.php"]);
        }

        jsonSuccess([], '✅ تم إرسال إثبات التحويل بنجاح. ستراجعه الإدارة قريباً.');
    }
}

jsonError('طلب غير صالح');
