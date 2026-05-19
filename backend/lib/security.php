<?php
/**
 * security.php - مكتبة الأمان الموحّدة
 * تشمل: CSRF / Rate Limiting / Sanitize / Password Strength
 */

// ============================================================
// CSRF TOKEN
// ============================================================
if (!function_exists('csrfToken')) {
    function csrfToken() {
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }
}

if (!function_exists('csrfVerify')) {
    function csrfVerify($token) {
        if (empty($_SESSION['_csrf_token']) || empty($token)) return false;
        return hash_equals($_SESSION['_csrf_token'], $token);
    }
}

if (!function_exists('requireCsrf')) {
    function requireCsrf() {
        // قراءة التوكن من Header أو من body
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (empty($token)) {
            $body = json_decode(file_get_contents('php://input'), true);
            $token = $body['_csrf'] ?? $body['csrf_token'] ?? $_POST['_csrf'] ?? $_POST['csrf_token'] ?? '';
        }
        if (!csrfVerify($token)) {
            http_response_code(419);
            echo json_encode([
                'success' => false,
                'message' => 'انتهت صلاحية الجلسة. يرجى إعادة تحميل الصفحة.',
                'code' => 'CSRF_INVALID'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
}

// ============================================================
// RATE LIMITING (file-based)
// ============================================================
if (!function_exists('rateLimit')) {
    function rateLimit($key, $maxAttempts = 5, $windowSeconds = 300) {
        $cacheDir = dirname(__DIR__, 2) . '/cache/ratelimit';
        if (!is_dir($cacheDir)) @mkdir($cacheDir, 0775, true);

        $hash = md5($key);
        $file = "$cacheDir/$hash.json";
        $now = time();
        $data = ['count' => 0, 'first' => $now];

        if (file_exists($file)) {
            $content = @json_decode(file_get_contents($file), true);
            if (is_array($content)) {
                if (($now - $content['first']) < $windowSeconds) {
                    $data = $content;
                } else {
                    // النافذة انتهت، إعادة تعيين
                    $data = ['count' => 0, 'first' => $now];
                }
            }
        }

        $data['count']++;
        @file_put_contents($file, json_encode($data));

        if ($data['count'] > $maxAttempts) {
            $waitSeconds = $windowSeconds - ($now - $data['first']);
            return [
                'allowed' => false,
                'retry_after' => $waitSeconds,
                'attempts' => $data['count']
            ];
        }

        return ['allowed' => true, 'attempts' => $data['count']];
    }
}

if (!function_exists('rateLimitKey')) {
    function rateLimitKey($prefix) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        return $prefix . ':' . $ip;
    }
}

// ============================================================
// PASSWORD STRENGTH
// ============================================================
if (!function_exists('validatePasswordStrength')) {
    function validatePasswordStrength($password) {
        if (strlen($password) < 6) {
            return ['valid' => false, 'message' => 'كلمة المرور يجب ألا تقل عن 6 أحرف'];
        }
        if (strlen($password) > 128) {
            return ['valid' => false, 'message' => 'كلمة المرور طويلة جداً (الحد الأقصى 128 حرف)'];
        }
        // مزيج من الأحرف والأرقام
        if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            // قاعدة لينة: على الأقل حرف ورقم. (يمكن تشديدها لاحقاً)
            // نسمح بالعربية + رقم أيضاً
            if (!preg_match('/[\p{Arabic}A-Za-z]/u', $password) || !preg_match('/[0-9]/', $password)) {
                return ['valid' => false, 'message' => 'كلمة المرور يجب أن تحتوي على حرف ورقم على الأقل'];
            }
        }
        return ['valid' => true];
    }
}

// ============================================================
// PHONE VALIDATION (Yemen)
// ============================================================
if (!function_exists('normalizePhone')) {
    function normalizePhone($phone) {
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        // إزالة كود الدولة +967 أو 00967
        $phone = preg_replace('/^(\+?967|00967)/', '', $phone);
        $phone = ltrim($phone, '0');
        return $phone;
    }
}

if (!function_exists('isValidYemenPhone')) {
    function isValidYemenPhone($phone) {
        $normalized = normalizePhone($phone);
        // أرقام يمنية: 7 أو 1 أو 2 (للأرضي) في بدايتها، طول 9 أو 7
        if (strlen($normalized) < 7 || strlen($normalized) > 10) return false;
        return preg_match('/^[1-9][0-9]{6,9}$/', $normalized) === 1;
    }
}

// ============================================================
// SANITIZE
// ============================================================
if (!function_exists('cleanInput')) {
    function cleanInput($input) {
        if (is_array($input)) {
            return array_map('cleanInput', $input);
        }
        return htmlspecialchars(strip_tags(trim($input ?? '')), ENT_QUOTES, 'UTF-8');
    }
}

// ============================================================
// SECURE SESSION
// ============================================================
if (!function_exists('startSecureSession')) {
    function startSecureSession() {
        if (session_status() !== PHP_SESSION_NONE) return;

        $lifetime = (int) (env('SESSION_LIFETIME', 7200));
        $secure   = (bool) env('SESSION_SECURE_COOKIE', false);

        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        session_name('haraj_session');
        session_start();

        // Regenerate ID periodically to prevent fixation
        if (empty($_SESSION['_regen_at']) || (time() - $_SESSION['_regen_at']) > 1800) {
            session_regenerate_id(true);
            $_SESSION['_regen_at'] = time();
        }
    }
}

// ============================================================
// OTP HELPERS
// ============================================================
if (!function_exists('generateOTP')) {
    function generateOTP($length = 6) {
        $otp = '';
        for ($i = 0; $i < $length; $i++) {
            $otp .= random_int(0, 9);
        }
        return $otp;
    }
}
