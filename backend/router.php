<?php
// Simple router for API requests
// Rewrite rules should direct /api/action to /backend/router.php?route=action

$route = $_GET['route'] ?? '';

switch ($route) {
    case 'auth':
        require __DIR__ . '/auth.php';
        break;
    case 'ads':
        require __DIR__ . '/ads.php';
        break;
    case 'cities':
        require __DIR__ . '/cities.php';
        break;
    case 'categories':
        require __DIR__ . '/categories.php';
        break;
    case 'admin':
        require __DIR__ . '/admin.php';
        break;
    case 'chat':
        require __DIR__ . '/chat.php';
        break;
    case 'notifications':
        require __DIR__ . '/notifications.php';
        break;
    case 'user':
        require __DIR__ . '/user.php';
        break;
    default:
        require __DIR__ . '/config.php';
        apiHeaders();
        jsonError('مسار API غير موجود', 404);
}
