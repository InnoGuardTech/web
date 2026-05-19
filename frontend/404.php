<?php
require_once __DIR__ . '/../backend/config.php';
if (function_exists('secureSession')) { secureSession(); } else { session_start(); }
http_response_code(404);
define('PAGE_TITLE', '404 — الصفحة غير موجودة');
define('PAGE_DESC', 'الصفحة التي تبحث عنها غير موجودة.');
require __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/icons.php';
?>

<div style="min-height:60vh;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:var(--sp-8);">
    <div style="font-size:clamp(120px,20vw,200px);font-weight:900;line-height:1;background:linear-gradient(135deg,var(--brand-500),var(--brand-700) 50%,var(--gold-500));-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;color:transparent;margin-bottom:var(--sp-4);">404</div>
    <h1 style="font-size:clamp(22px,3vw,32px);margin-bottom:12px;">عذراً، الصفحة غير موجودة!</h1>
    <p style="color:var(--muted);max-width:480px;font-size:16px;line-height:1.8;margin-bottom:var(--sp-6);">
        يبدو أن الرابط الذي ضغطته خاطئ أو أن الصفحة قد حُذفت. لا تقلق، يمكنك العودة للصفحة الرئيسية بسهولة.
    </p>
    <div style="display:flex;gap:12px;flex-wrap:wrap;justify-content:center;">
        <a href="index.php" class="btn btn-lg"><?= icon('home', ['size'=>20]) ?> العودة للرئيسية</a>
        <a href="javascript:history.back()" class="btn btn-secondary btn-lg"><?= icon('arrow-right', ['size'=>20]) ?> الصفحة السابقة</a>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
