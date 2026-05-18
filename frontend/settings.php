<?php
require_once __DIR__ . '/../config.php';
if (!isset($_SESSION['user_id'])) { header('Location: auth.php'); exit; }
$me = getCurrentUser();
define('PAGE_TITLE', 'الإعدادات - ' . SITE_NAME);
include __DIR__ . '/includes/header.php';
?>
<style>
.settings-grid { display: grid; grid-template-columns: 240px 1fr; gap: 1.5rem; }
@media (max-width: 768px) { .settings-grid { grid-template-columns: 1fr; } }
.settings-nav { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 0.5rem; height: fit-content; }
.settings-nav button { display: block; width: 100%; text-align: right; background: none; border: none; padding: 0.7rem 1rem; border-radius: var(--radius-md); color: var(--text-main); font-weight: 700; cursor: pointer; transition: var(--transition); font-family: inherit; font-size: 0.92rem; margin-bottom: 4px; }
.settings-nav button:hover { background: var(--hover-bg); }
.settings-nav button.active { background: var(--primary); color: white; }
.tab-pane { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 1.5rem; }
.tab-pane h3 { margin: 0 0 0.5rem; color: var(--primary); font-weight: 900; }
.tab-pane .sub { color: var(--text-muted); font-size: 0.88rem; margin-bottom: 1.5rem; }
.avatar-uploader { display: flex; gap: 1rem; align-items: center; margin-bottom: 1.5rem; }
.avatar-preview { width: 90px; height: 90px; border-radius: 50%; overflow: hidden; border: 3px solid var(--accent); }
.avatar-preview img { width: 100%; height: 100%; object-fit: cover; }
</style>

<div class="container animate-fade-in">
    <h2 style="margin:0 0 1.5rem; color:var(--primary); font-weight:900;">⚙️ الإعدادات</h2>

    <div class="settings-grid">
        <nav class="settings-nav">
            <button class="active" onclick="showTab('profile', this)">👤 الملف الشخصي</button>
            <button onclick="showTab('password', this)">🔒 كلمة المرور</button>
            <button onclick="showTab('verify', this)">✓ تأكيد الجوال</button>
            <button onclick="showTab('appearance', this)">🎨 المظهر</button>
            <button onclick="showTab('danger', this)" style="color:var(--danger);">⚠️ حذف الحساب</button>
        </nav>

        <div>
            <!-- Profile Tab -->
            <div id="tab-profile" class="tab-pane">
                <h3>👤 الملف الشخصي</h3>
                <p class="sub">حدّث معلومات حسابك الأساسية</p>

                <div class="avatar-uploader">
                    <div class="avatar-preview"><img id="avatar-img" src="" alt=""></div>
                    <div>
                        <button class="btn-outline btn-sm" onclick="document.getElementById('avatar-input').click()">📷 تغيير الصورة</button>
                        <input type="file" id="avatar-input" accept="image/*" style="display:none;" onchange="uploadAvatar(event)">
                        <p style="font-size:0.75rem; color:var(--text-muted); margin-top:6px;">JPG / PNG / WebP (حتى 5MB)</p>
                    </div>
                </div>

                <form onsubmit="saveProfile(event)">
                    <div class="form-group">
                        <label>الاسم الكامل *</label>
                        <input type="text" id="p-name" value="<?= htmlspecialchars($me['name']) ?>" required minlength="3" maxlength="100">
                    </div>
                    <div class="form-group">
                        <label>📱 الجوال (لا يمكن تغييره)</label>
                        <input type="tel" value="<?= htmlspecialchars($me['phone']) ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label>📧 البريد الإلكتروني</label>
                        <input type="email" id="p-email" value="<?= htmlspecialchars($me['email'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>نبذة عني</label>
                        <textarea id="p-bio" rows="3" maxlength="500"><?= htmlspecialchars($me['bio'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="btn-primary">💾 حفظ التغييرات</button>
                </form>
            </div>

            <!-- Password Tab -->
            <div id="tab-password" class="tab-pane hidden">
                <h3>🔒 تغيير كلمة المرور</h3>
                <p class="sub">حافظ على أمان حسابك بتغيير كلمة المرور دورياً</p>

                <form onsubmit="changePassword(event)">
                    <div class="form-group">
                        <label>كلمة المرور الحالية</label>
                        <input type="password" id="curr-pwd" required>
                    </div>
                    <div class="form-group">
                        <label>كلمة المرور الجديدة (على الأقل 6 أحرف وأرقام)</label>
                        <input type="password" id="new-pwd" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label>تأكيد كلمة المرور الجديدة</label>
                        <input type="password" id="conf-pwd" required minlength="6">
                    </div>
                    <button type="submit" class="btn-primary">🔐 تغيير كلمة المرور</button>
                </form>
            </div>

            <!-- Verify Phone -->
            <div id="tab-verify" class="tab-pane hidden">
                <h3>✓ تأكيد رقم الجوال</h3>
                <p class="sub">سيتم إرسال رمز تحقق إلى رقم جوالك <strong><?= htmlspecialchars($me['phone']) ?></strong></p>

                <?php if ($me['isPhoneVerified']): ?>
                <div style="background:rgba(16,185,129,0.1); border:1px solid var(--success); border-radius:var(--radius-md); padding:1rem; color:var(--success); font-weight:800;">
                    ✓ تم تأكيد رقم جوالك بالفعل
                </div>
                <?php else: ?>
                <button class="btn-primary" onclick="sendVerifyOtp()">📤 إرسال رمز التحقق</button>
                <div id="verify-form" class="hidden" style="margin-top:1.5rem;">
                    <div class="form-group">
                        <label>الرمز المرسل (6 أرقام)</label>
                        <input type="text" id="otp-code" placeholder="123456" maxlength="6">
                    </div>
                    <button class="btn-primary" onclick="verifyOtp()">✓ تأكيد</button>
                    <button class="btn-outline" onclick="sendVerifyOtp()" style="margin-right:6px;">إعادة الإرسال</button>
                </div>
                <?php endif; ?>
            </div>

            <!-- Appearance -->
            <div id="tab-appearance" class="tab-pane hidden">
                <h3>🎨 المظهر</h3>
                <p class="sub">خصّص شكل الموقع كما تحب</p>

                <div style="display:flex; gap:1rem; flex-wrap:wrap;">
                    <button class="btn-outline" onclick="toggleTheme()">🌓 تبديل الوضع الداكن / الفاتح</button>
                </div>
            </div>

            <!-- Danger Zone -->
            <div id="tab-danger" class="tab-pane hidden">
                <h3 style="color:var(--danger);">⚠️ حذف الحساب</h3>
                <p class="sub">حذف حسابك إجراء لا يمكن التراجع عنه. سيتم أرشفة إعلاناتك.</p>

                <form onsubmit="deleteAccount(event)" style="background:rgba(225,29,72,0.05); border:1px solid var(--danger); border-radius:var(--radius-md); padding:1.25rem;">
                    <div class="form-group">
                        <label>أدخل كلمة المرور للتأكيد</label>
                        <input type="password" id="del-pwd" required>
                    </div>
                    <button type="submit" class="btn-danger">🗑️ حذف حسابي نهائياً</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/app.js"></script>
<script>
async function loadAvatar() {
    try {
        const r = await apiRequest('auth&action=me');
        document.getElementById('avatar-img').src = r.data.avatar_url;
    } catch(e) {}
}

function showTab(tab, btn) {
    ['profile','password','verify','appearance','danger'].forEach(t => {
        document.getElementById('tab-' + t).classList.add('hidden');
    });
    document.getElementById('tab-' + tab).classList.remove('hidden');
    document.querySelectorAll('.settings-nav button').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
}

async function saveProfile(e) {
    e.preventDefault();
    try {
        await apiRequest('auth&action=update_profile', 'POST', {
            name: document.getElementById('p-name').value,
            email: document.getElementById('p-email').value,
            bio: document.getElementById('p-bio').value
        });
        showToast('✓ تم الحفظ', 'success');
    } catch(e) {}
}

async function uploadAvatar(e) {
    const file = e.target.files[0];
    if (!file) return;
    if (file.size > 5*1024*1024) { showToast('حجم الصورة كبير', 'warning'); return; }
    try {
        const dataUrl = await resizeImage(file, 400, 0.85);
        const r = await apiRequest('auth&action=update_avatar', 'POST', { avatar: dataUrl });
        document.getElementById('avatar-img').src = r.data.avatar;
        showToast('✓ تم تحديث الصورة', 'success');
    } catch (e) {}
}

async function changePassword(e) {
    e.preventDefault();
    const newP = document.getElementById('new-pwd').value;
    const conf = document.getElementById('conf-pwd').value;
    if (newP !== conf) return showToast('كلمات المرور غير متطابقة', 'error');
    try {
        await apiRequest('auth&action=change_password', 'POST', {
            current_password: document.getElementById('curr-pwd').value,
            new_password: newP
        });
        showToast('✓ تم تغيير كلمة المرور', 'success');
        document.querySelector('#tab-password form').reset();
    } catch(e) {}
}

async function sendVerifyOtp() {
    try {
        await apiRequest('auth&action=resend_otp', 'POST', {});
        showToast('📩 تم إرسال الرمز', 'success');
        document.getElementById('verify-form').classList.remove('hidden');
    } catch(e) {}
}

async function verifyOtp() {
    const code = document.getElementById('otp-code').value;
    if (!code) return;
    try {
        await apiRequest('auth&action=verify_otp', 'POST', { code, purpose: 'verify_phone' });
        showToast('✓ تم التأكيد', 'success');
        setTimeout(() => location.reload(), 1000);
    } catch(e) {}
}

async function deleteAccount(e) {
    e.preventDefault();
    if (!await confirmModal('سيتم حذف حسابك نهائياً وأرشفة جميع إعلاناتك. هل أنت متأكد؟', '⚠️ تحذير نهائي')) return;
    try {
        await apiRequest('auth&action=delete_account', 'POST', {
            password: document.getElementById('del-pwd').value
        });
        showToast('تم حذف الحساب 😔', 'success');
        setTimeout(() => window.location.href = 'index.php', 1500);
    } catch(e) {}
}

document.addEventListener('DOMContentLoaded', loadAvatar);
</script>
</body></html>
