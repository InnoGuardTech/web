<?php
/**
 * backend/presence.php - الحضور (Online/Offline) v2.0
 */
require_once __DIR__ . '/config.php';
apiHeaders();
requireAuth();

$db = getDBConnection();
$me = (int)$_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];
$input = getInput();
$action = $_GET['action'] ?? $input['action'] ?? '';

if ($method === 'POST') {
    if ($action === 'ping' || $action === 'heartbeat') {
        $status = in_array($input['status'] ?? 'online', ['online','away','offline']) ? $input['status'] : 'online';
        $db->prepare("INSERT INTO user_presence (userId, status) VALUES (?, ?)
                      ON DUPLICATE KEY UPDATE status = ?, lastSeenAt = NOW()")
           ->execute([$me, $status, $status]);
        $db->prepare("UPDATE users SET lastSeenAt = NOW() WHERE id = ?")->execute([$me]);
        jsonSuccess(['status' => $status]);
    }
    if ($action === 'offline') {
        $db->prepare("UPDATE user_presence SET status = 'offline' WHERE userId = ?")->execute([$me]);
        jsonSuccess([]);
    }
}

if ($method === 'GET') {
    if ($action === 'check') {
        $userIds = $_GET['user_ids'] ?? '';
        $ids = array_filter(array_map('intval', explode(',', $userIds)));
        if (empty($ids)) jsonSuccess([]);

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $db->prepare("SELECT userId, status, lastSeenAt FROM user_presence WHERE userId IN ($placeholders)");
        $stmt->execute($ids);
        $rows = $stmt->fetchAll();

        $result = [];
        foreach ($rows as $r) {
            // إذا lastSeenAt > 60 ثانية، اعتبره offline
            $diff = time() - strtotime($r['lastSeenAt']);
            $status = $r['status'];
            if ($diff > 60) $status = 'offline';
            $result[(int)$r['userId']] = [
                'status' => $status,
                'lastSeen' => formatArabicDate($r['lastSeenAt'])
            ];
        }
        jsonSuccess($result);
    }
}

jsonError('طلب غير صالح');
