<?php
require_once __DIR__ . '/config.php';
apiHeaders();
requireAuth();

$db = getDBConnection();
$currentUserId = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? json_decode(file_get_contents('php://input'), true)['action'] ?? '';

if ($method === 'GET') {
    if ($action === 'list') {
        $stmt = $db->prepare("SELECT * FROM notifications WHERE userId = ? ORDER BY createdAt DESC LIMIT 20");
        $stmt->execute([$currentUserId]);
        $notifs = $stmt->fetchAll();
        
        $formatted = array_map(function($n) {
            return [
                'id' => $n['id'],
                'title' => $n['title'],
                'content' => $n['content'],
                'isRead' => (bool)$n['isRead'],
                'date' => formatArabicDate($n['createdAt'])
            ];
        }, $notifs);
        
        jsonSuccess($formatted);
    } elseif ($action === 'unread_count') {
        $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE userId = ? AND isRead = 0");
        $stmt->execute([$currentUserId]);
        jsonSuccess(['count' => $stmt->fetchColumn()]);
    }
} elseif ($method === 'POST') {
    if ($action === 'mark_read') {
        $db->prepare("UPDATE notifications SET isRead = 1 WHERE userId = ?")->execute([$currentUserId]);
        jsonSuccess([], 'تم تحديد الكل كمقروء');
    }
}

jsonError('طلب غير صالح');
