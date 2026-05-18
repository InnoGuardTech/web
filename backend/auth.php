<?php
require_once __DIR__ . '/config.php';
apiHeaders();

$db = getDBConnection();
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$action = $_GET['action'] ?? $input['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'login') {
        $phone = sanitize($input['phone'] ?? '');
        $password = trim($input['password'] ?? '');
        
        if (empty($phone) || empty($password)) {
            jsonError('الرجاء إدخال رقم الجوال وكلمة المرور');
        }
        
        $stmt = $db->prepare("SELECT * FROM users WHERE phone = ?");
        $stmt->execute([$phone]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            if ($user['isBanned']) {
                jsonError('⚠️ عذراً، هذا الحساب محظور حالياً من قبل الإدارة لمخالفته الشروط.', 403);
            }
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            
            jsonSuccess([
                'id' => $user['id'],
                'name' => $user['name'],
                'role' => $user['role']
            ], 'تم تسجيل الدخول بنجاح');
        } else {
            jsonError('رقم الجوال أو كلمة المرور غير صحيحة', 401);
        }
    } elseif ($action === 'register') {
        $name = sanitize($input['name'] ?? '');
        $phone = sanitize($input['phone'] ?? '');
        $password = trim($input['password'] ?? '');
        
        if (empty($name) || empty($phone) || empty($password)) {
            jsonError('الرجاء ملء جميع الحقول المطلوبة');
        }
        
        if (strlen($phone) < 9) {
            jsonError('رقم الجوال يجب ألا يقل عن 9 أرقام');
        }
        
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE phone = ?");
        $stmt->execute([$phone]);
        if ($stmt->fetchColumn() > 0) {
            jsonError('رقم الجوال هذا مسجل مسبقاً، يرجى تسجيل الدخول مباشرة');
        }
        
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $joinedDate = date('Y-m');
        
        $stmt = $db->prepare("INSERT INTO users (name, phone, password, rating, joinedDate, role, isBanned) VALUES (?, ?, ?, 5.0, ?, 'seller', 0)");
        $stmt->execute([$name, $phone, $hashedPassword, $joinedDate]);
        
        jsonSuccess([], 'تم إنشاء حسابك الموثق بنجاح! يمكنك الآن تسجيل الدخول.');
    } elseif ($action === 'quick_switch') {
        // للتبديل السريع في وضع التطوير
        $targetId = intval($input['target_id'] ?? 0);
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$targetId]);
        $user = $stmt->fetch();
        
        if ($user) {
            if ($user['isBanned']) {
                jsonError('⚠️ عذراً، هذا الحساب محظور حالياً ولا يمكن التبديل إليه.', 403);
            }
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            jsonSuccess(['role' => $user['role']], 'تم التبديل بنجاح');
        } else {
            jsonError('الحساب غير موجود');
        }
    } elseif ($action === 'logout') {
        session_destroy();
        jsonSuccess([], 'تم تسجيل الخروج');
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'demo_users') {
        // جلب المستخدمين للتجربة
        $demoUsers = $db->query("SELECT id, name, phone, role, isBanned FROM users")->fetchAll();
        jsonSuccess($demoUsers);
    } elseif ($action === 'me') {
        if (isset($_SESSION['user_id'])) {
            jsonSuccess([
                'id' => $_SESSION['user_id'],
                'name' => $_SESSION['user_name'],
                'role' => $_SESSION['user_role']
            ]);
        } else {
            jsonError('غير مسجل الدخول', 401);
        }
    }
}

jsonError('طلب غير صالح');
