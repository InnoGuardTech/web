<?php
require_once __DIR__ . '/../backend/config.php';
if (function_exists('secureSession')) { secureSession(); } else { session_start(); }
if (!isset($_SESSION['user_id'])) { header('Location: auth.php'); exit; }
$me = getCurrentUser();
define('PAGE_TITLE', 'الإعدادات | حراج اليمن');
require __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/icons.php';
?>
<div style="max-width:780px;margin:0 auto;">
    <div style="margin-bottom:var(--sp-5);">
        <h1 class="section-title">إعدادات الحساب</h1>
        <p class="section-subtitle">إدارة معلوماتك الشخصية والأمان</p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:var(--sp-5);">
        <button class="chip active" data-tab="profile"><?= icon('user', ['size'=>14]) ?> الملف الشخصي</button>
        <button class="chip" data-tab="password"><?= icon('lock', ['size'=>14]) ?> كلمة المرور</button>
        <button class="chip" data-tab="verify"><?= icon('shield', ['size'=>14]) ?> توثيق الجوال</button>
        <button class="chip" data-tab="danger"><?= icon('alert', ['size'=>14]) ?> منطقة الخطر</button>
    </div>

    <div id="tab-profile" class="settings-tab surface-card" style="padding:var(--sp-6);">
        <h3 style="font-size:17px;font-weight:700;margin-bottom:var(--sp-4);">المعلومات الشخصية</h3>
        <form id="profileForm">
            <div class="field"><label class="field-label">الاسم الكامل</label><input type="text" class="input" name="name" value="<?= htmlspecialchars($me['name']) ?>" required minlength="3"></div>
            <div class="field"><label class="field-label">رقم الجوال</label><input type="tel" class="input" value="<?= htmlspecialchars($me['phone']) ?>" disabled><div class="field-hint">لا يمكن تغيير الرقم. تواصل مع الإدارة عند الحاجة.</div></div>
            <div class="field"><label class="field-label">نبذة عنك (اختياري)</label><textarea class="textarea" name="bio" maxlength="200" rows="3"><?= htmlspecialchars($me['bio'] ?? '') ?></textarea></div>
            <button type="submit" class="btn btn-primary"><?= icon('check', ['size'=>16]) ?> حفظ التغييرات</button>
        </form>
    </div>

    <div id="tab-password" class="settings-tab surface-card" style="padding:var(--sp-6);display:none;">
        <h3 style="font-size:17px;font-weight:700;margin-bottom:var(--sp-4);">تغيير كلمة المرور</h3>
        <form id="passwordForm">
            <div class="field"><label class="field-label">كلمة المرور الحالية</label><input type="password" class="input" name="current_password" required></div>
            <div class="field"><label class="field-label">كلمة المرور الجديدة</label><input type="password" class="input" name="new_password" required minlength="6"><div class="field-hint">6 أحرف على الأقل، ويفضل مع أرقام.</div></div>
            <div class="field"><label class="field-label">تأكيد كلمة المرور</label><input type="password" class="input" name="confirm_password" required minlength="6"></div>
            <button type="submit" class="btn btn-primary"><?= icon('lock', ['size'=>16]) ?> تحديث كلمة المرور</button>
        </form>
    </div>

    <div id="tab-verify" class="settings-tab surface-card" style="padding:var(--sp-6);display:none;">
        <h3 style="font-size:17px;font-weight:700;margin-bottom:var(--sp-4);">توثيق رقم الجوال</h3>
        <?php if (!empty($me['phone_verified']) || !empty($me['isPhoneVerified'])): ?>
            <div style="padding:20px;background:rgba(16,185,129,.1);border-radius:12px;color:var(--success);font-weight:600;"><?= icon('check-circle', ['size'=>18]) ?> تم توثيق رقم جوالك بنجاح</div>
        <?php else: ?>
            <p style="color:var(--text-soft);margin-bottom:var(--sp-4);">سنرسل لك رمز تحقق عبر SMS.</p>
            <button class="btn btn-primary" onclick="sendOtp()">إرسال رمز التحقق</button>
            <div id="otpBox" style="display:none;margin-top:var(--sp-4);">
                <div class="field"><label class="field-label">أدخل الرمز (6 أرقام)</label><input type="text" class="input" id="otpInput" maxlength="6" style="text-align:center;font-size:20px;letter-spacing:.5em;"></div>
                <button class="btn btn-success" onclick="verifyOtp()">تأكيد</button>
            </div>
        <?php endif; ?>
    </div>

    <div id="tab-danger" class="settings-tab surface-card" style="padding:var(--sp-6);display:none;border-color:rgba(239,68,68,.3);">
        <h3 style="font-size:17px;font-weight:700;margin-bottom:var(--sp-4);color:var(--danger);">منطقة الخطر</h3>
        <div style="padding:16px;border:1px solid rgba(239,68,68,.3);border-radius:12px;background:rgba(239,68,68,.05);">
            <div style="font-weight:700;margin-bottom:6px;">حذف الحساب نهائيًا</div>
            <p style="font-size:13px;color:var(--text-soft);margin-bottom:14px;line-height:1.6;">سيتم حذف حسابك وأرشفة جميع إعلاناتك. هذا الإجراء لا يمكن التراجع عنه.</p>
            <button class="btn btn-danger btn-sm" onclick="deleteAccount()">حذف الحساب</button>
        </div>
    </div>
</div>
<script>
document.querySelectorAll('[data-tab]').forEach(btn => {
    btn.onclick = () => {
        document.querySelectorAll('[data-tab]').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.settings-tab').forEach(t => t.style.display = 'none');
        btn.classList.add('active');
        document.getElementById('tab-' + btn.dataset.tab).style.display = '';
    };
});
document.getElementById('profileForm').onsubmit = async (e) => {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));
    const res = await api('user&action=update_profile', { method: 'POST', data });
    toast(res.message, res.success ? 'success' : 'error');
};
document.getElementById('passwordForm').onsubmit = async (e) => {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));
    if (data.new_password !== data.confirm_password) return toast('كلمتا المرور غير متطابقتين', 'error');
    const res = await api('auth&action=change_password', { method: 'POST', data });
    toast(res.message, res.success ? 'success' : 'error');
    if (res.success) e.target.reset();
};
async function sendOtp() {
    const res = await api('auth&action=resend_otp', { method: 'POST' });
    toast(res.message + (res.dev_otp ? ' (Dev: ' + res.dev_otp + ')' : ''), res.success ? 'success' : 'error', 6000);
    if (res.success) document.getElementById('otpBox').style.display = 'block';
}
async function verifyOtp() {
    const otp = document.getElementById('otpInput').value;
    const res = await api('auth&action=verify_otp', { method: 'POST', data: { code: otp } });
    toast(res.message, res.success ? 'success' : 'error');
    if (res.success) setTimeout(() => location.reload(), 1000);
}
async function deleteAccount() {
    if (confirm('حذف الحساب نهائيًا؟ هذا الإجراء لا رجعة فيه. ستفقد جميع البيانات والإعلانات.')) {
        const res = await api('auth&action=delete_account', { method: 'POST' });
        if (res.success) { toast('تم حذف الحساب', 'success'); setTimeout(() => location.href = 'index.php', 1500); }
        else toast(res.message, 'error');
    }
}
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
