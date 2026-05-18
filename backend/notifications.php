<?php
/**
 * backend/notifications.php - الإشعارات v2.0
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
    if ($action === 'list' || $action === '') {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $stmt = $db->prepare("SELECT * FROM notifications WHERE userId = ? ORDER BY createdAt DESC LIMIT $perPage OFFSET $offset");
        $stmt->execute([$me]);
        $list = array_map(function($n){
            $icons = ['comment'=>'💬','message'=>'✉️','offer'=>'💰','admin'=>'⚠️','system'=>'🔔','favorite'=>'❤️','review'=>'⭐'];
            return [
                'id' => (int)$n['id'],
                'title' => $n['title'],
                'content' => $n['content'],
                'type' => $n['type'],
                'icon' => $icons[$n['type']] ?? '🔔',
                'link' => $n['link'],
                'isRead' => (bool)$n['isRead'],
                'date' => formatArabicDate($n['createdAt'])
            ];
        }, $stmt->fetchAll());
        jsonSuccess($list);
    }
    if ($action === 'unread_count') {
        $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE userId = ? AND isRead = 0");
        $stmt->execute([$me]);
        jsonSuccess(['count' => (int)$stmt->fetchColumn()]);
    }
}

if ($method === 'POST') {
    requireCsrf();
    if ($action === 'mark_read') {
        $id = (int)($input['id'] ?? 0);
        if ($id > 0) {
            $db->prepare("UPDATE notifications SET isRead = 1 WHERE id = ? AND userId = ?")->execute([$id, $me]);
        }
        jsonSuccess([]);
    }
    if ($action === 'mark_all_read') {
        $db->prepare("UPDATE notifications SET isRead = 1 WHERE userId = ?")->execute([$me]);
        jsonSuccess([], 'تم تعليم الكل كمقروء');
    }
    if ($action === 'delete') {
        $id = (int)($input['id'] ?? 0);
        $db->prepare("DELETE FROM notifications WHERE id = ? AND userId = ?")->execute([$id, $me]);
        jsonSuccess([], 'تم الحذف');
    }
    if ($action === 'clear_all') {
        $db->prepare("DELETE FROM notifications WHERE userId = ? AND isRead = 1")->execute([$me]);
        jsonSuccess([], 'تم مسح الإشعارات المقروءة');
    }
}

jsonError('طلب غير صالح');
