<?php
/**
 * backend/auth_fixed.php - نظام المصادقة المحسّن v2.1
 * ✅ إصلاح كامل لتسجيل الدخول وإنشاء الحسابات
 * ✅ دعم كامل لـ MySQL مع معالجة أخطاء محسّنة
 * ✅ OTP للتحقق من رقم الجوال
 * ✅ نسيان كلمة المرور
 */
require_once __DIR__ . '/config.php';
apiHeaders();

$db = getDBConnection();
$input = getInput();
$action = $_GET['action'] ?? $input['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// ============================================================
// GET endpoints
// ============================================================
if ($method === 'GET') {
    if ($action === 'me') {
        if (!isset($_SESSION['user_id'])) {
            jsonError('غير مسجل الدخول', 401);
        }
        $user = getCurrentUser($db);
        if (!$user) jsonError('الحساب غير صالح', 401);

        $user['avatar_url'] = avatarUrl($user);
        unset($user['password']);
        jsonSuccess($user);
    }
    jsonError('طلب غير صالح');
}

// ============================================================
// POST endpoints
// ============================================================
if ($method !== 'POST') {
    jsonError('Method Not Allowed', 405);
}

// ============ LOGOUT ============
if ($action === 'logout') {
    $_SESSION = [];
    session_destroy();
    jsonSuccess([], 'تم تسجيل الخروج');
}

// التحقق من CSRF لجميع POST endpoints (ما عدا login/register أول مرة)
$requiresCsrf = in_array($action, ['change_password','update_profile','delete_account','verify_otp']);
if ($requiresCsrf) {
    requireCsrf();
}

// ============ LOGIN ============
if ($action === 'login') {
    // Rate Limit
    $rl = rateLimit(rateLimitKey('login'), (int)env('RATE_LIMIT_LOGIN', 5), (int)env('RATE_LIMIT_WINDOW', 300));
    if (!$rl['allowed']) {
        jsonError('عدد محاولات تسجيل الدخول كثير. حاول بعد ' . ceil($rl['retry_after']/60) . ' دقيقة', 429,
                  ['retry_after' => $rl['retry_after']]);
    }

    $phone = normalizePhone($input['phone'] ?? '');
    $password = trim($input['password'] ?? '');

    if (empty($phone) || empty($password)) {
        jsonError('الرجاء إدخال رقم الجوال وكلمة المرور');
    }

    // فحص القائمة السوداء
    $blStmt = $db->prepare("SELECT id FROM blacklist WHERE phone = ? LIMIT 1");
    $blStmt->execute([$phone]);
    if ($blStmt->fetch()) {
        jsonError('هذا الرقم محظور من النظام', 403);
    }

    $stmt = $db->prepare("SELECT id, name, phone, password, role, isBanned, isPhoneVerified, avatar FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        jsonError('رقم الجوال أو كلمة المرور غير صحيحة', 401);
    }

    if ($user['isBanned']) {
        jsonError('⚠️ هذا الحساب محظور حالياً من قبل الإدارة.', 403);
    }

    // نجح تسجيل الدخول - إعادة توليد session id
    session_regenerate_id(true);
    $_SESSION['user_id']   = (int)$user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['_regen_at'] = time();

    // تحديث الحضور
    try {
        $db->prepare("INSERT INTO user_presence (userId, status, lastSeenAt) VALUES (?, 'online', CURRENT_TIMESTAMP) 
                      ON DUPLICATE KEY UPDATE status='online', lastSeenAt=CURRENT_TIMESTAMP")
           ->execute([$user['id']]);
    } catch (Exception $e) {
        // تجاهل الأخطاء في تحديث الحضور
    }

    // تحديث آخر ظهور
    $db->prepare("UPDATE users SET updatedAt = CURRENT_TIMESTAMP WHERE id = ?")->execute([$user['id']]);

    jsonSuccess([
        'id'   => $user['id'],
        'name' => $user['name'],
        'role' => $user['role'],
        'avatar_url' => avatarUrl($user),
        'csrf_token' => csrfToken(),
        'redirect' => $_GET['redirect'] ?? 'index.php'
    ], 'تم تسجيل الدخول بنجاح');
}

// ============ REGISTER ============
if ($action === 'register') {
    $rl = rateLimit(rateLimitKey('register'), (int)env('RATE_LIMIT_REGISTER', 3), (int)env('RATE_LIMIT_WINDOW', 300));
    if (!$rl['allowed']) {
        jsonError('محاولات إنشاء الحساب كثيرة. حاول بعد ' . ceil($rl['retry_after']/60) . ' دقيقة', 429);
    }

    $name     = sanitize($input['name'] ?? '');
    $phone    = normalizePhone($input['phone'] ?? '');
    $email    = sanitize($input['email'] ?? '');
    $password = $input['password'] ?? '';

    if (empty($name) || empty($phone) || empty($password)) {
        jsonError('الرجاء ملء جميع الحقول المطلوبة');
    }
    if (mb_strlen($name) < 3 || mb_strlen($name) > 100) {
        jsonError('الاسم يجب أن يكون بين 3 و 100 حرف');
    }
    if (!isValidYemenPhone($phone)) {
        jsonError('رقم الجوال غير صالح. يجب أن يبدأ بـ 7 ويتكون من 9 أرقام');
    }
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonError('البريد الإلكتروني غير صحيح');
    }

    $pwdCheck = validatePasswordStrength($password);
    if (!$pwdCheck['valid']) jsonError($pwdCheck['message']);

    // فحص القائمة السوداء
    $blStmt = $db->prepare("SELECT id FROM blacklist WHERE phone = ? OR email = ? LIMIT 1");
    $blStmt->execute([$phone, $email ?: '__none__']);
    if ($blStmt->fetch()) jsonError('هذا الرقم/البريد محظور', 403);

    // التحقق من التكرار
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    if ($stmt->fetchColumn() > 0) {
        jsonError('رقم الجوال مسجل مسبقاً، يرجى تسجيل الدخول');
    }
    if (!empty($email)) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) jsonError('البريد الإلكتروني مسجل مسبقاً');
    }

    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $joined = date('Y-m');

    try {
        $stmt = $db->prepare("INSERT INTO users (name, phone, email, password, rating, ratingCount, joinedDate, role, isBanned)
                              VALUES (?, ?, ?, ?, 5.0, 0, ?, 'seller', 0)");
        $stmt->execute([$name, $phone, $email ?: null, $hashed, $joined]);
        $userId = $db->lastInsertId();
    } catch (PDOException $e) {
        jsonError('فشل إنشاء الحساب. يرجى المحاولة لاحقاً', 500);
    }

    // إنشاء OTP لتأكيد رقم الجوال
    $otp = generateOTP(6);
    $expires = date('Y-m-d H:i:s', time() + 600); // 10 دقائق
    $db->prepare("INSERT INTO otp_codes (phone, code, purpose, expiresAt) VALUES (?, ?, 'verify_phone', ?)")
       ->execute([$phone, $otp, $expires]);

    sendOTP($phone, $otp, 'verify');

    // تسجيل الدخول تلقائياً
    session_regenerate_id(true);
    $_SESSION['user_id']   = (int)$userId;
    $_SESSION['user_name'] = $name;
    $_SESSION['user_role'] = 'seller';
    $_SESSION['_regen_at'] = time();

    // إشعار ترحيبي
    try {
        $db->prepare("INSERT INTO notifications (userId, title, content, type)
                      VALUES (?, ?, ?, 'system')")
           ->execute([$userId, 'مرحباً بك في حراج اليمن! 🇾🇪', 'نرحب بك. ابدأ بإضافة أول إعلان لك أو تصفّح المنصة.']);
    } catch (Exception $e) {
        // تجاهل الأخطاء في الإشعارات
    }

    jsonSuccess([
        'id' => $userId,
        'name' => $name,
        'role' => 'seller',
        'otp_sent' => true,
        'csrf_token' => csrfToken(),
        'redirect' => 'index.php'
    ], 'تم إنشاء حسابك بنجاح! تم إرسال رمز التحقق إلى جوالك.');
}

// ============ تأكيد OTP ============
if ($action === 'verify_otp') {
    requireAuth();
    $code = trim($input['code'] ?? '');
    $purpose = $input['purpose'] ?? 'verify_phone';
    if (empty($code)) jsonError('أدخل رمز التحقق');

    $userStmt = $db->prepare("SELECT phone FROM users WHERE id = ?");
    $userStmt->execute([$_SESSION['user_id']]);
    $user = $userStmt->fetch();
    if (!$user) jsonError('المستخدم غير موجود', 404);

    $stmt = $db->prepare("SELECT * FROM otp_codes WHERE phone = ? AND purpose = ? AND isUsed = 0 AND expiresAt > NOW() ORDER BY id DESC LIMIT 1");
    $stmt->execute([$user['phone'], $purpose]);
    $otp = $stmt->fetch();

    if (!$otp) jsonError('انتهت صلاحية الرمز أو غير موجود. اطلب رمزاً جديداً');

    if ($otp['attempts'] >= 5) {
        $db->prepare("UPDATE otp_codes SET isUsed = 1 WHERE id = ?")->execute([$otp['id']]);
        jsonError('تجاوزت الحد الأقصى للمحاولات');
    }

    if ($otp['code'] !== $code) {
        $db->prepare("UPDATE otp_codes SET attempts = attempts + 1 WHERE id = ?")->execute([$otp['id']]);
        jsonError('رمز غير صحيح. محاولاتك المتبقية: ' . (4 - $otp['attempts']));
    }

    $db->prepare("UPDATE otp_codes SET isUsed = 1 WHERE id = ?")->execute([$otp['id']]);
    $db->prepare("UPDATE users SET isPhoneVerified = 1 WHERE id = ?")->execute([$_SESSION['user_id']]);

    jsonSuccess([], 'تم تأكيد رقم جوالك بنجاح ✓');
}

// ============ إعادة إرسال OTP ============
if ($action === 'resend_otp') {
    requireAuth();
    $rl = rateLimit('otp:' . $_SESSION['user_id'], 3, 600);
    if (!$rl['allowed']) jsonError('تجاوزت حد إرسال الرمز. حاول بعد دقائق', 429);

    $userStmt = $db->prepare("SELECT phone FROM users WHERE id = ?");
    $userStmt->execute([$_SESSION['user_id']]);
    $user = $userStmt->fetch();
    if (!$user) jsonError('المستخدم غير موجود', 404);

    $otp = generateOTP(6);
    $expires = date('Y-m-d H:i:s', time() + 600);
    $db->prepare("INSERT INTO otp_codes (phone, code, purpose, expiresAt) VALUES (?, ?, 'verify_phone', ?)")
       ->execute([$user['phone'], $otp, $expires]);

    sendOTP($user['phone'], $otp, 'verify');
    jsonSuccess([], 'تم إرسال رمز جديد إلى جوالك');
}

// ============ نسيت كلمة المرور ============
if ($action === 'forgot_password') {
    $rl = rateLimit(rateLimitKey('forgot'), 3, 600);
    if (!$rl['allowed']) jsonError('طلبات كثيرة. حاول لاحقاً', 429);

    $phone = normalizePhone($input['phone'] ?? '');
    if (empty($phone)) jsonError('أدخل رقم الجوال');

    $stmt = $db->prepare("SELECT id, name FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    $user = $stmt->fetch();

    // نُرجع نجاحاً دائماً لمنع إفشاء وجود الحساب
    if ($user) {
        $otp = generateOTP(6);
        $expires = date('Y-m-d H:i:s', time() + 900); // 15 دقيقة
        $db->prepare("INSERT INTO otp_codes (phone, code, purpose, expiresAt) VALUES (?, ?, 'reset_password', ?)")
           ->execute([$phone, $otp, $expires]);
        sendOTP($phone, $otp, 'reset');
    }

    jsonSuccess([], 'إذا كان الرقم مسجلاً، فستصلك رسالة بالرمز');
}

// ============ إعادة تعيين كلمة المرور ============
if ($action === 'reset_password') {
    $phone = normalizePhone($input['phone'] ?? '');
    $code = trim($input['code'] ?? '');
    $newPassword = $input['new_password'] ?? '';

    if (empty($phone) || empty($code) || empty($newPassword)) {
        jsonError('جميع الحقول مطلوبة');
    }

    $pwdCheck = validatePasswordStrength($newPassword);
    if (!$pwdCheck['valid']) jsonError($pwdCheck['message']);

    $stmt = $db->prepare("SELECT * FROM otp_codes WHERE phone = ? AND purpose = 'reset_password' AND isUsed = 0 AND expiresAt > NOW() ORDER BY id DESC LIMIT 1");
    $stmt->execute([$phone]);
    $otp = $stmt->fetch();

    if (!$otp || $otp['code'] !== $code) jsonError('رمز غير صحيح أو منتهي');

    $db->prepare("UPDATE otp_codes SET isUsed = 1 WHERE id = ?")->execute([$otp['id']]);

    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $db->prepare("UPDATE users SET password = ? WHERE phone = ?")->execute([$newHash, $phone]);

    jsonSuccess([], 'تم تغيير كلمة المرور بنجاح. يمكنك تسجيل الدخول الآن.');
}

// ============ تغيير كلمة المرور (من الحساب) ============
if ($action === 'change_password') {
    requireAuth();
    $currentPwd = $input['current_password'] ?? '';
    $newPwd = $input['new_password'] ?? '';

    if (empty($currentPwd) || empty($newPwd)) jsonError('جميع الحقول مطلوبة');

    $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $u = $stmt->fetch();

    if (!$u || !password_verify($currentPwd, $u['password'])) {
        jsonError('كلمة المرور الحالية غير صحيحة');
    }

    $pwdCheck = validatePasswordStrength($newPwd);
    if (!$pwdCheck['valid']) jsonError($pwdCheck['message']);

    $newHash = password_hash($newPwd, PASSWORD_DEFAULT);
    $db->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$newHash, $_SESSION['user_id']]);

    jsonSuccess([], 'تم تغيير كلمة المرور بنجاح');
}

// ============ تحديث الملف الشخصي ============
if ($action === 'update_profile') {
    requireAuth();
    $name = sanitize($input['name'] ?? '');
    $email = sanitize($input['email'] ?? '');
    $bio = sanitize($input['bio'] ?? '');

    if (empty($name) || mb_strlen($name) < 3) jsonError('الاسم يجب ألا يقل عن 3 أحرف');
    if (mb_strlen($name) > 100) jsonError('الاسم طويل جداً');
    if (mb_strlen($bio) > 500) jsonError('النبذة طويلة جداً (الحد الأقصى 500 حرف)');
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) jsonError('بريد إلكتروني غير صالح');

    if (!empty($email)) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $_SESSION['user_id']]);
        if ($stmt->fetchColumn() > 0) jsonError('البريد مستخدم بالفعل');
    }

    $db->prepare("UPDATE users SET name = ?, email = ?, bio = ? WHERE id = ?")
       ->execute([$name, $email ?: null, $bio ?: null, $_SESSION['user_id']]);

    $_SESSION['user_name'] = $name;
    jsonSuccess(['name'=>$name,'email'=>$email,'bio'=>$bio], 'تم تحديث الملف الشخصي');
}

// ============ تحديث الصورة الشخصية ============
if ($action === 'update_avatar') {
    requireAuth();
    $avatarData = $input['avatar'] ?? '';
    if (empty($avatarData)) jsonError('لم يتم رفع صورة');

    $result = uploadBase64Image($avatarData, 'avatars');
    if (!$result['success']) jsonError($result['message']);

    $db->prepare("UPDATE users SET avatar = ? WHERE id = ?")->execute([$result['url'], $_SESSION['user_id']]);
    jsonSuccess(['avatar_url' => $result['url']], 'تم تحديث الصورة');
}

// ============ حذف الحساب ============
if ($action === 'delete_account') {
    requireAuth();
    $password = $input['password'] ?? '';
    if (empty($password)) jsonError('أدخل كلمة المرور للتأكيد');

    $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        jsonError('كلمة المرور غير صحيحة');
    }

    // حذف البيانات المرتبطة
    $db->prepare("DELETE FROM ads WHERE userId = ?")->execute([$_SESSION['user_id']]);
    $db->prepare("DELETE FROM messages WHERE senderId = ? OR receiverId = ?")->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $db->prepare("DELETE FROM favorites WHERE userId = ?")->execute([$_SESSION['user_id']]);
    $db->prepare("DELETE FROM ratings WHERE fromUserId = ? OR toUserId = ?")->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $db->prepare("DELETE FROM notifications WHERE userId = ?")->execute([$_SESSION['user_id']]);
    $db->prepare("DELETE FROM user_presence WHERE userId = ?")->execute([$_SESSION['user_id']]);

    // حذف الحساب
    $db->prepare("DELETE FROM users WHERE id = ?")->execute([$_SESSION['user_id']]);

    $_SESSION = [];
    session_destroy();

    jsonSuccess([], 'تم حذف الحساب بنجاح');
}

jsonError('إجراء غير معروف', 400);
