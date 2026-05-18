<?php
/**
 * csrf.php - إصدار CSRF Token للواجهة الأمامية
 */
require_once __DIR__ . '/config.php';
apiHeaders();

jsonSuccess(['token' => csrfToken()]);
