<?php
/**
 * backend/config.php - مساعدات API
 * يستورد الإعدادات الرئيسية ويوفر دوال JSON
 */
require_once __DIR__ . '/../config.php';

function apiHeaders() {
    header('Content-Type: application/json; charset=utf-8');
    // CORS مقيّد - فقط نفس الـ origin (آمن للإنتاج)
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $allowedOrigins = [APP_URL, 'http://localhost', 'http://localhost:8000'];
    if (in_array($origin, $allowedOrigins, true)) {
        header("Access-Control-Allow-Origin: $origin");
    }
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-Token');
    header('Access-Control-Allow-Credentials: true');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: same-origin');

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

function jsonSuccess($data = [], $message = 'تمت العملية بنجاح') {
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data'    => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function jsonError($message = 'حدث خطأ', $code = 400, $extra = []) {
    http_response_code($code);
    echo json_encode(array_merge([
        'success' => false,
        'message' => $message,
        'data'    => null
    ], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        jsonError('يجب تسجيل الدخول أولاً', 401);
    }
}

function requireAdmin($db) {
    requireAuth();
    $stmt = $db->prepare("SELECT role, isBanned FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if (!$user || $user['isBanned'] || $user['role'] !== 'admin') {
        jsonError('غير مصرح لك بهذا الإجراء', 403);
    }
}

/**
 * يقرأ JSON body أو POST data ويعيدها كمصفوفة
 */
function getInput() {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }
    return $_POST;
}

/**
 * يجلب باراميتر بأمان من الطلب (GET، POST، أو JSON)
 */
function getParam($key, $default = null) {
    if (isset($_GET[$key])) return $_GET[$key];
    $input = getInput();
    return $input[$key] ?? $default;
}

/**
 * كائن المستخدم المصادق عليه
 */
function authUserId() {
    return $_SESSION['user_id'] ?? null;
}
