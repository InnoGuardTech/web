<?php
require_once __DIR__ . '/config.php';
apiHeaders();
if ($_SERVER['REQUEST_METHOD'] === 'GET') jsonSuccess(getCities());
jsonError('طلب غير صالح');
