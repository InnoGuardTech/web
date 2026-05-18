<?php
require_once __DIR__ . '/../config.php';
if (!isset($_SESSION['user_id'])) { header('Location: auth.php'); exit; }
define('PAGE_TITLE', 'الإشعارات - ' . SITE_NAME);
include __DIR__ . '/includes/header.php';
?>
<style>
.notif-item { display: flex; gap: 0.85rem; padding: 1rem; border-bottom: 1px solid var(--border-color); cursor: pointer; transition: var(--transition); }
.notif-item:hover { background: var(--hover-bg); }
.notif-item.unread { background: var(--primary-light); }
.notif-icon { width: 42px; height: 42px; border-radius: 50%; background: var(--primary-light); display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0; }
.notif-content { flex: 1; min-width: 0; }
.notif-title { font-weight: 800; margin-bottom: 4px; }
.notif-text { font-size: 0.88rem; color: var(--text-muted); }
.notif-time { font-size: 0.72rem; color: var(--text-light); margin-top: 4px; }
</style>

<div class="container animate-fade-in" style="max-width:780px;">
    <div class="flex items-center justify-between" style="margin-bottom:1rem; flex-wrap:wrap; gap:0.5rem;">
        <h2 style="margin:0; color:var(--primary); font-weight:900;">🔔 الإشعارات</h2>
        <div style="display:flex; gap:0.4rem;">
            <button class="btn-outline btn-sm" onclick="markAllRead()">✓ تعليم الكل كمقروء</button>
            <button class="btn-outline btn-sm" onclick="clearAll()" style="color:var(--danger); border-color:var(--danger);">🗑️ مسح المقروءة</button>
        </div>
    </div>
    <div class="premium-card" style="padding:0; overflow:hidden;" id="list">
        <div style="text-align:center; padding:3rem; color:var(--text-muted);">جاري التحميل...</div>
    </div>
</div>

<script src="assets/js/app.js"></script>
<script>
async function load() {
    try {
        const r = await apiRequest('notifications&action=list');
        const list = document.getElementById('list');
        if (!r.data.length) {
            list.innerHTML = '<div style="text-align:center; padding:4rem; color:var(--text-muted);"><div style="font-size:3rem; opacity:0.3;">🔕</div><h3>لا توجد إشعارات</h3></div>';
            return;
        }
        list.innerHTML = r.data.map(n => `
            <div class="notif-item ${!n.isRead ? 'unread' : ''}" onclick="${n.link ? `markRead(${n.id}, '${n.link}')` : `markRead(${n.id})`}">
                <div class="notif-icon">${n.icon}</div>
                <div class="notif-content">
                    <div class="notif-title">${escapeHtml(n.title)}</div>
                    <div class="notif-text">${escapeHtml(n.content)}</div>
                    <div class="notif-time">${n.date}</div>
                </div>
                <button onclick="event.stopPropagation(); delNotif(${n.id})" style="background:none; border:none; color:var(--danger); cursor:pointer; font-size:1rem;">🗑️</button>
            </div>
        `).join('');
    } catch(e) {}
}

async function markRead(id, link) {
    try { await apiRequest('notifications&action=mark_read', 'POST', { id }); } catch(e) {}
    if (link) window.location.href = link;
    else load();
}

async function markAllRead() {
    try {
        await apiRequest('notifications&action=mark_all_read', 'POST', {});
        showToast('تم تعليم الكل كمقروء', 'success');
        load();
    } catch(e) {}
}

async function delNotif(id) {
    try {
        await apiRequest('notifications&action=delete', 'POST', { id });
        load();
    } catch(e) {}
}

async function clearAll() {
    if (!await confirmModal('سيتم حذف جميع الإشعارات المقروءة. هل أنت متأكد؟', 'مسح')) return;
    try {
        await apiRequest('notifications&action=clear_all', 'POST', {});
        showToast('تم المسح', 'success');
        load();
    } catch(e) {}
}

document.addEventListener('DOMContentLoaded', load);
</script>
</body></html>
