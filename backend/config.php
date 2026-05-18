<?php
// backend/config.php - جسر الإعدادات المشتركة
require_once __DIR__ . '/../config.php';

// إعداد ترويسات API (CORS + JSON)
function apiHeaders() {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

// استجابة JSON ناجحة
function jsonSuccess($data = [], $message = 'تمت العملية بنجاح') {
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data'    => $data
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// استجابة JSON بخطأ
function jsonError($message = 'حدث خطأ', $code = 400) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'message' => $message,
        'data'    => null
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// التحقق من تسجيل الدخول (للـ API)
function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        jsonError('يجب تسجيل الدخول أولاً', 401);
    }
}

// التحقق من صلاحية المدير (للـ API)
function requireAdmin($db) {
    requireAuth();
    $stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if (!$user || $user['role'] !== 'admin') {
        jsonError('غير مصرح لك بهذا الإجراء', 403);
    }
}
