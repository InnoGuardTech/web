<?php
require_once __DIR__ . '/../config.php';
if (isset($_SESSION['user_id'])) { header('Location: index.php'); exit; }
define('PAGE_TITLE', 'تسجيل الدخول - ' . SITE_NAME);
define('PAGE_DESC', 'سجّل دخولك أو أنشئ حساباً جديداً في حراج اليمن');
define('HIDE_SEARCH', true);
include __DIR__ . '/includes/header.php';
?>

<style>
.auth-wrap {
    max-width: 480px;
    margin: 2rem auto;
    padding: 0 1rem;
}
.auth-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-lg);
    overflow: hidden;
}
.auth-tabs {
    display: flex;
    background: var(--bg-color);
    border-bottom: 1px solid var(--border-color);
}
.auth-tab {
    flex: 1;
    padding: 1.1rem;
    text-align: center;
    cursor: pointer;
    font-weight: 900;
    color: var(--text-muted);
    border-bottom: 3px solid transparent;
    transition: var(--transition);
    font-size: 0.95rem;
}
.auth-tab.active { color: var(--primary); border-bottom-color: var(--accent); background: var(--card-bg); }
.auth-body { padding: 2rem; }
.auth-body h2 { margin: 0 0 0.5rem; color: var(--primary); font-weight: 900; }
.auth-body .sub { color: var(--text-muted); font-size: 0.88rem; margin-bottom: 1.5rem; }
.auth-divider {
    text-align: center;
    padding: 1.5rem 2rem 0;
    border-top: 1px dashed var(--border-color);
    margin-top: 1rem;
    font-size: 0.85rem;
}
.auth-divider a { color: var(--primary); font-weight: 800; }
</style>

<div class="auth-wrap">
    <div class="auth-card animate-fade-in">
        <div class="auth-tabs">
            <div class="auth-tab active" id="tab-login" onclick="switchTab('login')">تسجيل الدخول</div>
            <div class="auth-tab" id="tab-register" onclick="switchTab('register')">إنشاء حساب</div>
        </div>

        <div class="auth-body">
            <!-- LOGIN -->
            <form id="login-form" onsubmit="handleLogin(event)">
                <h2>👋 أهلاً بعودتك!</h2>
                <p class="sub">سجّل دخولك للوصول إلى حسابك</p>

                <div class="form-group">
                    <label>📱 رقم الجوال</label>
                    <input type="tel" id="login-phone" placeholder="مثال: 777111111" required autocomplete="tel">
                </div>
                <div class="form-group">
                    <label>🔒 كلمة المرور</label>
                    <input type="password" id="login-password" placeholder="••••••••" required autocomplete="current-password">
                </div>

                <button type="submit" class="btn-primary btn-block">دخول إلى حسابي →</button>

                <div style="text-align:center; margin-top:1rem;">
                    <a href="#" onclick="switchTab('forgot'); return false;" style="color: var(--primary); font-size:0.88rem; font-weight:800;">نسيت كلمة المرور؟</a>
                </div>
            </form>

            <!-- REGISTER -->
            <form id="register-form" class="hidden" onsubmit="handleRegister(event)">
                <h2>🎉 انضم لنا!</h2>
                <p class="sub">أنشئ حسابك الجديد في حراج اليمن</p>

                <div class="form-group">
                    <label>👤 الاسم الكامل</label>
                    <input type="text" id="reg-name" placeholder="مثال: أحمد محمد" required minlength="3" maxlength="100">
                </div>
                <div class="form-group">
                    <label>📱 رقم الجوال</label>
                    <input type="tel" id="reg-phone" placeholder="777111111" required>
                </div>
                <div class="form-group">
                    <label>📧 البريد الإلكتروني (اختياري)</label>
                    <input type="email" id="reg-email" placeholder="example@gmail.com">
                </div>
                <div class="form-group">
                    <label>🔒 كلمة المرور</label>
                    <input type="password" id="reg-password" placeholder="على الأقل 6 أحرف وأرقام" required minlength="6" autocomplete="new-password">
                    <small style="color: var(--text-muted); font-size:0.78rem;">يجب أن تحتوي على حرف ورقم على الأقل</small>
                </div>

                <button type="submit" class="btn-primary btn-block">إنشاء حسابي ✓</button>
            </form>

            <!-- FORGOT -->
            <form id="forgot-form" class="hidden" onsubmit="handleForgot(event)">
                <h2>🔑 استعادة كلمة المرور</h2>
                <p class="sub">أدخل رقم جوالك وسنرسل لك رمز التحقق</p>

                <div class="form-group">
                    <label>📱 رقم الجوال</label>
                    <input type="tel" id="forgot-phone" placeholder="777111111" required>
                </div>

                <button type="submit" class="btn-primary btn-block">إرسال رمز التحقق</button>
                <div style="text-align:center; margin-top:1rem;">
                    <a href="#" onclick="switchTab('login'); return false;" style="color: var(--primary); font-size:0.85rem;">← العودة لتسجيل الدخول</a>
                </div>
            </form>

            <!-- RESET -->
            <form id="reset-form" class="hidden" onsubmit="handleReset(event)">
                <h2>✓ إعادة تعيين كلمة المرور</h2>
                <p class="sub">أدخل الرمز الذي وصلك وكلمة المرور الجديدة</p>

                <div class="form-group">
                    <label>📱 رقم الجوال</label>
                    <input type="tel" id="reset-phone" readonly>
                </div>
                <div class="form-group">
                    <label>🔢 رمز التحقق (6 أرقام)</label>
                    <input type="text" id="reset-code" placeholder="123456" maxlength="6" required>
                </div>
                <div class="form-group">
                    <label>🔒 كلمة المرور الجديدة</label>
                    <input type="password" id="reset-password" placeholder="على الأقل 6 أحرف وأرقام" required minlength="6">
                </div>

                <button type="submit" class="btn-primary btn-block">تعيين كلمة المرور الجديدة</button>
            </form>
        </div>

        <div class="auth-divider">
            بالتسجيل، فأنت توافق على شروط الاستخدام وسياسة الخصوصية للموقع
        </div>
    </div>

    <div style="background: rgba(13,148,136,0.08); border:1px solid rgba(13,148,136,0.2); border-radius: var(--radius-lg); padding: 1rem; margin-top: 1.5rem; font-size: 0.85rem;">
        <strong style="color: var(--secondary);">🔐 حسابات تجريبية للاختبار:</strong><br>
        <span style="font-family:monospace; color: var(--text-muted); display: block; margin-top: 6px;">
            مدير: <strong>777111111</strong> / <strong>Admin@123</strong><br>
            مستخدم: <strong>777222222</strong> / <strong>User@123</strong>
        </span>
    </div>
</div>

<script src="assets/js/app.js"></script>
<script>
function switchTab(tab) {
    ['login','register','forgot','reset'].forEach(t => {
        const f = document.getElementById(t + '-form');
        if (f) f.classList.add('hidden');
    });
    const target = document.getElementById(tab + '-form');
    if (target) target.classList.remove('hidden');

    document.getElementById('tab-login').classList.toggle('active', tab === 'login');
    document.getElementById('tab-register').classList.toggle('active', tab === 'register');
}

async function handleLogin(e) {
    e.preventDefault();
    const phone = document.getElementById('login-phone').value;
    const password = document.getElementById('login-password').value;
    const btn = e.target.querySelector('button[type=submit]');
    btn.disabled = true; btn.textContent = '⏳ جاري الدخول...';

    try {
        const r = await apiRequest('auth&action=login', 'POST', { phone, password }, { skipCsrf: true });
        showToast('🎉 تم تسجيل الدخول!', 'success');
        setTimeout(() => window.location.href = 'index.php', 800);
    } catch (err) {
        btn.disabled = false; btn.textContent = 'دخول إلى حسابي →';
    }
}

async function handleRegister(e) {
    e.preventDefault();
    const name = document.getElementById('reg-name').value.trim();
    const phone = document.getElementById('reg-phone').value.trim();
    const email = document.getElementById('reg-email').value.trim();
    const password = document.getElementById('reg-password').value;
    const btn = e.target.querySelector('button[type=submit]');
    btn.disabled = true; btn.textContent = '⏳ جاري الإنشاء...';

    try {
        await apiRequest('auth&action=register', 'POST', { name, phone, email, password }, { skipCsrf: true });
        showToast('✓ تم إنشاء حسابك!', 'success');
        setTimeout(() => window.location.href = 'index.php', 1000);
    } catch (err) {
        btn.disabled = false; btn.textContent = 'إنشاء حسابي ✓';
    }
}

async function handleForgot(e) {
    e.preventDefault();
    const phone = document.getElementById('forgot-phone').value;
    try {
        await apiRequest('auth&action=forgot_password', 'POST', { phone }, { skipCsrf: true });
        showToast('📩 إذا كان الرقم مسجلاً، فستصلك رسالة', 'success', 5000);
        document.getElementById('reset-phone').value = phone;
        switchTab('reset');
    } catch (err) {}
}

async function handleReset(e) {
    e.preventDefault();
    const phone = document.getElementById('reset-phone').value;
    const code = document.getElementById('reset-code').value;
    const newPwd = document.getElementById('reset-password').value;

    try {
        await apiRequest('auth&action=reset_password', 'POST', { phone, code, new_password: newPwd }, { skipCsrf: true });
        showToast('✓ تم تغيير كلمة المرور!', 'success');
        setTimeout(() => switchTab('login'), 1500);
    } catch (err) {}
}
</script>
</body>
</html>
