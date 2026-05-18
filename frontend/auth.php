<?php
session_start();
// If already logged in, redirect to index
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول / إنشاء حساب - حراج الفاخر</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .auth-container {
            max-width: 450px;
            margin: 4rem auto;
            padding: 2rem;
        }
        .tabs {
            display: flex;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 1.5rem;
        }
        .tab {
            flex: 1;
            text-align: center;
            padding: 1rem;
            cursor: pointer;
            font-weight: 800;
            color: var(--text-muted);
            border-bottom: 2px solid transparent;
            transition: all 0.3s ease;
        }
        .tab.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 700;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
        }
        .hidden { display: none; }
        .demo-switcher {
            margin-top: 2rem;
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.3);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
        }
        .demo-user {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .demo-user:hover {
            border-color: #f59e0b;
        }
    </style>
</head>
<body>
    <header class="glass-header">
        <div style="max-w: 1200px; margin: 0 auto; padding: 1rem; display: flex; justify-content: space-between; align-items: center;">
            <a href="index.php" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;">
                <span style="background: var(--primary); color: white; padding: 4px 8px; border-radius: 8px; font-weight: 900; font-size: 0.8rem;">حراج</span>
                <span style="font-size: 1.25rem; font-weight: 900;">الفاخر</span>
            </a>
            <button onclick="toggleTheme()" style="background:none; border:none; cursor:pointer; font-size:1.2rem;">🌓</button>
        </div>
    </header>

    <main class="auth-container premium-card animate-fade-in">
        <div class="tabs">
            <div class="tab active" id="tab-login" onclick="switchTab('login')">تسجيل الدخول</div>
            <div class="tab" id="tab-register" onclick="switchTab('register')">إنشاء حساب</div>
        </div>

        <form id="login-form" onsubmit="handleLogin(event)">
            <div class="form-group">
                <label>رقم الجوال *</label>
                <input type="text" id="login-phone" class="input-premium" placeholder="مثال: 771234567" required>
            </div>
            <div class="form-group">
                <label>كلمة المرور *</label>
                <input type="password" id="login-password" class="input-premium" placeholder="اكتب كلمة المرور..." required>
            </div>
            <button type="submit" class="btn-primary" style="width: 100%;">تسجيل الدخول</button>
        </form>

        <form id="register-form" class="hidden" onsubmit="handleRegister(event)">
            <div class="form-group">
                <label>الاسم الكامل *</label>
                <input type="text" id="reg-name" class="input-premium" placeholder="اسمك الحقيقي..." required>
            </div>
            <div class="form-group">
                <label>رقم الجوال *</label>
                <input type="text" id="reg-phone" class="input-premium" placeholder="مثال: 771234567" required>
            </div>
            <div class="form-group">
                <label>كلمة المرور *</label>
                <input type="password" id="reg-password" class="input-premium" placeholder="اختر كلمة مرور قوية..." required>
            </div>
            <button type="submit" class="btn-primary" style="width: 100%;">إنشاء الحساب</button>
        </form>
    </main>

    <div class="auth-container demo-switcher animate-fade-in" style="margin-top: 1rem;">
        <h4 style="color: #b45309; margin-top: 0;">🛠️ التبديل السريع للمطورين</h4>
        <p style="font-size: 0.75rem; color: #d97706;">اختر حساباً للدخول به فوراً للتجربة:</p>
        <div id="demo-users-list"></div>
    </div>

    <script src="assets/js/app.js"></script>
    <script>
        function switchTab(tab) {
            document.getElementById('login-form').classList.toggle('hidden', tab !== 'login');
            document.getElementById('register-form').classList.toggle('hidden', tab !== 'register');
            document.getElementById('tab-login').classList.toggle('active', tab === 'login');
            document.getElementById('tab-register').classList.toggle('active', tab === 'register');
        }

        async function handleLogin(e) {
            e.preventDefault();
            const phone = document.getElementById('login-phone').value;
            const password = document.getElementById('login-password').value;
            
            try {
                await apiRequest('auth', 'POST', { action: 'login', phone, password });
                showToast('تم تسجيل الدخول بنجاح! يتم تحويلك...', 'success');
                setTimeout(() => window.location.href = 'index.php', 1000);
            } catch (err) {
                // error already shown by app.js toast
            }
        }

        async function handleRegister(e) {
            e.preventDefault();
            const name = document.getElementById('reg-name').value;
            const phone = document.getElementById('reg-phone').value;
            const password = document.getElementById('reg-password').value;
            
            try {
                await apiRequest('auth', 'POST', { action: 'register', name, phone, password });
                showToast('تم إنشاء الحساب! يرجى تسجيل الدخول.', 'success');
                switchTab('login');
                document.getElementById('login-phone').value = phone;
                document.getElementById('reg-password').value = '';
            } catch (err) {}
        }

        async function quickSwitch(id) {
            try {
                await apiRequest('auth', 'POST', { action: 'quick_switch', target_id: id });
                window.location.href = 'index.php';
            } catch (err) {}
        }

        // Load demo users
        document.addEventListener('DOMContentLoaded', async () => {
            try {
                const res = await apiRequest('auth&action=demo_users', 'GET');
                const list = document.getElementById('demo-users-list');
                res.data.forEach(u => {
                    const div = document.createElement('div');
                    div.className = 'demo-user';
                    div.onclick = () => quickSwitch(u.id);
                    div.innerHTML = `
                        <div>
                            <div style="font-weight:bold; font-size:0.875rem;">${u.name}</div>
                            <div style="font-size:0.75rem; color:var(--text-muted);">${u.phone}</div>
                        </div>
                        <span style="font-size:0.7rem; background:#fef3c7; color:#d97706; padding:2px 6px; border-radius:4px;">${u.role}</span>
                    `;
                    list.appendChild(div);
                });
            } catch (err) {}
        });
    </script>
</body>
</html>
