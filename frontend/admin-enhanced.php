<?php
/**
 * ============================================================
 * حراج اليمن - لوحة الإدارة المحسّنة v4.1
 * ============================================================
 * صفحة إدارة متقدمة مع تحسينات الأداء والأمان
 */

require_once __DIR__ . '/../config.php';

// التحقق من الصلاحيات
$_me = getCurrentUser();
if (!$_me || $_me['role'] !== 'admin') {
    header('Location: auth.php?redirect=admin-enhanced.php');
    exit;
}

// إعدادات الصفحة
define('PAGE_TITLE', 'لوحة الإدارة - حراج اليمن');
define('PAGE_DESC', 'إدارة الإعلانات والمستخدمين والبلاغات والعمولات');
define('PAGE_URL', APP_URL . '/frontend/admin-enhanced.php');
define('PAGE_OG_IMAGE', '');
define('EXTRA_HEAD', '
    <link rel="stylesheet" href="assets/css/improvements.css">
    <style>
        .admin-dashboard {
            display: grid;
            grid-template-columns: 1fr;
            gap: var(--sp-6);
            margin-bottom: var(--sp-6);
        }
        
        @media (min-width: 1024px) {
            .admin-dashboard {
                grid-template-columns: repeat(4, 1fr);
            }
        }
        
        .stat-card {
            background: var(--surface);
            border-radius: var(--r-lg);
            padding: var(--sp-6);
            box-shadow: var(--sh-sm);
            border: 1px solid var(--line);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            box-shadow: var(--sh-md);
            transform: translateY(-2px);
        }
        
        .stat-number {
            font-size: 28px;
            font-weight: 800;
            color: var(--brand-500);
            margin-bottom: var(--sp-2);
        }
        
        .stat-label {
            font-size: 13px;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .admin-tabs {
            display: flex;
            gap: var(--sp-2);
            border-bottom: 1px solid var(--line);
            margin-bottom: var(--sp-4);
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .admin-tabs button {
            padding: var(--sp-3) var(--sp-4);
            border: none;
            background: none;
            color: var(--text-soft);
            cursor: pointer;
            font-weight: 500;
            border-bottom: 2px solid transparent;
            transition: all 0.3s ease;
            white-space: nowrap;
        }
        
        .admin-tabs button.active {
            color: var(--brand-500);
            border-bottom-color: var(--brand-500);
        }
        
        .admin-tabs button:hover {
            color: var(--text);
        }
        
        .admin-content {
            background: var(--surface);
            border-radius: var(--r-lg);
            padding: var(--sp-6);
            box-shadow: var(--sh-sm);
            border: 1px solid var(--line);
        }
        
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        
        .admin-table thead {
            background: var(--bg-soft);
            border-bottom: 2px solid var(--line);
        }
        
        .admin-table th {
            padding: var(--sp-3) var(--sp-4);
            text-align: right;
            font-weight: 600;
            color: var(--text);
        }
        
        .admin-table td {
            padding: var(--sp-3) var(--sp-4);
            border-bottom: 1px solid var(--line);
        }
        
        .admin-table tbody tr:hover {
            background: var(--bg-soft);
        }
        
        .status-badge {
            display: inline-block;
            padding: var(--sp-1) var(--sp-3);
            border-radius: var(--r-full);
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-active {
            background: var(--success-soft);
            color: var(--success);
        }
        
        .status-pending {
            background: var(--warning-soft);
            color: var(--warning);
        }
        
        .status-inactive {
            background: var(--danger-soft);
            color: var(--danger);
        }
        
        .action-buttons {
            display: flex;
            gap: var(--sp-2);
        }
        
        .action-buttons .btn {
            padding: var(--sp-2) var(--sp-3);
            font-size: 12px;
        }
        
        @media (max-width: 768px) {
            .admin-dashboard {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .admin-content {
                padding: var(--sp-4);
            }
            
            .admin-table {
                font-size: 12px;
            }
            
            .admin-table th,
            .admin-table td {
                padding: var(--sp-2) var(--sp-3);
            }
        }
    </style>
');

require_once __DIR__ . '/includes/header.php';
?>

<main class="container" style="margin-top: var(--sp-6); margin-bottom: var(--sp-6);">
    <div style="margin-bottom: var(--sp-6);">
        <h1 style="font-size: 28px; font-weight: 800; margin-bottom: var(--sp-2);">لوحة الإدارة</h1>
        <p style="color: var(--text-soft);">مرحباً بك <?= htmlspecialchars($_me['name']) ?> - إدارة المنصة والمستخدمين</p>
    </div>
    
    <!-- Statistics Dashboard -->
    <div class="admin-dashboard" id="statsContainer">
        <div class="stat-card">
            <div class="stat-number" id="totalAds">-</div>
            <div class="stat-label">إجمالي الإعلانات</div>
        </div>
        <div class="stat-card">
            <div class="stat-number" id="totalUsers">-</div>
            <div class="stat-label">إجمالي المستخدمين</div>
        </div>
        <div class="stat-card">
            <div class="stat-number" id="totalReports">-</div>
            <div class="stat-label">البلاغات المعلقة</div>
        </div>
        <div class="stat-card">
            <div class="stat-number" id="totalCommissions">-</div>
            <div class="stat-label">العمولات المعلقة</div>
        </div>
    </div>
    
    <!-- Admin Tabs -->
    <div class="admin-tabs">
        <button class="active" onclick="switchTab('overview')">نظرة عامة</button>
        <button onclick="switchTab('ads')">الإعلانات</button>
        <button onclick="switchTab('users')">المستخدمون</button>
        <button onclick="switchTab('reports')">البلاغات</button>
        <button onclick="switchTab('commissions')">العمولات</button>
        <button onclick="switchTab('settings')">الإعدادات</button>
    </div>
    
    <!-- Admin Content -->
    <div class="admin-content" id="adminContent">
        <div style="text-align: center; padding: var(--sp-6);">
            <div class="spinner" style="display: inline-block;"></div>
            <p style="margin-top: var(--sp-4); color: var(--muted);">جاري التحميل...</p>
        </div>
    </div>
</main>

<div id="toastContainer" style="position: fixed; bottom: var(--sp-4); right: var(--sp-4); z-index: 9999; display: flex; flex-direction: column; gap: var(--sp-2);"></div>

<script>
const API_BASE = '../backend/router.php';

// ===== Utility Functions =====
function toast(msg, type = 'info', duration = 3500) {
    const c = document.getElementById('toastContainer');
    if (!c) return;
    const t = document.createElement('div');
    t.className = 'toast ' + type;
    const icons = {
        success: '✓',
        error: '✕',
        warning: '⚠',
        info: 'ℹ'
    };
    t.innerHTML = '<span>' + (icons[type] || icons.info) + ' ' + msg + '</span>';
    t.style.cssText = `
        background: ${type === 'success' ? 'var(--success-soft)' : type === 'error' ? 'var(--danger-soft)' : 'var(--info-soft)'};
        color: ${type === 'success' ? 'var(--success)' : type === 'error' ? 'var(--danger)' : 'var(--info)'};
        padding: var(--sp-3) var(--sp-4);
        border-radius: var(--r-md);
        box-shadow: var(--sh-md);
    `;
    c.appendChild(t);
    setTimeout(() => t.remove(), duration);
}

async function api(routeAction, options = {}) {
    const url = API_BASE + (routeAction.includes('?') ? '&' : '?') + 'route=' + routeAction;
    const method = (options.method || 'GET').toUpperCase();
    const opt = {
        method: method,
        credentials: 'include',
        headers: Object.assign({ 'Accept': 'application/json' }, options.headers || {})
    };
    if (method !== 'GET' && options.data) {
        opt.headers['Content-Type'] = 'application/json';
        opt.body = JSON.stringify(options.data);
    }
    try {
        const r = await fetch(url, opt);
        return await r.json();
    } catch (e) {
        console.error('API Error:', e);
        return { success: false, message: 'خطأ في الاتصال' };
    }
}

function escapeHtml(text) {
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return text.replace(/[&<>"']/g, m => map[m]);
}

function fmtPrice(price) {
    return new Intl.NumberFormat('ar-YE', { style: 'currency', currency: 'YER' }).format(price);
}

function fmtDate(date) {
    return new Date(date).toLocaleDateString('ar-YE', { year: 'numeric', month: 'short', day: 'numeric' });
}

// ===== Tab Switching =====
function switchTab(tab) {
    document.querySelectorAll('.admin-tabs button').forEach(b => b.classList.remove('active'));
    event.target.classList.add('active');
    
    switch(tab) {
        case 'overview': renderOverview(); break;
        case 'ads': renderAds(); break;
        case 'users': renderUsers(); break;
        case 'reports': renderReports(); break;
        case 'commissions': renderCommissions(); break;
        case 'settings': renderSettings(); break;
    }
}

// ===== Overview Tab =====
async function renderOverview() {
    const res = await api('admin&action=stats');
    if (!res.success) {
        toast('فشل تحميل الإحصائيات', 'error');
        return;
    }
    
    const stats = res.data || {};
    document.getElementById('totalAds').textContent = stats.totalAds || 0;
    document.getElementById('totalUsers').textContent = stats.totalUsers || 0;
    document.getElementById('totalReports').textContent = stats.pendingReports || 0;
    document.getElementById('totalCommissions').textContent = stats.pendingCommissions || 0;
    
    document.getElementById('adminContent').innerHTML = `
        <div style="text-align: center; padding: var(--sp-8);">
            <h3 style="font-size: 18px; margin-bottom: var(--sp-4);">مرحباً بك في لوحة الإدارة</h3>
            <p style="color: var(--text-soft); margin-bottom: var(--sp-6);">استخدم الأزرار أعلاه للتنقل بين أقسام الإدارة المختلفة</p>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--sp-4);">
                <div style="padding: var(--sp-4); background: var(--bg-soft); border-radius: var(--r-md);">
                    <div style="font-size: 20px; font-weight: 700; color: var(--brand-500); margin-bottom: var(--sp-2);">${stats.totalAds || 0}</div>
                    <div style="font-size: 12px; color: var(--muted);">إعلانات نشطة</div>
                </div>
                <div style="padding: var(--sp-4); background: var(--bg-soft); border-radius: var(--r-md);">
                    <div style="font-size: 20px; font-weight: 700; color: var(--brand-500); margin-bottom: var(--sp-2);">${stats.totalUsers || 0}</div>
                    <div style="font-size: 12px; color: var(--muted);">مستخدمين مسجلين</div>
                </div>
                <div style="padding: var(--sp-4); background: var(--bg-soft); border-radius: var(--r-md);">
                    <div style="font-size: 20px; font-weight: 700; color: var(--warning); margin-bottom: var(--sp-2);">${stats.pendingReports || 0}</div>
                    <div style="font-size: 12px; color: var(--muted);">بلاغات معلقة</div>
                </div>
                <div style="padding: var(--sp-4); background: var(--bg-soft); border-radius: var(--r-md);">
                    <div style="font-size: 20px; font-weight: 700; color: var(--warning); margin-bottom: var(--sp-2);">${stats.pendingCommissions || 0}</div>
                    <div style="font-size: 12px; color: var(--muted);">عمولات معلقة</div>
                </div>
            </div>
        </div>
    `;
}

// ===== Ads Tab =====
async function renderAds() {
    const res = await api('admin&action=list_ads');
    const ads = res.ads || res.data || [];
    
    document.getElementById('adminContent').innerHTML = `
        <div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--sp-4);">
                <h3 style="font-size: 16px; font-weight: 700;">الإعلانات (${ads.length})</h3>
                <button class="btn btn-secondary btn-sm" onclick="location.reload()">تحديث</button>
            </div>
            ${ads.length === 0 ? '<div style="text-align: center; padding: var(--sp-6); color: var(--muted);">لا توجد إعلانات</div>' : `
            <div style="overflow-x: auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>الإعلان</th>
                            <th>المستخدم</th>
                            <th>الفئة</th>
                            <th>السعر</th>
                            <th>الحالة</th>
                            <th>التاريخ</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${ads.map(ad => `
                        <tr>
                            <td>${escapeHtml(ad.title || ad.name || 'بدون عنوان')}</td>
                            <td>${escapeHtml(ad.user_name || ad.userName || '-')}</td>
                            <td>${escapeHtml(ad.category || '-')}</td>
                            <td>${fmtPrice(ad.price || 0)}</td>
                            <td><span class="status-badge status-${ad.status === 'active' ? 'active' : ad.status === 'pending' ? 'pending' : 'inactive'}">${ad.status === 'active' ? 'نشط' : ad.status === 'pending' ? 'معلق' : 'معطل'}</span></td>
                            <td>${fmtDate(ad.created_at || ad.createdAt)}</td>
                            <td>
                                <div class="action-buttons">
                                    <a href="ad.php?id=${ad.id}" target="_blank" class="btn btn-secondary btn-sm">عرض</a>
                                    <button class="btn btn-danger btn-sm" onclick="deleteAd(${ad.id})">حذف</button>
                                </div>
                            </td>
                        </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
            `}
        </div>
    `;
}

async function deleteAd(id) {
    if (!confirm('هل تريد حذف هذا الإعلان؟')) return;
    const res = await api('admin&action=delete_ad', { method: 'POST', data: { ad_id: id } });
    toast(res.message, res.success ? 'success' : 'error');
    if (res.success) renderAds();
}

// ===== Users Tab =====
async function renderUsers() {
    const res = await api('admin&action=list_users');
    const users = res.users || res.data || [];
    
    document.getElementById('adminContent').innerHTML = `
        <div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--sp-4);">
                <h3 style="font-size: 16px; font-weight: 700;">المستخدمون (${users.length})</h3>
                <button class="btn btn-secondary btn-sm" onclick="location.reload()">تحديث</button>
            </div>
            ${users.length === 0 ? '<div style="text-align: center; padding: var(--sp-6); color: var(--muted);">لا توجد مستخدمين</div>' : `
            <div style="overflow-x: auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>الاسم</th>
                            <th>الهاتف</th>
                            <th>البريد</th>
                            <th>الدور</th>
                            <th>التقييم</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${users.map(u => `
                        <tr>
                            <td>${escapeHtml(u.name || '-')}</td>
                            <td>${escapeHtml(u.phone || '-')}</td>
                            <td>${escapeHtml(u.email || '-')}</td>
                            <td>${u.role === 'admin' ? 'مسؤول' : u.role === 'seller' ? 'بائع' : 'مستخدم'}</td>
                            <td>${u.rating || 0} ⭐</td>
                            <td><span class="status-badge ${u.isBanned ? 'status-inactive' : 'status-active'}">${u.isBanned ? 'محظور' : 'نشط'}</span></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="user.php?id=${u.id}" target="_blank" class="btn btn-secondary btn-sm">عرض</a>
                                    ${!u.isBanned ? `<button class="btn btn-danger btn-sm" onclick="banUser(${u.id})">حظر</button>` : `<button class="btn btn-success btn-sm" onclick="unbanUser(${u.id})">إلغاء الحظر</button>`}
                                </div>
                            </td>
                        </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
            `}
        </div>
    `;
}

async function banUser(id) {
    if (!confirm('هل تريد حظر هذا المستخدم؟')) return;
    const res = await api('admin&action=ban_user', { method: 'POST', data: { user_id: id } });
    toast(res.message, res.success ? 'success' : 'error');
    if (res.success) renderUsers();
}

async function unbanUser(id) {
    if (!confirm('هل تريد إلغاء حظر هذا المستخدم؟')) return;
    const res = await api('admin&action=unban_user', { method: 'POST', data: { user_id: id } });
    toast(res.message, res.success ? 'success' : 'error');
    if (res.success) renderUsers();
}

// ===== Reports Tab =====
async function renderReports() {
    const res = await api('admin&action=reports');
    const reports = res.reports || res.data || [];
    
    document.getElementById('adminContent').innerHTML = `
        <div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--sp-4);">
                <h3 style="font-size: 16px; font-weight: 700;">البلاغات (${reports.length})</h3>
                <button class="btn btn-secondary btn-sm" onclick="location.reload()">تحديث</button>
            </div>
            ${reports.length === 0 ? '<div style="text-align: center; padding: var(--sp-6); color: var(--muted);">لا توجد بلاغات</div>' : `
            <div style="display: grid; gap: var(--sp-4);">
                ${reports.map(r => `
                <div style="padding: var(--sp-4); border: 1px solid var(--line); border-radius: var(--r-md); background: var(--bg-soft);">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: var(--sp-3);">
                        <div>
                            <strong style="font-size: 14px;">إعلان #${r.ad_id || r.adId}</strong>
                            <p style="font-size: 12px; color: var(--muted); margin-top: var(--sp-1);">المُبلغ: ${escapeHtml(r.reporter_name || r.reporterName || '-')}</p>
                        </div>
                        <span class="status-badge status-${r.status === 'pending' ? 'pending' : 'active'}">${r.status === 'pending' ? 'معلق' : 'مغلق'}</span>
                    </div>
                    <p style="font-size: 13px; color: var(--text-soft); margin-bottom: var(--sp-3);">${escapeHtml(r.reason || r.body || r.content || '')}</p>
                    <div style="display: flex; gap: var(--sp-2);">
                        <a href="ad.php?id=${r.ad_id || r.adId}" target="_blank" class="btn btn-secondary btn-sm">عرض الإعلان</a>
                        ${r.status === 'pending' ? `<button class="btn btn-success btn-sm" onclick="resolveReport(${r.id})">حلّ البلاغ</button>` : ''}
                    </div>
                </div>
                `).join('')}
            </div>
            `}
        </div>
    `;
}

async function resolveReport(id) {
    const res = await api('admin&action=resolve_report', { method: 'POST', data: { report_id: id } });
    toast(res.message, res.success ? 'success' : 'error');
    if (res.success) renderReports();
}

// ===== Commissions Tab =====
async function renderCommissions() {
    const res = await api('admin&action=commissions');
    const commissions = res.commissions || res.data || [];
    
    document.getElementById('adminContent').innerHTML = `
        <div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--sp-4);">
                <h3 style="font-size: 16px; font-weight: 700;">العمولات (${commissions.length})</h3>
                <button class="btn btn-secondary btn-sm" onclick="location.reload()">تحديث</button>
            </div>
            ${commissions.length === 0 ? '<div style="text-align: center; padding: var(--sp-6); color: var(--muted);">لا توجد عمولات</div>' : `
            <div style="overflow-x: auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>المستخدم</th>
                            <th>الإعلان</th>
                            <th>المبلغ</th>
                            <th>الحالة</th>
                            <th>التاريخ</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${commissions.map(c => `
                        <tr>
                            <td>${escapeHtml(c.user_name || c.userName || '-')}</td>
                            <td>#${c.ad_id || c.adId}</td>
                            <td>${fmtPrice(c.amount || 0)}</td>
                            <td><span class="status-badge status-${c.status === 'paid' ? 'active' : 'pending'}">${c.status === 'paid' ? 'مدفوع' : 'معلق'}</span></td>
                            <td>${fmtDate(c.created_at || c.createdAt)}</td>
                            <td>
                                ${c.status === 'pending' ? `<button class="btn btn-success btn-sm" onclick="approveCommission(${c.id})">تأكيد</button>` : '-'}
                            </td>
                        </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
            `}
        </div>
    `;
}

async function approveCommission(id) {
    if (!confirm('هل تريد تأكيد هذه العمولة؟')) return;
    const res = await api('admin&action=approve_commission', { method: 'POST', data: { id: id } });
    toast(res.message, res.success ? 'success' : 'error');
    if (res.success) renderCommissions();
}

// ===== Settings Tab =====
function renderSettings() {
    document.getElementById('adminContent').innerHTML = `
        <div style="max-width: 600px;">
            <h3 style="font-size: 16px; font-weight: 700; margin-bottom: var(--sp-4);">إعدادات المنصة</h3>
            <div style="padding: var(--sp-4); background: var(--bg-soft); border-radius: var(--r-md); margin-bottom: var(--sp-4);">
                <h4 style="font-size: 14px; font-weight: 600; margin-bottom: var(--sp-2);">الفئات</h4>
                <p style="font-size: 13px; color: var(--text-soft);">سيارات، عقارات، إلكترونيات، أثاث، وظائف، خدمات، حيوانات، أخرى</p>
            </div>
            <div style="padding: var(--sp-4); background: var(--bg-soft); border-radius: var(--r-md); margin-bottom: var(--sp-4);">
                <h4 style="font-size: 14px; font-weight: 600; margin-bottom: var(--sp-2);">المدن</h4>
                <p style="font-size: 13px; color: var(--text-soft);">جميع المحافظات اليمنية (22 مدينة)</p>
            </div>
            <div style="padding: var(--sp-4); background: var(--bg-soft); border-radius: var(--r-md);">
                <h4 style="font-size: 14px; font-weight: 600; margin-bottom: var(--sp-2);">معدل العمولة</h4>
                <p style="font-size: 13px; color: var(--text-soft);">يتم تعديل معدل العمولة من ملف <code>config.php</code></p>
            </div>
        </div>
    `;
}

// ===== Initialize =====
document.addEventListener('DOMContentLoaded', () => {
    renderOverview();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
