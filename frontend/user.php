<?php
require_once __DIR__ . '/../backend/config.php';
if (function_exists('secureSession')) { secureSession(); } else { session_start(); }
$uid = (int)($_GET['id'] ?? 0);
if (!$uid) { header('Location: index.php'); exit; }
$pdo = getDBConnection();
$st = $pdo->prepare("SELECT id, name, isPhoneVerified AS phone_verified, rating, bio, createdAt AS created_at, phone, lastSeenAt AS last_seen FROM users WHERE id=:id AND isBanned=0 LIMIT 1");
$st->execute([':id' => $uid]);
$user = $st->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    require __DIR__ . '/includes/header.php';
    echo '<div class="empty-state" style="padding:80px 20px;"><h3>المستخدم غير موجود</h3></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}
$me = isset($_SESSION['user_id']) ? getCurrentUser() : null;
$isOwn = $me && $me['id'] == $uid;
$isOnline = false;
try {
    $p = $pdo->prepare("SELECT status FROM user_presence WHERE userId=:u LIMIT 1");
    $p->execute([':u' => $uid]);
    $isOnline = ($p->fetchColumn() === 'online');
} catch (Throwable $e) {}

define('PAGE_TITLE', $user['name'] . ' | حراج اليمن');
require __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/icons.php';

$active = $sold = 0;
try {
    $active = (int)$pdo->query("SELECT COUNT(*) FROM ads WHERE userId=$uid AND status='active'")->fetchColumn();
    $sold = (int)$pdo->query("SELECT COUNT(*) FROM ads WHERE userId=$uid AND status='sold'")->fetchColumn();
} catch (Throwable $e) {}
$total = $active + $sold;
?>
<div class="surface-card" style="padding:var(--sp-6);margin-bottom:var(--sp-5);">
    <div style="display:flex;align-items:center;gap:var(--sp-5);flex-wrap:wrap;">
        <div class="avatar-circle avatar-lg" style="background:linear-gradient(135deg,var(--brand-500),var(--brand-700));font-size:32px;"><?= mb_substr($user['name'], 0, 1, 'UTF-8') ?></div>
        <div style="flex:1;min-width:200px;">
            <h1 style="font-size:24px;font-weight:800;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                <?= htmlspecialchars($user['name']) ?>
                <?php if ($user['phone_verified']): ?><span style="color:var(--success);display:inline-flex;align-items:center;gap:4px;font-size:13px;font-weight:600;padding:3px 10px;border-radius:20px;background:rgba(16,185,129,.1);"><?= icon('check-circle', ['size'=>14]) ?> موثّق</span><?php endif; ?>
                <?php if ($isOnline): ?><span style="color:var(--success);font-size:12px;display:inline-flex;align-items:center;gap:4px;"><span style="width:8px;height:8px;border-radius:50%;background:var(--success);"></span> متصل</span><?php endif; ?>
            </h1>
            <div style="display:flex;gap:20px;flex-wrap:wrap;margin-top:8px;font-size:13.5px;color:var(--muted);">
                <span><?= icon('star', ['size'=>14]) ?> <?= number_format((float)$user['rating'], 1) ?>/5.0</span>
                <span><?= icon('calendar', ['size'=>14]) ?> عضو منذ <?= date('Y', strtotime($user['created_at'])) ?></span>
                <span><?= icon('list', ['size'=>14]) ?> <?= $total ?> إعلان</span>
            </div>
            <?php if (!empty($user['bio'])): ?><p style="margin-top:10px;color:var(--text-soft);font-size:14px;line-height:1.6;"><?= nl2br(htmlspecialchars($user['bio'])) ?></p><?php endif; ?>
        </div>
        <?php if ($me && !$isOwn): ?>
        <div style="display:flex;gap:8px;flex-direction:column;">
            <a href="https://wa.me/967<?= $user['phone'] ?>" target="_blank" class="btn btn-success btn-sm"><?= icon('whatsapp', ['size'=>16]) ?> واتساب</a>
            <button class="btn btn-primary btn-sm" onclick="startDM()"><?= icon('message', ['size'=>16]) ?> رسالة</button>
        </div>
        <?php elseif ($isOwn): ?>
            <a href="settings.php" class="btn btn-secondary btn-sm"><?= icon('settings', ['size'=>16]) ?> تعديل الملف</a>
        <?php endif; ?>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:12px;margin-top:var(--sp-5);padding-top:var(--sp-5);border-top:1px solid var(--line-soft);">
        <div style="text-align:center;"><div style="font-size:24px;font-weight:800;color:var(--brand-600);"><?= $total ?></div><div style="font-size:12px;color:var(--muted);">إجمالي الإعلانات</div></div>
        <div style="text-align:center;"><div style="font-size:24px;font-weight:800;color:var(--success);"><?= $active ?></div><div style="font-size:12px;color:var(--muted);">نشطة</div></div>
        <div style="text-align:center;"><div style="font-size:24px;font-weight:800;"><?= $sold ?></div><div style="font-size:12px;color:var(--muted);">مباعة</div></div>
        <div style="text-align:center;"><div style="font-size:24px;font-weight:800;color:var(--gold-600);"><?= number_format((float)$user['rating'], 1) ?></div><div style="font-size:12px;color:var(--muted);">التقييم</div></div>
    </div>
</div>

<h2 style="font-size:20px;font-weight:800;margin-bottom:var(--sp-4);">إعلانات <?= htmlspecialchars($user['name']) ?></h2>
<div class="ads-grid" id="userAds"></div>

<script>
const USER_ID = <?= $uid ?>;
async function loadUserAds() {
    const list = document.getElementById('userAds');
    list.innerHTML = skeletonGrid(4);
    const res = await api('user&action=profile&id=' + USER_ID);
    if (!res.success) return list.innerHTML = `<div class="empty-state" style="grid-column:1/-1;">تعذّر التحميل</div>`;
    const ads = res.ads || res.data?.ads || [];
    if (!ads.length) { list.innerHTML = `<div class="empty-state" style="grid-column:1/-1;padding:40px;"><p>لا توجد إعلانات نشطة</p></div>`; return; }
    list.innerHTML = ads.map(renderAdCard).join('');
}
async function startDM() {
    const msg = prompt('اكتب رسالتك:');
    if (!msg) return;
    const res = await api('chat&action=send', { method: 'POST', data: { to_user_id: USER_ID, toUserId: USER_ID, message: msg, body: msg } });
    if (res.success) location.href = 'messages.php?thread=' + (res.threadId || res.data?.threadId || '');
}
loadUserAds();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
