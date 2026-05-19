<?php
require_once __DIR__ . '/../backend/config.php';
if (function_exists('secureSession')) { secureSession(); } else { session_start(); }
if (!isset($_SESSION['user_id'])) { header('Location: auth.php'); exit; }
define('PAGE_TITLE', 'الإشعارات | حراج اليمن');
require __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/icons.php';
?>
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:var(--sp-5);flex-wrap:wrap;gap:10px;">
    <div>
        <h1 class="section-title">الإشعارات</h1>
        <p class="section-subtitle">آخر الأنشطة المتعلقة بحسابك</p>
    </div>
    <button class="btn btn-secondary btn-sm" onclick="markAllRead()"><?= icon('check', ['size'=>16]) ?> تعليم الكل كمقروء</button>
</div>
<div id="notifList" class="surface-card" style="padding:8px;">
    <div class="skeleton" style="height:60px;margin:8px;border-radius:8px;"></div>
    <div class="skeleton" style="height:60px;margin:8px;border-radius:8px;"></div>
</div>
<script>
async function loadNotifs() {
    const res = await api('notifications&action=list');
    const list = document.getElementById('notifList');
    const items = res.notifications || res.data?.notifications || res.data || [];
    if (!items.length) { list.innerHTML = `<div class="empty-state" style="padding:50px 20px;"><div style="font-size:50px;opacity:.4;">🔔</div><h3>لا توجد إشعارات</h3></div>`; return; }
    list.innerHTML = items.map(n => {
        const read = n.isRead || n.is_read;
        return `<a href="${n.link || '#'}" style="display:block;padding:14px 16px;border-radius:10px;background:${read==0?'rgba(59,108,246,.05)':'transparent'};margin:4px 0;border-inline-start:3px solid ${read==0?'var(--brand-500)':'transparent'};">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                <strong style="font-size:14px;">${escapeHtml(n.title || '')}</strong>
                <span style="font-size:11px;color:var(--muted);">${fmtDate(n.created_at || n.createdAt)}</span>
            </div>
            <p style="font-size:13px;color:var(--text-soft);margin-top:4px;">${escapeHtml(n.content || n.body || n.message || '')}</p>
        </a>`;
    }).join('');
}
async function markAllRead() {
    const res = await api('notifications&action=mark_all_read', { method: 'POST' });
    if (res.success) { toast('تم وضع علامة مقروء', 'success'); loadNotifs(); }
}
loadNotifs();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
