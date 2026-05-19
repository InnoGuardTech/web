<?php
require_once __DIR__ . '/../backend/config.php';
if (function_exists('secureSession')) { secureSession(); } else { session_start(); }
if (isset($_SESSION['user_id'])) { header('Location: index.php'); exit; }
define('PAGE_TITLE', 'تسجيل الدخول | حراج اليمن الفاخر');
require __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/icons.php';
?>
<div class="auth-wrap">
    <div class="auth-card">
        <div class="auth-header">
            <div style="width:64px;height:64px;border-radius:18px;background:linear-gradient(135deg,var(--brand-500),var(--brand-700));margin:0 auto 18px;display:grid;place-items:center;box-shadow:var(--sh-md);">
                <span style="color:#fff;font-size:30px;font-weight:900;">ح</span>
            </div>
            <h1>أهلاً بك في حراج اليمن</h1>
            <p>سوق الإعلانات الأفخم. سجل دخولك أو أنشئ حساباً جديداً</p>
        </div>
        <div class="auth-tabs">
            <button class="active" data-tab="login">تسجيل الدخول</button>
            <button data-tab="register">إنشاء حساب</button>
        </div>
        <div class="auth-body">
            <form id="loginForm" class="auth-form">
                <div class="field">
                    <label class="field-label">رقم الجوال</label>
                    <input type="tel" class="input" name="phone" placeholder="7XXXXXXXX" required pattern="7[0-9]{8}">
                </div>
                <div class="field">
                    <label class="field-label">كلمة المرور</label>
                    <input type="password" class="input" name="password" placeholder="••••••••" required minlength="6">
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
                    <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text-soft);cursor:pointer;">
                        <input type="checkbox" name="remember"> تذكرني
                    </label>
                    <a href="#" onclick="event.preventDefault();showForgot()" style="font-size:13px;color:var(--brand-600);font-weight:600;">نسيت كلمة المرور؟</a>
                </div>
                <button type="submit" class="btn btn-primary btn-block btn-lg"><?= icon('log-in', ['size'=>18]) ?> تسجيل الدخول</button>
            </form>
            <form id="registerForm" class="auth-form" style="display:none;">
                <div class="field">
                    <label class="field-label">الاسم الكامل</label>
                    <input type="text" class="input" name="name" placeholder="أدخل اسمك" required minlength="3" maxlength="60">
                </div>
                <div class="field">
                    <label class="field-label">رقم الجوال</label>
                    <input type="tel" class="input" name="phone" placeholder="7XXXXXXXX" required pattern="7[0-9]{8}">
                </div>
                <div class="field">
                    <label class="field-label">كلمة المرور</label>
                    <input type="password" class="input" name="password" placeholder="6 أحرف على الأقل" required minlength="6">
                    <div class="field-hint">استخدم 6 أحرف على الأقل مع أرقام لمزيد من الأمان</div>
                </div>
                <div class="field" style="margin-bottom:18px;">
                    <label style="display:flex;align-items:flex-start;gap:8px;font-size:13px;color:var(--text-soft);cursor:pointer;line-height:1.6;">
                        <input type="checkbox" required style="margin-top:4px;">
                        <span>أوافق على <a href="#" style="color:var(--brand-600);">الشروط والأحكام</a> و<a href="#" style="color:var(--brand-600);">سياسة الخصوصية</a></span>
                    </label>
                </div>
                <button type="submit" class="btn btn-primary btn-block btn-lg"><?= icon('user', ['size'=>18]) ?> إنشاء حساب جديد</button>
            </form>
            <div class="auth-divider">حسابات تجريبية</div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <button class="chip" onclick="quickLogin('777111111','Admin@123')"><?= icon('shield', ['size'=>14]) ?> أدمن</button>
                <button class="chip" onclick="quickLogin('777222222','User@123')"><?= icon('user', ['size'=>14]) ?> بائع</button>
                <button class="chip" onclick="quickLogin('777444444','User@123')"><?= icon('shopping', ['size'=>14]) ?> مشتري</button>
            </div>
        </div>
    </div>
</div>
<script>
document.querySelectorAll('.auth-tabs button').forEach(b => {
    b.onclick = () => {
        document.querySelectorAll('.auth-tabs button').forEach(x => x.classList.remove('active'));
        b.classList.add('active');
        document.getElementById('loginForm').style.display = b.dataset.tab === 'login' ? '' : 'none';
        document.getElementById('registerForm').style.display = b.dataset.tab === 'register' ? '' : 'none';
    };
});
document.getElementById('loginForm').onsubmit = async (e) => {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));
    const btn = e.target.querySelector('button[type=submit]');
    btn.disabled = true; btn.innerHTML = 'جارٍ الدخول...';
    const res = await api('auth&action=login', { method: 'POST', data });
    if (res.success) { toast('تم تسجيل الدخول بنجاح', 'success'); setTimeout(() => location.href = 'index.php', 600); }
    else { toast(res.message || 'بيانات غير صحيحة', 'error'); btn.disabled = false; btn.innerHTML = 'تسجيل الدخول'; }
};
document.getElementById('registerForm').onsubmit = async (e) => {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));
    const btn = e.target.querySelector('button[type=submit]');
    btn.disabled = true; btn.innerHTML = 'جارٍ الإنشاء...';
    const res = await api('auth&action=register', { method: 'POST', data });
    if (res.success) { toast('تم إنشاء الحساب بنجاح', 'success'); setTimeout(() => location.href = 'index.php', 800); }
    else { toast(res.message || 'تعذر إنشاء الحساب', 'error'); btn.disabled = false; btn.innerHTML = 'إنشاء حساب جديد'; }
};
async function quickLogin(phone, password) {
    const res = await api('auth&action=login', { method: 'POST', data: { phone, password } });
    if (res.success) { toast('تم الدخول السريع', 'success'); setTimeout(() => location.href = 'index.php', 500); }
    else toast(res.message, 'error');
}
function showForgot() {
    const overlay = document.createElement('div');
    overlay.className = 'modal-overlay';
    overlay.innerHTML = `<div class="modal"><div class="modal-header"><h3>استعادة كلمة المرور</h3></div><div class="modal-body"><p style="color:var(--text-soft);margin-bottom:16px;">أدخل رقم جوالك وسنرسل لك رمز التحقق (OTP).</p><div id="forgotStep1"><input type="tel" class="input" id="forgotPhone" placeholder="7XXXXXXXX" pattern="7[0-9]{8}"></div><div id="forgotStep2" style="display:none;"><div class="field"><label class="field-label">رمز التحقق</label><input type="text" class="input" id="forgotOtp" placeholder="6 أرقام" maxlength="6"></div><div class="field"><label class="field-label">كلمة مرور جديدة</label><input type="password" class="input" id="forgotNewPass" placeholder="••••••••" minlength="6"></div></div></div><div class="modal-footer"><button class="btn btn-ghost" onclick="this.closest('.modal-overlay').remove()">إلغاء</button><button class="btn btn-primary" id="forgotBtn">إرسال الرمز</button></div></div>`;
    document.body.appendChild(overlay);
    let step = 1;
    document.getElementById('forgotBtn').onclick = async () => {
        if (step === 1) {
            const phone = document.getElementById('forgotPhone').value;
            if (!/^7\d{8}$/.test(phone)) return toast('رقم جوال غير صحيح', 'error');
            const res = await api('auth&action=forgot_password', { method: 'POST', data: { phone } });
            if (res.success) {
                toast('تم إرسال الرمز' + (res.dev_otp ? ' (Dev: ' + res.dev_otp + ')' : ''), 'success', 6000);
                document.getElementById('forgotStep1').style.display = 'none';
                document.getElementById('forgotStep2').style.display = 'block';
                document.getElementById('forgotBtn').textContent = 'تأكيد التغيير';
                step = 2;
            } else toast(res.message, 'error');
        } else {
            const phone = document.getElementById('forgotPhone').value;
            const otp = document.getElementById('forgotOtp').value;
            const newPass = document.getElementById('forgotNewPass').value;
            if (otp.length !== 6) return toast('أدخل الرمز كاملاً', 'error');
            if (newPass.length < 6) return toast('كلمة المرور قصيرة', 'error');
            const res = await api('auth&action=reset_password', { method: 'POST', data: { phone, otp, new_password: newPass } });
            if (res.success) { toast('تم تغيير كلمة المرور', 'success'); overlay.remove(); }
            else toast(res.message, 'error');
        }
    };
}
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
