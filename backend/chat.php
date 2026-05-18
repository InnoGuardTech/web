<?php
require_once __DIR__ . '/config.php';
apiHeaders();
requireAuth(); // Must be logged in

$db = getDBConnection();
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $input['action'] ?? '';
$currentUserId = $_SESSION['user_id'];

if ($method === 'GET') {
    if ($action === 'threads') {
        // Get all chat threads for the current user (either as buyer or ad owner)
        $stmt = $db->prepare("
            SELECT t.id, t.adId, t.unreadFor, a.title as adTitle, a.userId as sellerId, 
                   u_buyer.name as buyerName, u_seller.name as sellerName
            FROM chat_threads t
            JOIN ads a ON t.adId = a.id
            JOIN users u_buyer ON t.buyerId = u_buyer.id
            JOIN users u_seller ON a.userId = u_seller.id
            WHERE t.buyerId = ? OR a.userId = ?
            ORDER BY t.createdAt DESC
        ");
        $stmt->execute([$currentUserId, $currentUserId]);
        $threads = $stmt->fetchAll();
        
        $formatted = [];
        foreach ($threads as $t) {
            $isSeller = ($t['sellerId'] == $currentUserId);
            $otherName = $isSeller ? $t['buyerName'] : $t['sellerName'];
            $unreadArray = json_decode($t['unreadFor'], true) ?: [];
            $isUnread = in_array($currentUserId, $unreadArray);
            
            // Get last message
            $msgStmt = $db->prepare("SELECT text, createdAt FROM messages WHERE threadId = ? ORDER BY createdAt DESC LIMIT 1");
            $msgStmt->execute([$t['id']]);
            $lastMsg = $msgStmt->fetch();

            $formatted[] = [
                'id' => $t['id'],
                'adId' => $t['adId'],
                'adTitle' => $t['adTitle'],
                'otherName' => $otherName,
                'isUnread' => $isUnread,
                'lastMessage' => $lastMsg ? $lastMsg['text'] : 'لا توجد رسائل',
                'date' => $lastMsg ? formatArabicDate($lastMsg['createdAt']) : ''
            ];
        }
        jsonSuccess($formatted);
    } elseif ($action === 'messages') {
        $threadId = intval($_GET['thread_id'] ?? 0);
        
        // Verify access
        $stmt = $db->prepare("
            SELECT t.*, a.userId as sellerId 
            FROM chat_threads t JOIN ads a ON t.adId = a.id 
            WHERE t.id = ?
        ");
        $stmt->execute([$threadId]);
        $thread = $stmt->fetch();
        
        if (!$thread || ($thread['buyerId'] != $currentUserId && $thread['sellerId'] != $currentUserId)) {
            jsonError('غير مصرح لك بمشاهدة هذه المحادثة', 403);
        }

        // Mark as read
        $unreadArray = json_decode($thread['unreadFor'], true) ?: [];
        if (($key = array_search($currentUserId, $unreadArray)) !== false) {
            unset($unreadArray[$key]);
            $db->prepare("UPDATE chat_threads SET unreadFor = ? WHERE id = ?")
               ->execute([json_encode(array_values($unreadArray)), $threadId]);
        }

        // Get messages (support polling with last_msg_id)
        $lastMsgId = intval($_GET['last_msg_id'] ?? 0);
        if ($lastMsgId > 0) {
            $msgStmt = $db->prepare("SELECT m.*, u.name as senderName FROM messages m JOIN users u ON m.senderId = u.id WHERE threadId = ? AND m.id > ? ORDER BY m.createdAt ASC");
            $msgStmt->execute([$threadId, $lastMsgId]);
        } else {
            $msgStmt = $db->prepare("SELECT m.*, u.name as senderName FROM messages m JOIN users u ON m.senderId = u.id WHERE threadId = ? ORDER BY m.createdAt ASC");
            $msgStmt->execute([$threadId]);
        }
        
        $messages = array_map(function($m) use ($currentUserId) {
            return [
                'id' => $m['id'],
                'text' => $m['text'],
                'senderName' => $m['senderName'],
                'isMe' => ($m['senderId'] == $currentUserId),
                'date' => date('H:i', strtotime($m['createdAt']))
            ];
        }, $msgStmt->fetchAll());
        
        jsonSuccess(['threadId' => $threadId, 'adId' => $thread['adId'], 'messages' => $messages]);
    } elseif ($action === 'unread_count') {
        $stmt = $db->prepare("SELECT COUNT(*) FROM chat_threads WHERE JSON_CONTAINS(unreadFor, ?)");
        $stmt->execute([json_encode($currentUserId)]);
        jsonSuccess(['count' => $stmt->fetchColumn()]);
    }
} elseif ($method === 'POST') {
    if ($action === 'send') {
        $adId = intval($input['ad_id'] ?? 0);
        $threadId = intval($input['thread_id'] ?? 0);
        $text = sanitize($input['text'] ?? '');

        if (empty($text)) jsonError('لا يمكن إرسال رسالة فارغة');

        if ($threadId === 0 && $adId > 0) {
            // New conversation started from Ad page
            $stmt = $db->prepare("SELECT userId FROM ads WHERE id = ?");
            $stmt->execute([$adId]);
            $sellerId = $stmt->fetchColumn();

            if (!$sellerId) jsonError('الإعلان غير موجود');
            if ($sellerId == $currentUserId) jsonError('لا يمكنك مراسلة نفسك');

            // Check if thread already exists
            $check = $db->prepare("SELECT id FROM chat_threads WHERE adId = ? AND buyerId = ?");
            $check->execute([$adId, $currentUserId]);
            $existing = $check->fetchColumn();

            if ($existing) {
                $threadId = $existing;
            } else {
                $db->prepare("INSERT INTO chat_threads (adId, buyerId) VALUES (?, ?)")->execute([$adId, $currentUserId]);
                $threadId = $db->lastInsertId();
            }
        }

        if ($threadId > 0) {
            // Get other user to set unread flag
            $stmt = $db->prepare("SELECT buyerId, a.userId as sellerId FROM chat_threads t JOIN ads a ON t.adId = a.id WHERE t.id = ?");
            $stmt->execute([$threadId]);
            $thread = $stmt->fetch();
            
            $receiverId = ($thread['buyerId'] == $currentUserId) ? $thread['sellerId'] : $thread['buyerId'];

            // Insert message
            $db->prepare("INSERT INTO messages (threadId, senderId, text) VALUES (?, ?, ?)")
               ->execute([$threadId, $currentUserId, $text]);

            // Set unread and notify
            $db->prepare("UPDATE chat_threads SET unreadFor = ? WHERE id = ?")
               ->execute([json_encode([$receiverId]), $threadId]);
               
            $db->prepare("INSERT INTO notifications (userId, title, content) VALUES (?, 'رسالة جديدة', ?)")
               ->execute([$receiverId, 'لديك رسالة جديدة بخصوص إعلان.']);

            jsonSuccess(['threadId' => $threadId], 'تم الإرسال');
        } else {
            jsonError('بيانات مفقودة');
        }
    }
}

jsonError('طلب غير صالح');
