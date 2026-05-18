<?php
/**
 * backend/router.php - موجه API الرئيسي v2.0
 */
require_once __DIR__ . '/config.php';
apiHeaders();

$route = $_GET['route'] ?? '';

// خريطة المسارات
$routes = [
    'auth'          => 'auth.php',
    'ads'           => 'ads.php',
    'cities'        => 'cities.php',
    'categories'    => 'categories.php',
    'admin'         => 'admin.php',
    'chat'          => 'chat.php',
    'notifications' => 'notifications.php',
    'user'          => 'user.php',
    'reports'       => 'reports.php',
    'commission'    => 'commission.php',
    'presence'      => 'presence.php',
    'otp'           => 'otp.php',
    'realtime'      => 'realtime.php',  // Server-Sent Events
    'upload'        => 'upload_api.php',
    'csrf'          => 'csrf.php',
];

if (isset($routes[$route])) {
    $file = __DIR__ . '/' . $routes[$route];
    if (file_exists($file)) {
        require $file;
        exit;
    }
}

jsonError('مسار API غير موجود', 404);
