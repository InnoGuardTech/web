<?php
require_once __DIR__ . '/../backend/config.php';
if (function_exists('secureSession')) { secureSession(); } else { session_start(); }
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') { header('Location: index.php'); exit; }
define('PAGE_TITLE', 'لوحة الإدارة | حراج اليمن');
require __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/icons.php';
?>
<div style="margin-bottom:var(--sp-5);">
    <h1 class="section-title">لوحة التحكم</h1>
    <p class="section-subtitle">إدارة المنصة بكفاءة وسهولة</p>
</div>
<div class="admin-layout">
    <nav class="admin-nav">
        <div class="admin-nav-item active" data-view="dashboard"><?= icon('dashboard', ['size'=>18]) ?> لوحة المعلومات</div>
        <div class="admin-nav-item" data-view="users"><?= icon('users', ['size'=>18]) ?> المستخدمون</div>
        <div class="admin-nav-item" data-view="ads"><?= icon('list', ['size'=>18]) ?> الإعلانات</div>
        <div class="admin-nav-item" data-view="reports"><?= icon('flag', ['size'=>18]) ?> البلاغات <span id="reportsBadge" style="float:left;background:var(--danger);color:#fff;font-size:10px;padding:2px 7px;border-radius:10px;display:none;"></span></div>
        <div class="admin-nav-item" data-view="commissions"><?= icon('dollar', ['size'=>18]) ?> العمولات</div>
        <div class="admin-nav-item" data-view="categories"><?= icon('grid', ['size'=>18]) ?> الفئات والمدن</div>
    </nav>
    <main id="adminContent"><div style="display:grid;place-items:center;padding:60px;color:var(--muted);">جارٍ التحميل...</div></main>
</div>
<script>
document.querySelectorAll('.admin-nav-item').forEach(item => {
    item.onclick = () => {
        document.querySelectorAll('.admin-nav-item').forEach(x => x.classList.remove('active'));
        item.classList.add('active');
        loadView(item.dataset.view);
    };
});
async function loadView(view) {
    const main = document.getElementById('adminContent');
    main.innerHTML = `<div class="skeleton" style="height:300px;border-radius:14px;"></div>`;
    switch(view) {
        case 'dashboard': return renderDashboard();
        case 'users': return renderUsers();
        case 'ads': return renderAds();
        case 'reports': return renderReports();
        case 'commissions': return renderCommissions();
        case 'categories': return renderCategories();
    }
}
async function renderDashboard() {
    const res = await api('admin&action=stats');
    if (!res.success) return document.getElementById('adminContent').innerHTML = `<div class="empty-state">تعذّر التحميل</div>`;
    const s = res.stats || res.data || res;
    document.getElementById('reportsBadge').textContent = s.pending_reports || s.pendingReports || 0;
    document.getElementById('reportsBadge').style.display = ((s.pending_reports || s.pendingReports) > 0) ? '' : 'none';
    document.getElementById('adminContent').innerHTML = `
        <div class="stats-grid">
            ${statCard('المستخدمون', s.users || 0, `+${s.new_users_today || s.newUsersToday || 0} اليوم`)}
            ${statCard('الإعلانات النشطة', s.active_ads || s.activeAds || s.ads || 0, `+${s.new_ads_today || s.newAdsToday || 0} اليوم`)}
            ${statCard('الإعلانات المباعة', s.sold_ads || s.soldAds || 0)}
            ${statCard('البلاغات المعلقة', s.pending_reports || s.pendingReports || 0, '', (s.pending_reports || s.pendingReports) > 0 ? 'down' : '')}
            ${statCard('الرسائل', s.messages || 0)}
            ${statCard('العمولات', ((s.commissions || 0)).toLocaleString() + ' ر.ي')}
        </div>
        <div class="surface-card" style="padding:var(--sp-6);margin-top:var(--sp-5);">
            <h3 style="font-size:16px;font-weight:700;margin-bottom:var(--sp-4);">إجراءات سريعة</h3>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;">
                <button class="btn btn-secondary" onclick="document.querySelector('[data-view=reports]').click()">معالجة البلاغات</button>
                <button class="btn btn-secondary" onclick="document.querySelector('[data-view=ads]').click()">إدارة الإعلانات</button>
                <button class="btn btn-secondary" onclick="document.querySelector('[data-view=users]').click()">إدارة المستخدمين</button>
            </div>
        </div>`;
}
function statCard(label, value, trend, trendDir) {
    return `<div class="stat-card"><div class="stat-value">${value}</div><div class="stat-label">${label}</div>${trend ? `<div class="stat-trend ${trendDir||''}">${trend}</div>` : ''}</div>`;
}
async function renderUsers() {
    const res = await api('admin&action=users');
    const users = res.users || res.data?.users || res.data || [];
    document.getElementById('adminContent').innerHTML = `
        <div class="surface-card">
            <div class="card-header"><div class="card-title">المستخدمون (${users.length})</div><input type="search" class="input" id="userSearch" placeholder="بحث..." style="width:240px;height:36px;"></div>
            <div class="table-wrap" style="border:none;border-radius:0;">
                <table class="data-table">
                    <thead><tr><th>الاسم</th><th>الجوال</th><th>الدور</th><th>الإعلانات</th><th>الحالة</th><th>الانضمام</th><th>الإجراءات</th></tr></thead>
                    <tbody id="usersTbody">
                        ${users.map(u => {
                            const isBanned = u.is_banned || u.isBanned;
                            return `<tr data-search="${escapeHtml((u.name||'')+' '+(u.phone||''))}">
                                <td><div style="display:flex;align-items:center;gap:8px;"><div class="avatar-circle" style="width:32px;height:32px;font-size:13px;">${escapeHtml((u.name||'?').substring(0,1))}</div><span>${escapeHtml(u.name)}</span></div></td>
                                <td>${escapeHtml(u.phone || '-')}</td>
                                <td><span class="status-pill ${u.role==='admin'?'status-active':''}">${u.role||'user'}</span></td>
                                <td>${u.ads_count || u.adsCount || 0}</td>
                                <td><span class="status-pill ${isBanned ? 'status-banned' : 'status-active'}">${isBanned ? 'محظور' : 'نشط'}</span></td>
                                <td>${fmtDate(u.created_at || u.createdAt)}</td>
                                <td><button class="btn btn-sm ${isBanned ? 'btn-success' : 'btn-danger'}" onclick="toggleBan(${u.id}, ${isBanned?1:0})">${isBanned ? 'فك الحظر' : 'حظر'}</button></td>
                            </tr>`;
                        }).join('')}
                    </tbody>
                </table>
            </div>
        </div>`;
    document.getElementById('userSearch').oninput = (e) => {
        const q = e.target.value.toLowerCase();
        document.querySelectorAll('#usersTbody tr').forEach(tr => tr.style.display = tr.dataset.search.toLowerCase().includes(q) ? '' : 'none');
    };
}
async function toggleBan(id, banned) {
    const res = await api('admin&action=' + (banned ? 'unban_user' : 'ban_user'), { method: 'POST', data: { user_id: id } });
    toast(res.message, res.success ? 'success' : 'error');
    if (res.success) renderUsers();
}
async function renderAds() {
    const res = await api('admin&action=ads');
    const ads = res.ads || res.data?.ads || res.data || [];
    document.getElementById('adminContent').innerHTML = `
        <div class="surface-card">
            <div class="card-header"><div class="card-title">الإعلانات (${ads.length})</div></div>
            <div class="table-wrap" style="border:none;border-radius:0;">
                <table class="data-table">
                    <thead><tr><th>العنوان</th><th>السعر</th><th>المالك</th><th>الفئة</th><th>الحالة</th><th>المشاهدات</th><th>الإجراءات</th></tr></thead>
                    <tbody>
                        ${ads.map(a => `<tr>
                            <td><a href="ad.php?id=${a.id}" target="_blank" style="font-weight:600;">${escapeHtml((a.title||'').substring(0,40))}</a></td>
                            <td>${fmtPrice(a.price)}</td>
                            <td>${escapeHtml(a.user_name || a.userName || '-')}</td>
                            <td>${escapeHtml(a.category || '-')}</td>
                            <td><span class="status-pill status-${a.status}">${a.status}</span></td>
                            <td>${a.views || 0}</td>
                            <td><button class="btn btn-sm btn-danger" onclick="adminDeleteAd(${a.id})">حذف</button></td>
                        </tr>`).join('')}
                    </tbody>
                </table>
            </div>
        </div>`;
}
async function adminDeleteAd(id) {
    if (confirm('حذف الإعلان؟ سيتم حذفه نهائياً.')) {
        const res = await api('admin&action=delete_ad', { method: 'POST', data: { ad_id: id } });
        toast(res.message, res.success ? 'success' : 'error');
        if (res.success) renderAds();
    }
}
async function renderReports() {
    const res = await api('admin&action=reports');
    const reports = res.reports || res.data?.reports || res.data || [];
    document.getElementById('adminContent').innerHTML = `
        <div class="surface-card">
            <div class="card-header"><div class="card-title">البلاغات (${reports.length})</div></div>
            <div style="padding:var(--sp-4);">
                ${reports.length === 0 ? '<div class="empty-state"><p>لا توجد بلاغات</p></div>' :
                reports.map(r => `<div style="padding:14px;border:1px solid var(--line);border-radius:12px;margin-bottom:10px;">
                    <div style="display:flex;justify-content:space-between;margin-bottom:8px;"><strong>إعلان #${r.ad_id || r.adId}</strong><span class="status-pill status-${r.status||'pending'}">${r.status==='pending'?'معلق':'مغلق'}</span></div>
                    <p style="font-size:14px;color:var(--text-soft);margin-bottom:8px;">${escapeHtml(r.reason || r.body || r.content || '')}</p>
                    <div style="font-size:12px;color:var(--muted);margin-bottom:10px;">المُبلغ: ${escapeHtml(r.reporter_name || r.reporterName || '-')} · ${fmtDate(r.created_at || r.createdAt)}</div>
                    <div style="display:flex;gap:6px;">
                        <a href="ad.php?id=${r.ad_id || r.adId}" target="_blank" class="btn btn-secondary btn-sm">عرض الإعلان</a>
                        ${r.status === 'pending' ? `<button class="btn btn-success btn-sm" onclick="resolveReport(${r.id})">حلّ البلاغ</button>` : ''}
                    </div>
                </div>`).join('')}
            </div>
        </div>`;
}
async function resolveReport(id) {
    const res = await api('admin&action=resolve_report', { method: 'POST', data: { report_id: id } });
    toast(res.message, res.success ? 'success' : 'error');
    if (res.success) renderReports();
}
async function renderCommissions() {
    const res = await api('admin&action=commissions');
    const list = res.commissions || res.data?.commissions || res.data || [];
    document.getElementById('adminContent').innerHTML = `
        <div class="surface-card">
            <div class="card-header"><div class="card-title">العمولات (${list.length})</div></div>
            <div class="table-wrap" style="border:none;border-radius:0;">
                <table class="data-table">
                    <thead><tr><th>المستخدم</th><th>الإعلان</th><th>المبلغ</th><th>الحالة</th><th>التاريخ</th><th>الإجراءات</th></tr></thead>
                    <tbody>
                        ${list.length===0 ? '<tr><td colspan="6" style="text-align:center;color:var(--muted);padding:40px;">لا توجد سجلات</td></tr>' :
                        list.map(c => `<tr>
                            <td>${escapeHtml(c.user_name || c.userName || '-')}</td>
                            <td>#${c.ad_id || c.adId}</td>
                            <td>${fmtPrice(c.amount)}</td>
                            <td><span class="status-pill status-${c.status==='paid'?'active':'pending'}">${c.status==='paid'?'مدفوع':'معلق'}</span></td>
                            <td>${fmtDate(c.created_at || c.createdAt)}</td>
                            <td>${c.status === 'pending' ? `<button class="btn btn-success btn-sm" onclick="approveCommission(${c.id})">تأكيد</button>` : '-'}</td>
                        </tr>`).join('')}
                    </tbody>
                </table>
            </div>
        </div>`;
}
async function approveCommission(id) {
    const res = await api('admin&action=approve_commission', { method: 'POST', data: { id: id } });
    toast(res.message, res.success ? 'success' : 'error');
    if (res.success) renderCommissions();
}
function renderCategories() {
    document.getElementById('adminContent').innerHTML = `
        <div class="surface-card" style="padding:var(--sp-6);">
            <h3 style="font-size:17px;font-weight:700;margin-bottom:var(--sp-4);">الفئات والمدن</h3>
            <p style="color:var(--muted);">يتم تكوين الفئات والمدن من ملف <code>config.php</code>.</p>
            <ul style="margin-top:var(--sp-4);padding-inline-start:20px;line-height:2;color:var(--text-soft);">
                <li>الفئات: سيارات، عقارات، إلكترونيات، أثاث، وظائف، خدمات، حيوانات، أخرى</li>
                <li>المدن: جميع المحافظات اليمنية (22 مدينة)</li>
            </ul>
        </div>`;
}
renderDashboard();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
