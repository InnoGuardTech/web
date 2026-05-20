<?php
// Compatibility endpoint for real-time chat requests.
$_GET['action'] = $_GET['action'] ?? 'sse';
require __DIR__ . '/chat.php';
