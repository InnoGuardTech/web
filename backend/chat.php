<?php
/**
 * backend/chat.php - نظام الدردشة المتقدم v2.0
 * ✅ Long Polling + SSE للوقت الحقيقي
 * ✅ Typing Indicator
 * ✅ Read Receipts
 * ✅ Online/Offline Status
 * ✅ Image/File Attachments
 * ✅ Delete Message
 */
require_once __DIR__ . '/config.php';
apiHeaders();
requireAuth();

$db = getDBConnection();
$method = $_SERVER['REQUEST_METHOD'];
$input  = getInput();
$action = $_GET['action'] ?? $input['action'] ?? '';
$me     = (int)$_SESSION['user_id'];

// تحديث الحضور
$db->prepare("INSERT INTO user_presence (userId, status) VALUES (?, 'online')
              ON DUPLICATE KEY UPDATE status='online', lastSeenAt=NOW()")
   ->execute([$me]);

if ($method === 'GET') {

    // ---- قائمة المحادثات ----
    if ($action === 'threads') {
        $stmt = $db->prepare("
            SELECT t.id, t.adId, t.buyerId, t.sellerId, t.buyerUnread, t.sellerUnread, t.lastMessageAt,
                   a.title AS adTitle, a.images AS adImages, a.category,
                   ub.name AS buyerName, ub.avatar AS buyerAvatar,
                   us.name AS sellerName, us.avatar AS sellerAvatar,
                   pb.status AS buyerStatus, ps.status AS sellerStatus,
                   (SELECT text FROM messages WHERE threadId = t.id ORDER BY id DESC LIMIT 1) AS lastText,
                   (SELECT type FROM messages WHERE threadId = t.id ORDER BY id DESC LIMIT 1) AS lastType
            FROM chat_threads t
            JOIN ads a ON t.adId = a.id
            JOIN users ub ON t.buyerId = ub.id
            JOIN users us ON t.sellerId = us.id
            LEFT JOIN user_presence pb ON pb.userId = t.buyerId
            LEFT JOIN user_presence ps ON ps.userId = t.sellerId
            WHERE (t.buyerId = ? OR t.sellerId = ?)
              AND (t.deletedBy IS NULL OR NOT JSON_CONTAINS(t.deletedBy, ?))
            ORDER BY COALESCE(t.lastMessageAt, t.createdAt) DESC
        ");
        $stmt->execute([$me, $me, json_encode($me)]);

        $formatted = [];
        foreach ($stmt->fetchAll() as $t) {
            $isSeller = ($t['sellerId'] == $me);
            $otherId  = $isSeller ? $t['buyerId'] : $t['sellerId'];
            $otherName = $isSeller ? $t['buyerName'] : $t['sellerName'];
            $otherAvatar = $isSeller ? $t['buyerAvatar'] : $t['sellerAvatar'];
            $otherStatus = $isSeller ? $t['buyerStatus'] : $t['sellerStatus'];
            $unread = $isSeller ? $t['sellerUnread'] : $t['buyerUnread'];

            $lastMsg = $t['lastText'] ?? 'لا توجد رسائل';
            if ($t['lastType'] === 'image') $lastMsg = '📷 صورة';
            elseif ($t['lastType'] === 'file') $lastMsg = '📎 ملف';

            $formatted[] = [
                'id'        => (int)$t['id'],
                'adId'      => (int)$t['adId'],
                'adTitle'   => $t['adTitle'],
                'adImage'   => firstImage($t['adImages'], $t['category']),
                'otherId'   => (int)$otherId,
                'otherName' => $otherName,
                'otherAvatar' => avatarUrl(['name'=>$otherName,'avatar'=>$otherAvatar]),
                'otherStatus' => $otherStatus ?: 'offline',
                'unread'    => (int)$unread,
                'unreadCount' => (int)$unread,
                'isUnread'  => $unread > 0,
                'lastMessage' => mb_strimwidth($lastMsg ?: '', 0, 60, '...'),
                'lastMessageAt' => $t['lastMessageAt'],
                'date'      => $t['lastMessageAt'] ? formatArabicDate($t['lastMessageAt']) : ''
            ];
        }
        jsonSuccess($formatted);
    }

    // ---- جلب رسائل محادثة ----
    if ($action === 'messages') {
        $threadId = (int)($_GET['thread_id'] ?? 0);
        $lastId   = (int)($_GET['last_id'] ?? 0); // للـ polling - فقط الرسائل بعد هذا الرقم

        // التحقق من الصلاحية
        $tStmt = $db->prepare("SELECT * FROM chat_threads WHERE id = ?");
        $tStmt->execute([$threadId]);
        $thread = $tStmt->fetch();
        if (!$thread || ($thread['buyerId'] != $me && $thread['sellerId'] != $me)) {
            jsonError('غير مصرح', 403);
        }

        $isSeller = ($thread['sellerId'] == $me);

        // تعليم كمقروء
        if ($isSeller && $thread['sellerUnread'] > 0) {
            $db->prepare("UPDATE chat_threads SET sellerUnread = 0 WHERE id = ?")->execute([$threadId]);
        } elseif (!$isSeller && $thread['buyerUnread'] > 0) {
            $db->prepare("UPDATE chat_threads SET buyerUnread = 0 WHERE id = ?")->execute([$threadId]);
        }
        // تحديث readAt للرسائل القادمة من الطرف الآخر
        $db->prepare("UPDATE messages SET isRead = 1, readAt = NOW() WHERE threadId = ? AND senderId != ? AND isRead = 0")
           ->execute([$threadId, $me]);

        // جلب الرسائل
        $sql = "SELECT m.*, u.name AS senderName, u.avatar AS senderAvatar
                FROM messages m JOIN users u ON m.senderId = u.id
                WHERE m.threadId = ? AND m.isDeleted = 0";
        $params = [$threadId];
        if ($lastId > 0) {
            $sql .= " AND m.id > ?";
            $params[] = $lastId;
        }
        $sql .= " ORDER BY m.id ASC LIMIT 200";

        $mStmt = $db->prepare($sql);
        $mStmt->execute($params);
        $messages = array_map(function($m) use ($me){
            return [
                'id'         => (int)$m['id'],
                'text'       => $m['text'],
                'body'       => $m['text'],
                'content'    => $m['text'],
                'message'    => $m['text'],
                'type'       => $m['type'],
                'attachment' => $m['attachment'] ? imageUrl($m['attachment']) : null,
                'senderId'   => (int)$m['senderId'],
                'senderName' => $m['senderName'],
                'isMe'       => ($m['senderId'] == $me),
                'isRead'     => (bool)$m['isRead'],
                'date'       => date('H:i', strtotime($m['createdAt'])),
                'dateFull'   => $m['createdAt'],
                'timestamp'  => strtotime($m['createdAt'])
            ];
        }, $mStmt->fetchAll());

        // معلومات الطرف الآخر
        $otherId = $isSeller ? $thread['buyerId'] : $thread['sellerId'];
        $otherStmt = $db->prepare("SELECT u.id, u.name, u.avatar, p.status, p.lastSeenAt
                                   FROM users u LEFT JOIN user_presence p ON p.userId = u.id WHERE u.id = ?");
        $otherStmt->execute([$otherId]);
        $other = $otherStmt->fetch();
        $other['avatar_url'] = avatarUrl(['name'=>$other['name'],'avatar'=>$other['avatar']]);

        // معلومات الإعلان
        $adStmt = $db->prepare("SELECT id, title, slug, price, images, category FROM ads WHERE id = ?");
        $adStmt->execute([$thread['adId']]);
        $ad = $adStmt->fetch();
        if ($ad) {
            $ad['image'] = firstImage($ad['images'], $ad['category']);
            $ad['priceFormatted'] = formatPrice($ad['price']);
        }

        // هل الطرف الآخر يكتب الآن؟
        $typingStmt = $db->prepare("SELECT 1 FROM typing_status WHERE threadId = ? AND userId = ? AND updatedAt > DATE_SUB(NOW(), INTERVAL 5 SECOND)");
        $typingStmt->execute([$threadId, $otherId]);
        $isTyping = (bool)$typingStmt->fetchColumn();

        jsonSuccess([
            'threadId' => $threadId,
            'ad'       => $ad,
            'other'    => $other,
            'isTyping' => $isTyping,
            'messages' => $messages
        ]);
    }

    // ---- عدد الرسائل غير المقروءة (للـ badge) ----
    if ($action === 'unread_count') {
        $stmt = $db->prepare("SELECT
            COALESCE(SUM(CASE WHEN buyerId = ? THEN buyerUnread ELSE 0 END), 0) +
            COALESCE(SUM(CASE WHEN sellerId = ? THEN sellerUnread ELSE 0 END), 0) AS total
            FROM chat_threads WHERE buyerId = ? OR sellerId = ?");
        $stmt->execute([$me, $me, $me, $me]);
        $count = (int)$stmt->fetchColumn();
        jsonSuccess(['count' => $count]);
    }

    // ---- Server-Sent Events للوقت الحقيقي ----
    if ($action === 'sse') {
        $threadId = (int)($_GET['thread_id'] ?? 0);
        $lastId   = (int)($_GET['last_id'] ?? 0);

        // تحقق من الصلاحية
        $tStmt = $db->prepare("SELECT buyerId, sellerId FROM chat_threads WHERE id = ?");
        $tStmt->execute([$threadId]);
        $thread = $tStmt->fetch();
        if (!$thread || ($thread['buyerId'] != $me && $thread['sellerId'] != $me)) {
            jsonError('غير مصرح', 403);
        }
        $otherId = ($thread['sellerId'] == $me) ? $thread['buyerId'] : $thread['sellerId'];

        // إعدادات SSE
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('X-Accel-Buffering: no'); // لـ nginx
        @ob_end_clean();
        @ini_set('zlib.output_compression', '0');
        set_time_limit(0);
        ignore_user_abort(true);

        $startTime = time();
        $maxRunTime = 30; // ثانية - ثم العميل يعيد الاتصال

        while (true) {
            // فحص قطع الاتصال
            if (connection_aborted()) break;
            if ((time() - $startTime) >= $maxRunTime) break;

            // جلب الرسائل الجديدة
            $msgStmt = $db->prepare("SELECT m.*, u.name AS senderName FROM messages m JOIN users u ON m.senderId = u.id
                                     WHERE m.threadId = ? AND m.id > ? AND m.isDeleted = 0 ORDER BY m.id ASC");
            $msgStmt->execute([$threadId, $lastId]);
            $newMessages = $msgStmt->fetchAll();

            if (!empty($newMessages)) {
                foreach ($newMessages as $m) {
                    $payload = [
                        'id'         => (int)$m['id'],
                        'text'       => $m['text'],
                        'type'       => $m['type'],
                        'attachment' => $m['attachment'] ? imageUrl($m['attachment']) : null,
                        'senderId'   => (int)$m['senderId'],
                        'senderName' => $m['senderName'],
                        'isMe'       => ($m['senderId'] == $me),
                        'isRead'     => (bool)$m['isRead'],
                        'date'       => date('H:i', strtotime($m['createdAt']))
                    ];
                    echo "event: message\n";
                    echo "data: " . json_encode($payload, JSON_UNESCAPED_UNICODE) . "\n\n";
                    $lastId = (int)$m['id'];
                }
                @ob_flush();
                @flush();
            }

            // فحص حالة الكتابة
            $typingStmt = $db->prepare("SELECT 1 FROM typing_status WHERE threadId = ? AND userId = ? AND updatedAt > DATE_SUB(NOW(), INTERVAL 5 SECOND)");
            $typingStmt->execute([$threadId, $otherId]);
            $isTyping = (bool)$typingStmt->fetchColumn();
            echo "event: typing\n";
            echo "data: " . json_encode(['typing' => $isTyping]) . "\n\n";
            @ob_flush();
            @flush();

            // فحص حالة الوجود
            $presStmt = $db->prepare("SELECT status FROM user_presence WHERE userId = ?");
            $presStmt->execute([$otherId]);
            $status = $presStmt->fetchColumn() ?: 'offline';
            echo "event: presence\n";
            echo "data: " . json_encode(['userId' => $otherId, 'status' => $status]) . "\n\n";
            @ob_flush();
            @flush();

            // ping
            echo ": ping " . time() . "\n\n";
            @ob_flush();
            @flush();

            sleep(2);
        }
        exit;
    }
}

if ($method === 'POST') {

    // ---- إرسال رسالة ----
    if ($action === 'send' || $action === 'send_image') {
        requireCsrf();
        $adId     = (int)($input['ad_id'] ?? $input['adId'] ?? 0);
        $threadId = (int)($input['thread_id'] ?? $input['threadId'] ?? 0);
        $text     = sanitize($input['text'] ?? $input['message'] ?? $input['body'] ?? $input['content'] ?? '');
        $attachment = $input['attachment'] ?? $input['image'] ?? null;
        $type     = $input['type'] ?? ($action === 'send_image' ? 'image' : 'text');

        if (empty($text) && empty($attachment) && !isset($_FILES['image'])) jsonError('لا يمكن إرسال رسالة فارغة');
        if (mb_strlen($text) > 2000) jsonError('الرسالة طويلة جداً');

        // معالجة المرفق
        $attachmentPath = null;
        if ($action === 'send_image' && isset($_FILES['image'])) {
            $upload = uploadImage($_FILES['image'], 'chat');
            if (!$upload['success']) jsonError($upload['message']);
            $attachmentPath = $upload['path'];
            $type = 'image';
        } elseif (!empty($attachment)) {
            $upload = uploadBase64Image($attachment, 'chat');
            if (!$upload['success']) jsonError($upload['message']);
            $attachmentPath = $upload['path'];
            if ($type === 'text') $type = 'image';
        }

        // إنشاء thread جديد إن لزم
        if ($threadId === 0 && $adId > 0) {
            $adStmt = $db->prepare("SELECT userId FROM ads WHERE id = ? AND status = 'active'");
            $adStmt->execute([$adId]);
            $sellerId = $adStmt->fetchColumn();
            if (!$sellerId) jsonError('الإعلان غير موجود');
            if ($sellerId == $me) jsonError('لا يمكنك مراسلة نفسك');

            // فحص ما إذا كان الـ thread موجوداً مسبقاً
            $existStmt = $db->prepare("SELECT id FROM chat_threads WHERE adId = ? AND buyerId = ?");
            $existStmt->execute([$adId, $me]);
            $existing = $existStmt->fetchColumn();

            if ($existing) {
                $threadId = (int)$existing;
            } else {
                $db->prepare("INSERT INTO chat_threads (adId, buyerId, sellerId) VALUES (?, ?, ?)")
                   ->execute([$adId, $me, $sellerId]);
                $threadId = (int)$db->lastInsertId();
            }
        }

        if ($threadId === 0) jsonError('بيانات ناقصة');

        // التحقق من العضوية في الـ thread
        $check = $db->prepare("SELECT buyerId, sellerId FROM chat_threads WHERE id = ?");
        $check->execute([$threadId]);
        $thr = $check->fetch();
        if (!$thr || ($thr['buyerId'] != $me && $thr['sellerId'] != $me)) {
            jsonError('غير مصرح', 403);
        }

        $receiverId = ($thr['buyerId'] == $me) ? $thr['sellerId'] : $thr['buyerId'];

        // إدراج الرسالة
        $db->prepare("INSERT INTO messages (threadId, senderId, text, type, attachment)
                      VALUES (?, ?, ?, ?, ?)")
           ->execute([$threadId, $me, $text ?: '', $type, $attachmentPath]);
        $msgId = (int)$db->lastInsertId();

        // تحديث الـ thread
        $unreadField = ($thr['buyerId'] == $receiverId) ? 'buyerUnread' : 'sellerUnread';
        $db->prepare("UPDATE chat_threads SET lastMessageId = ?, lastMessageAt = NOW(), $unreadField = $unreadField + 1 WHERE id = ?")
           ->execute([$msgId, $threadId]);

        // إشعار للطرف الآخر
        $senderName = $_SESSION['user_name'] ?? 'مستخدم';
        $previewText = $type === 'image' ? '📷 أرسل لك صورة' : ($type === 'file' ? '📎 أرسل لك ملف' : mb_strimwidth($text, 0, 50, '...'));
        $db->prepare("INSERT INTO notifications (userId, title, content, type, link)
                      VALUES (?, ?, ?, 'message', ?)")
           ->execute([$receiverId, "💬 رسالة جديدة من $senderName", $previewText, "messages.php?thread=$threadId"]);

        jsonSuccess([
            'threadId' => $threadId,
            'messageId' => $msgId,
            'date' => date('H:i'),
            'attachment' => $attachmentPath ? imageUrl($attachmentPath) : null
        ], 'تم الإرسال');
    }

    // ---- مؤشر الكتابة (typing) ----
    if ($action === 'typing') {
        $threadId = (int)($input['thread_id'] ?? 0);
        $check = $db->prepare("SELECT 1 FROM chat_threads WHERE id = ? AND (buyerId = ? OR sellerId = ?)");
        $check->execute([$threadId, $me, $me]);
        if (!$check->fetchColumn()) jsonError('غير مصرح', 403);

        $db->prepare("INSERT INTO typing_status (threadId, userId) VALUES (?, ?)
                      ON DUPLICATE KEY UPDATE updatedAt = NOW()")
           ->execute([$threadId, $me]);
        jsonSuccess([]);
    }

    // ---- حذف رسالة ----
    if ($action === 'delete_message') {
        requireCsrf();
        $msgId = (int)($input['message_id'] ?? 0);
        $check = $db->prepare("SELECT senderId, threadId FROM messages WHERE id = ?");
        $check->execute([$msgId]);
        $msg = $check->fetch();
        if (!$msg || $msg['senderId'] != $me) jsonError('لا يمكنك حذف رسالة لست مرسلها', 403);

        $db->prepare("UPDATE messages SET isDeleted = 1, text = '[رسالة محذوفة]', attachment = NULL WHERE id = ?")
           ->execute([$msgId]);
        jsonSuccess(['messageId' => $msgId], 'تم حذف الرسالة');
    }

    // ---- حذف محادثة كاملة (من جانبي فقط) ----
    if ($action === 'delete_thread') {
        requireCsrf();
        $threadId = (int)($input['thread_id'] ?? 0);
        $check = $db->prepare("SELECT buyerId, sellerId, deletedBy FROM chat_threads WHERE id = ?");
        $check->execute([$threadId]);
        $thr = $check->fetch();
        if (!$thr || ($thr['buyerId'] != $me && $thr['sellerId'] != $me)) jsonError('غير مصرح', 403);

        $deletedBy = json_decode($thr['deletedBy'] ?? '[]', true) ?: [];
        if (!in_array($me, $deletedBy)) $deletedBy[] = $me;
        $db->prepare("UPDATE chat_threads SET deletedBy = ? WHERE id = ?")
           ->execute([json_encode($deletedBy), $threadId]);
        jsonSuccess([], 'تم حذف المحادثة من قائمتك');
    }
}

jsonError('طلب غير صالح');
