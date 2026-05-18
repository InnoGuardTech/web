<?php
require_once __DIR__ . '/config.php';
apiHeaders();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $categories = [
        ['id' => 'all', 'name' => 'الكل', 'icon' => '🔍'],
        ['id' => 'cars', 'name' => 'حراج السيارات', 'icon' => '🚗'],
        ['id' => 'realestate', 'name' => 'عقارات', 'icon' => '🏠'],
        ['id' => 'electronics', 'name' => 'أجهزة وإلكترونيات', 'icon' => '📱'],
        ['id' => 'livestock', 'name' => 'مواشي وحيوانات', 'icon' => '🐏'],
        ['id' => 'furniture', 'name' => 'أثاث ومفروشات', 'icon' => '🪑'],
        ['id' => 'jobs', 'name' => 'وظائف', 'icon' => '💼'],
        ['id' => 'services', 'name' => 'خدمات', 'icon' => '🔧'],
        ['id' => 'other', 'name' => 'أخرى', 'icon' => '📦']
    ];
    jsonSuccess($categories);
}

jsonError('طلب غير صالح');
