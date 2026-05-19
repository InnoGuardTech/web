<?php
define('PAGE_TITLE', 'لا يوجد اتصال — حراج اليمن');
?><!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لا يوجد اتصال بالإنترنت</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div style="min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:24px;">
        <div style="font-size:80px;margin-bottom:24px;">📡</div>
        <h1>لا يوجد اتصال بالإنترنت</h1>
        <p style="color:var(--muted);max-width:420px;margin:16px 0 24px;line-height:1.8;">
            يبدو أنك غير متصل بالإنترنت حالياً. تحقق من اتصالك وأعد المحاولة.
        </p>
        <button class="btn btn-lg" onclick="location.reload()">🔄 إعادة المحاولة</button>
    </div>
</body>
</html>
