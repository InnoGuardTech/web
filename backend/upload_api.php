<?php
require_once __DIR__ . '/config.php';
apiHeaders();
requireAuth();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    jsonError('Method Not Allowed', 405);
}

requireCsrf();

$type = preg_replace('/[^a-z_]/i', '', $_POST['type'] ?? 'ads');
$allowed = ['ads', 'avatars', 'chat', 'commission'];
if (!in_array($type, $allowed, true)) {
    $type = 'ads';
}

$file = $_FILES['file'] ?? $_FILES['image'] ?? null;
if (!$file) {
    jsonError('لم يتم رفع أي ملف');
}

$upload = uploadImage($file, $type);
if (!$upload['success']) {
    jsonError($upload['message']);
}

jsonSuccess([
    'path' => $upload['path'],
    'url' => imageUrl($upload['path'])
], 'تم الرفع بنجاح');
