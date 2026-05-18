<?php
require_once __DIR__ . '/../config.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: index.php'); exit;
}
define('PAGE_TITLE', 'لوحة التحكم - ' . SITE_NAME);
include __DIR__ . '/includes/header.php';
?>
<style>
.admin-layout { display: grid; grid-template-columns: 240px 1fr; gap: 1.25rem; }
@media (max-width: 768px) { .admin-layout { grid-template-columns: 1fr; } }
.admin-sidebar { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 0.5rem; height: fit-content; }
.admin-sidebar button { display: block; width: 100%; text-align: right; background: none; border: none; padding: 0.7rem 1rem; border-radius: var(--radius-md); color: var(--text-main); font-weight: 700; cursor: pointer; transition: var(--transition); font-family: inherit; font-size: 0.92rem; margin-bottom: 4px; }
.admin-sidebar button:hover { background: var(--hover-bg); }
.admin-sidebar button.active { background: var(--primary); color: white; }

.stats-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 1rem; }
.stat-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 1.25rem; box-shadow: var(--shadow-xs); }
.stat-card .num { font-size: 2rem; font-weight: 900; color: var(--primary); margin-bottom: 4px; }
.stat-card .label { font-size: 0.82rem; color: var(--text-muted); font-weight: 700; }
.stat-card .icon { font-size: 1.5rem; opacity: 0.4; float: left; }

.admin-table { width: 100%; border-collapse: collapse; }
.admin-table th, .admin-table td { padding: 0.7rem 0.85rem; text-align: right; border-bottom: 1px solid var(--border-color); font-size: 0.85rem; }
.admin-table th { background: var(--bg-color); font-weight: 800; color: var(--primary); }
.admin-table tr:hover { background: var(--hover-bg); }
.admin-table-wrap { overflow-x: auto; background: var(--card-bg); border: 1px solid var(--border-color); border-radius: var(--radius-lg); }
</style>

<div class="container animate-fade-in">
    <h2 style="margin:0 0 1.5rem; color:var(--primary); font-weight:900;">🛡️ لوحة تحكم المدير</h2>

    <div class="admin-layout">
        <nav class="admin-sidebar">
            <button class="active" onclick="switchView('dashboard', this)">📊 لوحة القيادة</button>
            <button onclick="switchView('users', this)">👥 المستخدمون</button>
            <button onclick="switchView('ads', this)">📋 الإعلانات</button>
            <button onclick="switchView('reports', this)">🚩 البلاغات</button>
            <button onclick="switchView('commissions', this)">💰 التحويلات</button>
        </nav>

        <div id="admin-content">
            <div style="text-align:center; padding:3rem;">جاري التحميل...</div>
        </div>
    </div>
</div>

<script src="assets/js/app.js"></script>
<script>
let currentView = 'dashboard';

async function switchView(view, btn) {
    currentView = view;
    document.querySelectorAll('.admin-sidebar button').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');
    document.getElementById('admin-content').innerHTML = '<div style="text-align:center; padding:3rem;">جاري التحميل...</div>';

    if (view === 'dashboard') return loadDashboard();
    if (view === 'users') return loadUsers();
    if (view === 'ads') return loadAdsList();
    if (view === 'reports') return loadReports();
    if (view === 'commissions') return loadCommissions();
}

async function loadDashboard() {
    try {
        const r = await apiRequest('admin&action=stats');
        const s = r.data;
        document.getElementById('admin-content').innerHTML = `
            <div class="stats-grid">
                <div class="stat-card"><span class="icon">👥</span><div class="num">${s.users}</div><div class="label">المستخدمون</div></div>
                <div class="stat-card"><span class="icon">📋</span><div class="num">${s.ads}</div><div class="label">إجمالي الإعلانات</div></div>
                <div class="stat-card"><span class="icon">🟢</span><div class="num">${s.activeAds}</div><div class="label">الإعلانات النشطة</div></div>
                <div class="stat-card"><span class="icon">✓</span><div class="num">${s.soldAds}</div><div class="label">تم البيع</div></div>
                <div class="stat-card"><span class="icon">🚩</span><div class="num" style="color:var(--danger);">${s.pendingReports}</div><div class="label">بلاغات قيد المراجعة</div></div>
                <div class="stat-card"><span class="icon">💰</span><div class="num">${formatNumber(s.commissions)} ر.ي</div><div class="label">إجمالي العمولات</div></div>
                <div class="stat-card"><span class="icon">⏳</span><div class="num" style="color:var(--warning);">${s.pendingCommissions}</div><div class="label">تحويلات قيد المراجعة</div></div>
                <div class="stat-card"><span class="icon">💬</span><div class="num">${formatNumber(s.totalMessages)}</div><div class="label">الرسائل المرسلة</div></div>
                <div class="stat-card"><span class="icon">🆕</span><div class="num" style="color:var(--success);">${s.newUsersToday}</div><div class="label">مستخدمون جدد اليوم</div></div>
                <div class="stat-card"><span class="icon">📣</span><div class="num" style="color:var(--secondary);">${s.newAdsToday}</div><div class="label">إعلانات جديدة اليوم</div></div>
            </div>
        `;
    } catch(e) {}
}

async function loadUsers() {
    try {
        const r = await apiRequest('admin&action=users');
        const u = r.data.users;
        document.getElementById('admin-content').innerHTML = `
            <h3 style="margin-top:0;">👥 إدارة المستخدمين (${r.data.total})</h3>
            <div class="admin-table-wrap"><table class="admin-table">
                <tr><th>#</th><th>الاسم</th><th>الجوال</th><th>الدور</th><th>الحالة</th><th>التقييم</th><th>الإجراءات</th></tr>
                ${u.map(x => `
                    <tr>
                        <td>${x.id}</td>
                        <td>${escapeHtml(x.name)} ${x.isPhoneVerified ? '<span class="badge-verified">✓</span>' : ''}</td>
                        <td style="direction:ltr;">${escapeHtml(x.phone)}</td>
                        <td><span class="status-badge status-${x.role === 'admin' ? 'pending' : 'active'}">${x.role}</span></td>
                        <td>${x.isBanned ? '<span class="status-badge status-archived">محظور</span>' : '<span class="status-badge status-active">نشط</span>'}</td>
                        <td>⭐ ${parseFloat(x.rating).toFixed(1)}</td>
                        <td>
                            ${x.isBanned ? `<button class="btn-outline btn-sm" onclick="toggleBan(${x.id}, 0)">رفع الحظر</button>` : `<button class="btn-outline btn-sm" style="border-color:var(--danger); color:var(--danger);" onclick="toggleBan(${x.id}, 1)">حظر</button>`}
                        </td>
                    </tr>
                `).join('')}
            </table></div>
        `;
    } catch(e) {}
}

async function loadAdsList() {
    try {
        const r = await apiRequest('admin&action=ads');
        const ads = r.data.ads;
        document.getElementById('admin-content').innerHTML = `
            <h3 style="margin-top:0;">📋 جميع الإعلانات (${r.data.total})</h3>
            <div class="admin-table-wrap"><table class="admin-table">
                <tr><th>#</th><th>العنوان</th><th>الفئة</th><th>المدينة</th><th>السعر</th><th>الناشر</th><th>المشاهدات</th><th>الإجراءات</th></tr>
                ${ads.map(a => `
                    <tr>
                        <td>${a.id}</td>
                        <td><a href="ad.php?id=${a.id}" target="_blank" style="color:var(--primary);">${escapeHtml(a.title)}</a></td>
                        <td>${a.category}</td>
                        <td>${a.city}</td>
                        <td>${a.price}</td>
                        <td>${escapeHtml(a.authorName)}</td>
                        <td>${a.views}</td>
                        <td>
                            <button class="btn-outline btn-sm" onclick="adminTogglePin(${a.id})">📌 تثبيت</button>
                            <button class="btn-outline btn-sm" style="border-color:var(--danger); color:var(--danger);" onclick="adminDeleteAd(${a.id})">🗑️</button>
                        </td>
                    </tr>
                `).join('')}
            </table></div>
        `;
    } catch(e) {}
}

async function loadReports() {
    try {
        const r = await apiRequest('admin&action=reports');
        const reports = r.data;
        document.getElementById('admin-content').innerHTML = `
            <h3 style="margin-top:0;">🚩 البلاغات (${reports.length})</h3>
            <div class="admin-table-wrap"><table class="admin-table">
                <tr><th>#</th><th>الإعلان</th><th>المُبلِّغ</th><th>السبب</th><th>التفاصيل</th><th>الحالة</th><th>الإجراءات</th></tr>
                ${reports.map(r => `
                    <tr>
                        <td>${r.id}</td>
                        <td>${r.adId ? `<a href="ad.php?id=${r.adId}" target="_blank">${escapeHtml(r.adTitle || '#'+r.adId)}</a>` : '—'}</td>
                        <td>${escapeHtml(r.reporterName || '—')}</td>
                        <td>${escapeHtml(r.reason)}</td>
                        <td>${escapeHtml(r.details || '—')}</td>
                        <td><span class="status-badge status-${r.status === 'pending' ? 'pending' : 'sold'}">${r.status}</span></td>
                        <td>
                            ${r.status === 'pending' ? `
                                <button class="btn-outline btn-sm" onclick="resolveReport(${r.id}, 'resolved')">✓ حل</button>
                                <button class="btn-outline btn-sm" onclick="resolveReport(${r.id}, 'dismissed')">رفض</button>
                                ${r.adId ? `<button class="btn-outline btn-sm" style="border-color:var(--danger); color:var(--danger);" onclick="adminDeleteAd(${r.adId})">🗑️ حذف الإعلان</button>` : ''}
                            ` : '—'}
                        </td>
                    </tr>
                `).join('') || '<tr><td colspan="7" style="text-align:center; padding:2rem;">لا توجد بلاغات</td></tr>'}
            </table></div>
        `;
    } catch(e) {}
}

async function loadCommissions() {
    try {
        const r = await apiRequest('admin&action=commissions');
        const list = r.data;
        document.getElementById('admin-content').innerHTML = `
            <h3 style="margin-top:0;">💰 تحويلات العمولة (${list.length})</h3>
            <div class="admin-table-wrap"><table class="admin-table">
                <tr><th>#</th><th>المستخدم</th><th>المبلغ</th><th>البنك</th><th>التاريخ</th><th>السند</th><th>الحالة</th><th>الإجراءات</th></tr>
                ${list.map(c => `
                    <tr>
                        <td>${c.id}</td>
                        <td>${escapeHtml(c.userName)} <small style="color:var(--text-muted); direction:ltr; display:block;">${escapeHtml(c.userPhone)}</small></td>
                        <td><strong>${c.amount}</strong></td>
                        <td>${escapeHtml(c.bankName)}</td>
                        <td>${escapeHtml(c.transferDate || c.date)}</td>
                        <td>${c.proofImage ? `<a href="${c.proofImage}" target="_blank" class="btn-outline btn-sm">👁️ سند</a>` : '—'}</td>
                        <td><span class="status-badge status-${c.status === 'pending' ? 'pending' : (c.status === 'approved' ? 'active' : 'sold')}">${c.status}</span></td>
                        <td>
                            ${c.status === 'pending' ? `
                                <button class="btn-outline btn-sm" onclick="approveCommission(${c.id})">✓ قبول</button>
                                <button class="btn-outline btn-sm" style="border-color:var(--danger); color:var(--danger);" onclick="rejectCommission(${c.id})">✗ رفض</button>
                            ` : '—'}
                        </td>
                    </tr>
                `).join('') || '<tr><td colspan="8" style="text-align:center; padding:2rem;">لا توجد تحويلات</td></tr>'}
            </table></div>
        `;
    } catch(e) {}
}

async function toggleBan(userId, ban) {
    const action = ban ? 'ban_user' : 'unban_user';
    if (ban && !await confirmModal('سيتم حظر هذا المستخدم.', 'حظر')) return;
    try {
        await apiRequest('admin&action=' + action, 'POST', { user_id: userId });
        showToast(ban ? 'تم الحظر' : 'تم رفع الحظر', 'success');
        loadUsers();
    } catch(e) {}
}

async function adminDeleteAd(id) {
    if (!await confirmModal('سيتم حذف الإعلان نهائياً.', 'حذف')) return;
    try {
        await apiRequest('admin&action=delete_ad', 'POST', { ad_id: id });
        showToast('تم الحذف', 'success');
        switchView(currentView);
    } catch(e) {}
}

async function adminTogglePin(id) {
    try {
        await apiRequest('admin&action=toggle_pin', 'POST', { ad_id: id });
        showToast('تم التبديل', 'success');
        loadAdsList();
    } catch(e) {}
}

async function resolveReport(id, status) {
    try {
        await apiRequest('admin&action=resolve_report', 'POST', { report_id: id, status });
        showToast('تم', 'success');
        loadReports();
    } catch(e) {}
}

async function approveCommission(id) {
    try {
        await apiRequest('admin&action=approve_commission', 'POST', { id });
        showToast('تم القبول', 'success');
        loadCommissions();
    } catch(e) {}
}

async function rejectCommission(id) {
    if (!await confirmModal('سيتم رفض التحويل', 'رفض')) return;
    try {
        await apiRequest('admin&action=reject_commission', 'POST', { id });
        showToast('تم الرفض', 'success');
        loadCommissions();
    } catch(e) {}
}

document.addEventListener('DOMContentLoaded', () => loadDashboard());
</script>
</body></html>
