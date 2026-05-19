<?php
require_once __DIR__ . '/../backend/config.php';
if (function_exists('secureSession')) { secureSession(); } else { session_start(); }
if (!isset($_SESSION['user_id'])) { header('Location: auth.php'); exit; }
define('PAGE_TITLE', 'العمولة | حراج اليمن');
require __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/icons.php';
?>
<div style="max-width:760px;margin:0 auto;">
    <div style="margin-bottom:var(--sp-5);">
        <h1 class="section-title">العمولة وتأكيد البيع</h1>
        <p class="section-subtitle">شفافية كاملة في رسوم المنصة</p>
    </div>
    <div class="surface-card" style="padding:var(--sp-6);margin-bottom:var(--sp-5);background:linear-gradient(135deg,rgba(193,154,62,.08),rgba(59,108,246,.05));">
        <div style="display:flex;align-items:center;gap:14px;margin-bottom:14px;">
            <div style="width:48px;height:48px;border-radius:14px;background:linear-gradient(135deg,var(--gold-400),var(--gold-600));display:grid;place-items:center;color:#1c1606;"><?= icon('dollar', ['size'=>24]) ?></div>
            <div>
                <h3 style="font-size:18px;font-weight:800;">عمولة المنصة: 1% فقط</h3>
                <p style="color:var(--text-soft);font-size:14px;">من قيمة كل عملية بيع تتم عبر المنصة</p>
            </div>
        </div>
        <p style="color:var(--text-soft);line-height:1.7;font-size:14px;">بعد إتمام البيع بنجاح، يُرجى تحويل قيمة العمولة (1% من سعر البيع) إلى الحساب البنكي المعتمد.</p>
    </div>

    <div class="surface-card" style="padding:var(--sp-6);margin-bottom:var(--sp-5);">
        <h3 style="font-size:16px;font-weight:700;margin-bottom:var(--sp-4);">معلومات التحويل البنكي</h3>
        <div style="display:grid;gap:12px;">
            <div style="display:flex;justify-content:space-between;padding:12px 16px;background:var(--bg-soft);border-radius:10px;"><span style="color:var(--muted);font-size:13px;">اسم البنك:</span><strong>بنك اليمن والكويت</strong></div>
            <div style="display:flex;justify-content:space-between;padding:12px 16px;background:var(--bg-soft);border-radius:10px;"><span style="color:var(--muted);font-size:13px;">اسم الحساب:</span><strong>حراج اليمن الفاخر</strong></div>
            <div style="display:flex;justify-content:space-between;padding:12px 16px;background:var(--bg-soft);border-radius:10px;"><span style="color:var(--muted);font-size:13px;">رقم الحساب:</span><code style="font-size:15px;font-weight:700;direction:ltr;">100-200-3001-4</code></div>
            <div style="display:flex;justify-content:space-between;padding:12px 16px;background:var(--bg-soft);border-radius:10px;"><span style="color:var(--muted);font-size:13px;">العملة:</span><strong>ريال يمني (YER)</strong></div>
        </div>
    </div>

    <div class="surface-card" style="padding:var(--sp-6);">
        <h3 style="font-size:16px;font-weight:700;margin-bottom:var(--sp-4);">تأكيد تحويل العمولة</h3>
        <form id="commissionForm">
            <div class="field"><label class="field-label">رقم الإعلان المباع *</label><input type="number" class="input" name="ad_id" required></div>
            <div class="field"><label class="field-label">سعر البيع (ر.ي) *</label><input type="number" class="input" name="sale_price" required></div>
            <div class="field"><label class="field-label">مبلغ العمولة المحوّل (1%) *</label><input type="number" class="input" name="amount" required step="0.01"></div>
            <div class="field"><label class="field-label">رقم/مرجع التحويل *</label><input type="text" class="input" name="reference" required></div>
            <div class="field"><label class="field-label">ملاحظات (اختياري)</label><textarea class="textarea" name="notes" rows="3"></textarea></div>
            <button type="submit" class="btn btn-primary btn-block btn-lg"><?= icon('check-circle', ['size'=>18]) ?> إرسال إثبات التحويل</button>
        </form>
    </div>
</div>
<script>
document.getElementById('commissionForm').onsubmit = async (e) => {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));
    const res = await api('commission&action=submit', { method: 'POST', data });
    if (res.success) { toast('تم استلام البيانات. سيتم المراجعة قريباً.', 'success'); e.target.reset(); }
    else toast(res.message || 'حدث خطأ', 'error');
};
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
