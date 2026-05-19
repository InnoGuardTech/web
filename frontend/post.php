<?php
require_once __DIR__ . '/../backend/config.php';
if (function_exists('secureSession')) { secureSession(); } else { session_start(); }
if (!isset($_SESSION['user_id'])) { header('Location: auth.php?return=post.php'); exit; }

$editId = (int)($_GET['edit'] ?? 0);
$editAd = null;
if ($editId) {
    $pdo = getDBConnection();
    $s = $pdo->prepare("SELECT * FROM ads WHERE id=:id AND userId=:u LIMIT 1");
    $s->execute([':id' => $editId, ':u' => $_SESSION['user_id']]);
    $editAd = $s->fetch(PDO::FETCH_ASSOC);
    if (!$editAd) { header('Location: my_ads.php'); exit; }
}

define('PAGE_TITLE', ($editAd ? 'تعديل الإعلان' : 'أضف إعلانًا جديدًا') . ' | حراج اليمن');
require __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/icons.php';

$specs = $editAd ? (json_decode($editAd['specifications'] ?? '{}', true) ?: []) : [];
$existingImages = $editAd ? (json_decode($editAd['images'] ?? '[]', true) ?: []) : [];
?>
<div style="max-width:780px;margin:0 auto;">
    <div style="margin-bottom:var(--sp-5);">
        <h1 class="section-title"><?= $editAd ? 'تعديل الإعلان' : 'أضف إعلانًا جديدًا' ?></h1>
        <p class="section-subtitle">املأ التفاصيل بدقة لزيادة فرص البيع</p>
    </div>

    <form id="adForm" class="surface-card" style="padding:var(--sp-6);">
        <input type="hidden" name="id" value="<?= (int)($editAd['id'] ?? 0) ?>">

        <div class="field">
            <label class="field-label">الفئة *</label>
            <select class="select" name="category" id="categorySelect" required>
                <option value="">اختر فئة</option>
                <?php $cats = ['cars'=>'سيارات','realestate'=>'عقارات','electronics'=>'إلكترونيات','furniture'=>'أثاث','jobs'=>'وظائف','services'=>'خدمات','livestock'=>'حيوانات','other'=>'أخرى'];
                foreach ($cats as $k=>$v): ?><option value="<?= $k ?>" <?= ($editAd['category'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label class="field-label">عنوان الإعلان *</label>
            <input type="text" class="input" name="title" required minlength="5" maxlength="100" placeholder="مثال: تويوتا كامري 2020 فل كامل" value="<?= htmlspecialchars($editAd['title'] ?? '') ?>">
            <div class="field-hint">عنوان واضح يجذب المشترين (5-100 حرف)</div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div class="field">
                <label class="field-label">السعر (ر.ي) *</label>
                <input type="number" class="input" name="price" required min="0" placeholder="0" value="<?= htmlspecialchars($editAd['price'] ?? '') ?>">
            </div>
            <div class="field">
                <label class="field-label">المدينة *</label>
                <select class="select" name="city" required>
                    <option value="">اختر مدينة</option>
                    <?php foreach (getCities() as $c): ?><option value="<?= htmlspecialchars($c) ?>" <?= ($editAd['city'] ?? '') === $c ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option><?php endforeach; ?>
                </select>
            </div>
        </div>

        <div id="carsSpecs" class="dyn-specs" style="display:none;">
            <h3 style="font-size:15px;font-weight:700;margin:var(--sp-4) 0 var(--sp-3);">تفاصيل السيارة</h3>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;">
                <div class="field" style="margin:0;"><label class="field-label">الماركة</label><input type="text" class="input" name="spec_brand" placeholder="تويوتا" value="<?= htmlspecialchars($specs['brand'] ?? '') ?>"></div>
                <div class="field" style="margin:0;"><label class="field-label">الموديل</label><input type="text" class="input" name="spec_model" placeholder="كامري" value="<?= htmlspecialchars($specs['model'] ?? '') ?>"></div>
                <div class="field" style="margin:0;"><label class="field-label">سنة الصنع</label><input type="number" class="input" name="spec_year" min="1970" max="2026" placeholder="2020" value="<?= htmlspecialchars($specs['year'] ?? '') ?>"></div>
                <div class="field" style="margin:0;"><label class="field-label">العداد (كم)</label><input type="number" class="input" name="spec_mileage" placeholder="50000" value="<?= htmlspecialchars($specs['mileage'] ?? '') ?>"></div>
                <div class="field" style="margin:0;"><label class="field-label">ناقل الحركة</label><select class="select" name="spec_transmission"><option value="">—</option><option value="أوتوماتيك" <?= ($specs['transmission'] ?? '')==='أوتوماتيك'?'selected':'' ?>>أوتوماتيك</option><option value="عادي" <?= ($specs['transmission'] ?? '')==='عادي'?'selected':'' ?>>عادي</option></select></div>
                <div class="field" style="margin:0;"><label class="field-label">اللون</label><input type="text" class="input" name="spec_color" placeholder="أبيض" value="<?= htmlspecialchars($specs['color'] ?? '') ?>"></div>
            </div>
        </div>

        <div id="realestateSpecs" class="dyn-specs" style="display:none;">
            <h3 style="font-size:15px;font-weight:700;margin:var(--sp-4) 0 var(--sp-3);">تفاصيل العقار</h3>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;">
                <div class="field" style="margin:0;"><label class="field-label">نوع العقار</label><select class="select" name="spec_property_type"><option value="">—</option><?php foreach (['شقة','فيلا','أرض','محل','مزرعة','عمارة'] as $t): ?><option value="<?= $t ?>" <?= ($specs['property_type'] ?? '')===$t?'selected':'' ?>><?= $t ?></option><?php endforeach; ?></select></div>
                <div class="field" style="margin:0;"><label class="field-label">المساحة (م²)</label><input type="number" class="input" name="spec_area" placeholder="200" value="<?= htmlspecialchars($specs['area'] ?? '') ?>"></div>
                <div class="field" style="margin:0;"><label class="field-label">عدد الغرف</label><input type="number" class="input" name="spec_rooms" min="0" max="20" placeholder="3" value="<?= htmlspecialchars($specs['rooms'] ?? '') ?>"></div>
                <div class="field" style="margin:0;"><label class="field-label">عدد الحمامات</label><input type="number" class="input" name="spec_bathrooms" min="0" max="10" placeholder="2" value="<?= htmlspecialchars($specs['bathrooms'] ?? '') ?>"></div>
            </div>
        </div>

        <div class="field">
            <label class="field-label">الوصف *</label>
            <textarea class="textarea" name="description" required minlength="20" maxlength="3000" rows="6" placeholder="اكتب وصفًا تفصيليًا..."><?= htmlspecialchars($editAd['description'] ?? '') ?></textarea>
            <div class="field-hint">20-3000 حرف. كلما كان الوصف أوضح، زادت ثقة المشتري.</div>
        </div>

        <div class="field">
            <label class="field-label">الصور (حتى 8) *</label>
            <div id="dropzone" style="border:2px dashed var(--line);border-radius:var(--r-lg);padding:30px;text-align:center;cursor:pointer;transition:all .2s;background:var(--bg-soft);">
                <div style="color:var(--muted);">
                    <?= icon('upload', ['size'=>36]) ?>
                    <div style="margin-top:8px;font-weight:600;color:var(--text);">اسحب الصور هنا أو انقر للاختيار</div>
                    <div style="font-size:12px;margin-top:4px;">JPG, PNG, WebP — حد أقصى 5MB لكل صورة</div>
                </div>
                <input type="file" id="fileInput" accept="image/*" multiple style="display:none;">
            </div>
            <div id="imagesPreview" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(110px,1fr));gap:8px;margin-top:12px;"></div>
        </div>

        <div style="display:flex;gap:10px;margin-top:var(--sp-5);">
            <a href="<?= $editAd ? 'my_ads.php' : 'index.php' ?>" class="btn btn-ghost">إلغاء</a>
            <button type="submit" class="btn btn-primary btn-lg" style="flex:1;"><?= $editAd ? 'حفظ التعديلات' : 'نشر الإعلان' ?></button>
        </div>
    </form>
</div>

<script>
let images = <?= json_encode($existingImages) ?>;
function syncCategoryFields() {
    const cat = document.getElementById('categorySelect').value;
    document.querySelectorAll('.dyn-specs').forEach(el => el.style.display = 'none');
    const target = document.getElementById(cat + 'Specs');
    if (target) target.style.display = 'block';
}
document.getElementById('categorySelect').onchange = syncCategoryFields;
syncCategoryFields();
const dz = document.getElementById('dropzone'), fileInput = document.getElementById('fileInput');
dz.onclick = () => fileInput.click();
dz.ondragover = e => { e.preventDefault(); dz.style.borderColor = 'var(--brand-500)'; dz.style.background = 'rgba(59,108,246,.05)'; };
dz.ondragleave = () => { dz.style.borderColor = ''; dz.style.background = 'var(--bg-soft)'; };
dz.ondrop = e => { e.preventDefault(); dz.style.borderColor = ''; dz.style.background = 'var(--bg-soft)'; handleFiles(e.dataTransfer.files); };
fileInput.onchange = e => handleFiles(e.target.files);
function handleFiles(files) {
    Array.from(files).forEach(f => {
        if (!f.type.startsWith('image/')) return toast('ملف غير مدعوم: ' + f.name, 'error');
        if (f.size > 5*1024*1024) return toast('الصورة كبيرة جداً (>5MB)', 'error');
        if (images.length >= 8) return toast('الحد الأقصى 8 صور', 'warning');
        const reader = new FileReader();
        reader.onload = e => { images.push(e.target.result); renderImages(); };
        reader.readAsDataURL(f);
    });
}
function renderImages() {
    document.getElementById('imagesPreview').innerHTML = images.map((src, i) => `<div style="position:relative;aspect-ratio:1;border-radius:var(--r-md);overflow:hidden;border:1px solid var(--line);"><img src="${src}" style="width:100%;height:100%;object-fit:cover;"><button type="button" onclick="removeImage(${i})" style="position:absolute;top:4px;left:4px;width:26px;height:26px;border-radius:50%;background:rgba(0,0,0,.7);color:#fff;font-size:14px;line-height:1;">×</button>${i===0?'<div style="position:absolute;bottom:4px;right:4px;background:var(--gold-500);color:#1c1606;font-size:10px;font-weight:700;padding:2px 6px;border-radius:4px;">رئيسية</div>':''}</div>`).join('');
}
function removeImage(i) { images.splice(i, 1); renderImages(); }
renderImages();
document.getElementById('adForm').onsubmit = async (e) => {
    e.preventDefault();
    if (images.length === 0) return toast('أضف صورة واحدة على الأقل', 'warning');
    const fd = new FormData(e.target);
    const data = Object.fromEntries(fd);
    const specs = {};
    for (const k in data) if (k.startsWith('spec_') && data[k]) specs[k.replace('spec_','')] = data[k];
    const payload = {
        id: parseInt(data.id || 0), category: data.category, title: data.title,
        price: parseFloat(data.price) || 0, city: data.city, description: data.description,
        specs, specifications: specs, images
    };
    const btn = e.target.querySelector('button[type=submit]');
    btn.disabled = true;
    const oldHtml = btn.innerHTML;
    btn.innerHTML = 'جارٍ الحفظ...';
    const action = payload.id ? 'update' : 'create';
    const res = await api('ads&action=' + action, { method: 'POST', data: payload });
    if (res.success) {
        toast(payload.id ? 'تم حفظ التعديلات' : 'تم نشر الإعلان بنجاح', 'success');
        const adId = res.adId || res.data?.adId || res.ad_id || payload.id;
        setTimeout(() => location.href = res.url || ('ad.php?id=' + adId), 800);
    } else { toast(res.message || 'حدث خطأ', 'error'); btn.disabled = false; btn.innerHTML = oldHtml; }
};
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
