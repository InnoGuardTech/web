<?php
require_once __DIR__ . '/../config.php';
if (!isset($_SESSION['user_id'])) { header('Location: auth.php'); exit; }
define('PAGE_TITLE', 'دفع العمولة - ' . SITE_NAME);
include __DIR__ . '/includes/header.php';
?>
<style>
.bank-card { background: rgba(13,148,136,0.05); border: 1px solid rgba(13,148,136,0.2); padding: 1.1rem; border-radius: var(--radius-lg); margin-bottom: 0.85rem; display: flex; justify-content: space-between; align-items: center; }
.bank-name { font-weight: 900; font-size: 1.05rem; color: var(--primary); }
.bank-account { color: var(--secondary); font-size: 1.05rem; font-weight: 800; font-family: monospace; }
.transfer-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 0.85rem; margin-bottom: 0.6rem; display: flex; justify-content: space-between; align-items: center; gap: 0.5rem; flex-wrap: wrap; }
</style>

<div class="container animate-fade-in" style="max-width:920px;">
    <h2 style="margin:0 0 0.5rem; color:var(--primary); font-weight:900;">💰 عمولة الموقع 1%</h2>
    <p style="color:var(--text-muted); margin-bottom:1.5rem;">عمولة الموقع هي 1% من قيمة السلعة المباعة. أرسلها على أحد البنوك أدناه ثم أرفق إثبات التحويل.</p>

    <div class="premium-card" style="margin-bottom:1.5rem;">
        <h3 style="margin:0 0 1rem; color:var(--primary); font-weight:900;">🏦 الحسابات البنكية</h3>

        <div class="bank-card">
            <div>
                <div class="bank-name">🏦 بنك الكريمي الإسلامي</div>
                <div class="bank-account">123456789</div>
            </div>
            <button class="btn-outline btn-sm" onclick="copyToClipboard('123456789')">📋 نسخ</button>
        </div>

        <div class="bank-card">
            <div>
                <div class="bank-name">🏛️ بنك التضامن الإسلامي</div>
                <div class="bank-account">000-111-2222</div>
            </div>
            <button class="btn-outline btn-sm" onclick="copyToClipboard('000-111-2222')">📋 نسخ</button>
        </div>

        <div class="bank-card">
            <div>
                <div class="bank-name">🏪 بنك القاسمي</div>
                <div class="bank-account">555-666-7777</div>
            </div>
            <button class="btn-outline btn-sm" onclick="copyToClipboard('555-666-7777')">📋 نسخ</button>
        </div>
    </div>

    <div class="premium-card" style="margin-bottom:1.5rem;">
        <h3 style="margin:0 0 1rem; color:var(--primary); font-weight:900;">📋 إرسال إثبات تحويل</h3>

        <form onsubmit="submitTransfer(event)">
            <div class="form-row">
                <div class="form-group">
                    <label>المبلغ (ر.ي) *</label>
                    <input type="number" id="amount" required min="1" placeholder="مثال: 250000">
                </div>
                <div class="form-group">
                    <label>تاريخ التحويل *</label>
                    <input type="date" id="transfer-date" required max="<?= date('Y-m-d') ?>">
                </div>
            </div>
            <div class="form-group">
                <label>اسم البنك *</label>
                <select id="bank-name" required>
                    <option value="">-- اختر --</option>
                    <option>بنك الكريمي الإسلامي</option>
                    <option>بنك التضامن الإسلامي</option>
                    <option>بنك القاسمي</option>
                </select>
            </div>
            <div class="form-group">
                <label>رقم الإعلان المرتبط (اختياري)</label>
                <input type="number" id="ad-id" placeholder="مثال: 5">
            </div>
            <div class="form-group">
                <label>ملاحظات (اختياري)</label>
                <textarea id="notes" rows="2"></textarea>
            </div>
            <div class="form-group">
                <label>📷 صورة إثبات التحويل *</label>
                <input type="file" id="proof-file" accept="image/*" required>
                <small style="color:var(--text-muted); font-size:0.78rem;">صورة سند التحويل من تطبيق البنك (JPG/PNG/PDF حتى 5MB)</small>
            </div>
            <button type="submit" class="btn-primary">📤 إرسال الإثبات</button>
        </form>
    </div>

    <div class="premium-card">
        <h3 style="margin:0 0 1rem; color:var(--primary); font-weight:900;">📋 تحويلاتي السابقة</h3>
        <div id="transfers-list"><div style="text-align:center; padding:2rem; color:var(--text-muted);">جاري التحميل...</div></div>
    </div>

    <div class="warning-card" style="background:rgba(245,158,11,0.08); border:1px solid rgba(245,158,11,0.3); border-radius:var(--radius-md); padding:1rem; color:#92400E; margin-top:1.5rem; font-weight:700;">
        ⚠️ بعد إرسال الإثبات، يرجى التواصل مع الإدارة على واتساب: <strong><?= htmlspecialchars(env('ADMIN_WHATSAPP', '777000000')) ?></strong>
    </div>
</div>

<script src="assets/js/app.js"></script>
<script>
async function loadTransfers() {
    try {
        const r = await apiRequest('commission&action=my_transfers');
        const list = document.getElementById('transfers-list');
        if (!r.data.length) {
            list.innerHTML = '<div style="text-align:center; padding:2rem; color:var(--text-muted);">لا توجد تحويلات سابقة</div>';
            return;
        }
        list.innerHTML = r.data.map(t => `
            <div class="transfer-card">
                <div>
                    <strong>${t.amount}</strong> · ${escapeHtml(t.bankName)} · ${escapeHtml(t.transferDate || '')}
                    ${t.adTitle ? `<div style="font-size:0.78rem; color:var(--text-muted); margin-top:2px;">إعلان: ${escapeHtml(t.adTitle)}</div>` : ''}
                </div>
                <div style="display:flex; gap:0.5rem; align-items:center;">
                    <span class="status-badge status-${t.status}">${t.statusLabel}</span>
                    ${t.proofImage ? `<a href="${t.proofImage}" target="_blank" class="btn-outline btn-sm">👁️ السند</a>` : ''}
                </div>
            </div>
        `).join('');
    } catch(e) {}
}

async function submitTransfer(e) {
    e.preventDefault();
    const file = document.getElementById('proof-file').files[0];
    if (!file) return showToast('أرفق صورة الإثبات', 'warning');
    if (file.size > 5*1024*1024) return showToast('حجم الصورة كبير', 'warning');

    try {
        const dataUrl = await resizeImage(file, 1400, 0.85);
        await apiRequest('commission&action=submit', 'POST', {
            amount: document.getElementById('amount').value,
            bank_name: document.getElementById('bank-name').value,
            transfer_date: document.getElementById('transfer-date').value,
            ad_id: document.getElementById('ad-id').value || 0,
            notes: document.getElementById('notes').value,
            proof_image: dataUrl
        });
        showToast('✅ تم الإرسال بنجاح', 'success');
        document.querySelector('form').reset();
        loadTransfers();
    } catch(e) {}
}

document.addEventListener('DOMContentLoaded', loadTransfers);
</script>
</body></html>
