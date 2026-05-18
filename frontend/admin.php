<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة الإدارة - حراج الفاخر</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .admin-layout {
            display: flex;
            min-height: calc(100vh - 80px); /* minus header */
            max-width: 1400px;
            margin: 0 auto;
        }
        .sidebar {
            width: 250px;
            background: var(--card-bg);
            border-left: 1px solid var(--border-color);
            padding: 2rem 1rem;
        }
        .sidebar-nav {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .nav-item {
            padding: 0.75rem 1rem;
            border-radius: var(--radius-lg);
            color: var(--text-muted);
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
        }
        .nav-item:hover, .nav-item.active {
            background: rgba(5, 150, 105, 0.1);
            color: var(--primary);
        }
        .content-area {
            flex: 1;
            padding: 2rem;
            background: var(--bg-color);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: var(--card-bg);
            padding: 1.5rem;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
        }
        .stat-card .value {
            font-size: 2rem;
            font-weight: 900;
            color: var(--primary);
        }
        .stat-card .label {
            color: var(--text-muted);
            font-weight: 700;
            font-size: 0.875rem;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            overflow: hidden;
            border: 1px solid var(--border-color);
        }
        .data-table th, .data-table td {
            padding: 1rem;
            text-align: right;
            border-bottom: 1px solid var(--border-color);
        }
        .data-table th {
            background: rgba(0,0,0,0.02);
            font-weight: 800;
            color: var(--text-muted);
        }
        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 800;
        }
        .badge.banned { background: #fee2e2; color: #ef4444; }
        .badge.active { background: #d1fae5; color: #10b981; }
        .action-btn {
            padding: 0.4rem 0.8rem;
            border-radius: 0.5rem;
            border: none;
            font-weight: 700;
            font-size: 0.75rem;
            cursor: pointer;
            font-family: inherit;
        }
        .action-btn.danger { background: #fee2e2; color: #ef4444; }
        .action-btn.success { background: #d1fae5; color: #10b981; }
    </style>
</head>
<body>

    <header class="glass-header">
        <div style="max-w: 1400px; margin: 0 auto; padding: 1rem; display: flex; justify-content: space-between; align-items: center;">
            <a href="index.php" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;">
                <span style="background: var(--primary); color: white; padding: 4px 8px; border-radius: 8px; font-weight: 900; font-size: 0.8rem;">لوحة الإدارة</span>
                <span style="font-size: 1.25rem; font-weight: 900;">حراج الفاخر</span>
            </a>
            <div style="display: flex; gap: 1rem;">
                <button onclick="toggleTheme()" style="background:none; border:none; cursor:pointer; font-size:1.2rem;">🌓</button>
                <a href="index.php" style="color:var(--text-muted); font-weight:bold; text-decoration:none;">العودة للموقع</a>
            </div>
        </div>
    </header>

    <div class="admin-layout">
        <aside class="sidebar">
            <nav class="sidebar-nav">
                <div class="nav-item active" onclick="switchView('dashboard', this)">📊 لوحة القيادة</div>
                <div class="nav-item" onclick="switchView('users', this)">👥 إدارة المستخدمين</div>
                <div class="nav-item" onclick="switchView('reports', this)">🚩 البلاغات</div>
                <div class="nav-item" onclick="switchView('ads', this)">📦 إدارة الإعلانات</div>
            </nav>
        </aside>

        <main class="content-area animate-fade-in" id="main-content">
            <!-- Views dynamically loaded here -->
        </main>
    </div>

    <script src="assets/js/app.js"></script>
    <script>
        async function loadDashboard() {
            const content = document.getElementById('main-content');
            content.innerHTML = 'جاري التحميل...';
            
            try {
                const res = await apiRequest('admin&action=stats');
                const s = res.data;
                content.innerHTML = `
                    <h2 style="margin-top:0;">نظرة عامة</h2>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="value">${s.users}</div>
                            <div class="label">إجمالي الأعضاء</div>
                        </div>
                        <div class="stat-card">
                            <div class="value">${s.ads}</div>
                            <div class="label">إجمالي الإعلانات</div>
                        </div>
                        <div class="stat-card">
                            <div class="value">${s.reports}</div>
                            <div class="label">بلاغات قيد المراجعة</div>
                        </div>
                        <div class="stat-card">
                            <div class="value">${s.commissions} ر.ي</div>
                            <div class="label">عمولات محصلة</div>
                        </div>
                    </div>
                `;
            } catch(e) {}
        }

                async function loadAds() {
            const content = document.getElementById('main-content');
            content.innerHTML = 'جاري التحميل...';
            
            try {
                const res = await apiRequest('admin&action=ads');
                let html = `
                    <h2 style="margin-top:0;">إدارة الإعلانات الشاملة</h2>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>رقم الإعلان</th>
                                <th>العنوان</th>
                                <th>الناشر</th>
                                <th>القسم / المدينة</th>
                                <th>التاريخ</th>
                                <th>إجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                `;
                
                if (res.data.length === 0) {
                    html += `<tr><td colspan="6" style="text-align:center;">لا توجد إعلانات</td></tr>`;
                } else {
                    res.data.forEach(a => {
                        html += `
                            <tr>
                                <td><a href="ad.php?id=${a.id}" target="_blank">#${a.id}</a></td>
                                <td><strong>${a.title}</strong></td>
                                <td>${a.authorName}</td>
                                <td>${a.category} / ${a.city}</td>
                                <td style="direction:ltr;">${a.createdAt.substring(0, 10)}</td>
                                <td><button class="action-btn danger" onclick="deleteAdGlobal(${a.id})">حذف نهائي 🗑️</button></td>
                            </tr>
                        `;
                    });
                }
                
                html += `</tbody></table>`;
                content.innerHTML = html;
            } catch(e) {}
        }

        async function deleteAdGlobal(adId) {
            if(!confirm('هل أنت متأكد من حذف هذا الإعلان نهائياً؟ سيتم مسح صوره أيضاً.')) return;
            try {
                await apiRequest('admin', 'POST', { action: 'delete_ad', ad_id: adId });
                loadAds();
                // Update dashboard stats too if we were to refresh
            } catch(e) {}
        }

        async function loadUsers() {
            const content = document.getElementById('main-content');
            content.innerHTML = 'جاري التحميل...';
            
            try {
                const res = await apiRequest('admin&action=users');
                let html = `
                    <h2 style="margin-top:0;">إدارة المستخدمين</h2>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>م</th>
                                <th>الاسم</th>
                                <th>الجوال</th>
                                <th>الدور</th>
                                <th>الحالة</th>
                                <th>إجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                `;
                
                res.data.forEach(u => {
                    const statusBadge = u.isBanned ? '<span class="badge banned">محظور</span>' : '<span class="badge active">نشط</span>';
                    const actionBtn = u.isBanned 
                        ? `<button class="action-btn success" onclick="toggleBan(${u.id}, 'unban')">فك الحظر</button>`
                        : `<button class="action-btn danger" onclick="toggleBan(${u.id}, 'ban')">حظر</button>`;
                        
                    html += `
                        <tr>
                            <td>${u.id}</td>
                            <td><strong>${u.name}</strong></td>
                            <td>${u.phone}</td>
                            <td>${u.role}</td>
                            <td>${statusBadge}</td>
                            <td>${u.role !== 'admin' ? actionBtn : '-'}</td>
                        </tr>
                    `;
                });
                
                html += `</tbody></table>`;
                content.innerHTML = html;
            } catch(e) {}
        }

        async function loadReports() {
            const content = document.getElementById('main-content');
            content.innerHTML = 'جاري التحميل...';
            
            try {
                const res = await apiRequest('admin&action=reports');
                let html = `
                    <h2 style="margin-top:0;">إدارة البلاغات</h2>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>رقم الإعلان</th>
                                <th>المُبلّغ</th>
                                <th>السبب</th>
                                <th>الحالة</th>
                                <th>إجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                `;
                
                if (res.data.length === 0) {
                    html += `<tr><td colspan="5" style="text-align:center;">لا توجد بلاغات</td></tr>`;
                } else {
                    res.data.forEach(r => {
                        const actionBtn = r.status === 'pending' 
                            ? `<button class="action-btn success" onclick="resolveReport(${r.id})">تحديد كـ محلول</button>`
                            : `-`;
                            
                        html += `
                            <tr>
                                <td><a href="ad.php?id=${r.adId}">#${r.adId}</a></td>
                                <td>${r.reporterName}</td>
                                <td>${r.reason}</td>
                                <td>${r.status === 'pending' ? 'قيد الانتظار' : 'محلول'}</td>
                                <td>${actionBtn}</td>
                            </tr>
                        `;
                    });
                }
                
                html += `</tbody></table>`;
                content.innerHTML = html;
            } catch(e) {}
        }

        async function toggleBan(userId, type) {
            if(!confirm('تأكيد الإجراء؟')) return;
            const action = type === 'ban' ? 'ban_user' : 'unban_user';
            try {
                await apiRequest('admin', 'POST', { action, user_id: userId });
                loadUsers(); // refresh
            } catch(e) {}
        }

        async function resolveReport(reportId) {
            try {
                await apiRequest('admin', 'POST', { action: 'resolve_report', report_id: reportId });
                loadReports();
            } catch(e) {}
        }

        function switchView(view, el) {
            document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
            el.classList.add('active');
            
            if (view === 'dashboard') loadDashboard();
            if (view === 'users') loadUsers();
            if (view === 'reports') loadReports();
            if (view === 'ads') loadAds();
        }

        // Init
        document.addEventListener('DOMContentLoaded', loadDashboard);
    </script>
</body>
</html>
