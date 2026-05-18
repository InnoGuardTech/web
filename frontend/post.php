<?php
require_once __DIR__ . '/../config.php';
if (!isset($_SESSION['user_id'])) { header('Location: auth.php'); exit; }

$editMode = isset($_GET['edit']) && (int)$_GET['edit'] > 0;
$editId = $editMode ? (int)$_GET['edit'] : 0;

define('PAGE_TITLE', ($editMode ? 'تعديل إعلان' : 'نشر إعلان جديد') . ' - ' . SITE_NAME);
define('HIDE_SEARCH', true);
include __DIR__ . '/includes/header.php';
?>

<style>
.post-container {
    max-width: 880px;
    margin: 2rem auto;
    padding: 0 1rem;
}
.post-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-xl);
    padding: 2rem;
    box-shadow: var(--shadow-md);
}
.post-card h1 {
    margin: 0 0 0.5rem;
    color: var(--primary);
    font-weight: 900;
    font-size: 1.75rem;
}
.post-card .sub { color: var(--text-muted); margin-bottom: 2rem; font-size: 0.92rem; }
.section-title {
    font-size: 1.05rem;
    font-weight: 900;
    color: var(--primary);
    margin: 1.5rem 0 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid var(--accent);
}
.dropzone {
    border: 2px dashed var(--border-color);
    border-radius: var(--radius-lg);
    padding: 2.5rem 1rem;
    text-align: center;
    cursor: pointer;
    transition: var(--transition);
    background: var(--bg-color);
}
.dropzone:hover, .dropzone.dragover {
    border-color: var(--primary);
    background: var(--primary-light);
}
.dropzone-icon { font-size: 3rem; opacity: 0.5; margin-bottom: 0.5rem; }
.images-preview {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 0.75rem;
    margin-top: 1rem;
}
.preview-item {
    position: relative;
    aspect-ratio: 1;
    border-radius: var(--radius-md);
    overflow: hidden;
    border: 1px solid var(--border-color);
}
.preview-item img { width: 100%; height: 100%; object-fit: cover; }
.preview-item .remove {
    position: absolute;
    top: 4px;
    left: 4px;
    background: var(--danger);
    color: white;
    border: none;
    border-radius: 50%;
    width: 28px;
    height: 28px;
    cursor: pointer;
    font-weight: 900;
    font-size: 0.9rem;
}
.preview-item.main::before {
    content: '⭐ الرئيسية';
    position: absolute;
    bottom: 4px;
    right: 4px;
    background: var(--accent);
    color: var(--primary);
    padding: 2px 8px;
    border-radius: var(--radius-full);
    font-size: 0.7rem;
    font-weight: 900;
}
</style>

<div class="post-container animate-fade-in">
    <div class="post-card">
        <h1 id="page-title"><?= $editMode ? '✏️ تعديل الإعلان' : '✨ نشر إعلان جديد' ?></h1>
        <p class="sub"><?= $editMode ? 'قم بتعديل بيانات إعلانك أدناه' : 'املأ التفاصيل أدناه لنشر إعلانك في حراج اليمن' ?></p>

        <form id="post-form" onsubmit="handleSubmit(event)">
            <input type="hidden" id="edit-id" value="<?= $editId ?>">

            <div class="section-title">📝 المعلومات الأساسية</div>

            <div class="form-group">
                <label>عنوان الإعلان *</label>
                <input type="text" id="title" placeholder="مثال: تويوتا كامري 2020 فل كامل" required minlength="5" maxlength="255">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>القسم *</label>
                    <select id="category" required onchange="toggleSpecs()">
                        <option value="">-- اختر القسم --</option>
                        <option value="cars">🚗 حراج السيارات</option>
                        <option value="realestate">🏠 عقارات</option>
                        <option value="electronics">📱 أجهزة وإلكترونيات</option>
                        <option value="livestock">🐏 مواشي وحيوانات</option>
                        <option value="furniture">🪑 أثاث ومفروشات</option>
                        <option value="jobs">💼 وظائف</option>
                        <option value="services">🔧 خدمات</option>
                        <option value="other">📦 أخرى</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>المدينة *</label>
                    <select id="city" required>
                        <option value="">-- اختر المدينة --</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>السعر (بالريال اليمني)</label>
                <input type="number" id="price" placeholder="اتركه فارغاً للتفاوض" min="0" step="1000">
                <small style="color:var(--text-muted); font-size:0.78rem;">اتركه فارغاً ليظهر "السعر عند التواصل"</small>
            </div>

            <!-- Cars Specs -->
            <div id="cars-specs" class="hidden">
                <div class="section-title">🚗 مواصفات السيارة</div>
                <div class="form-row">
                    <div class="form-group">
                        <label>الماركة</label>
                        <select id="carBrand">
                            <option value="">--</option>
                            <option>تويوتا</option><option>هيونداي</option><option>مرسيدس</option>
                            <option>لكزس</option><option>نيسان</option><option>فورد</option>
                            <option>كيا</option><option>شيفروليه</option><option>BMW</option>
                            <option>هوندا</option><option>مازدا</option><option>سوزوكي</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>السنة</label>
                        <select id="carYear">
                            <option value="">--</option>
                            <?php for($y = 2026; $y >= 1990; $y--): ?>
                                <option value="<?= $y ?>"><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>ناقل الحركة</label>
                        <select id="carTransmission">
                            <option value="">--</option>
                            <option>أوتوماتيك</option><option>عادي</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>الممشى (كم)</label>
                        <input type="number" id="carMileage" placeholder="مثال: 45000" min="0">
                    </div>
                </div>
            </div>

            <!-- Real Estate Specs -->
            <div id="realestate-specs" class="hidden">
                <div class="section-title">🏠 مواصفات العقار</div>
                <div class="form-row">
                    <div class="form-group">
                        <label>النوع</label>
                        <select id="propertyType">
                            <option value="">--</option>
                            <option>شقة</option><option>فيلا</option><option>أرض</option>
                            <option>محل تجاري</option><option>عمارة</option><option>مكتب</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>عدد الغرف</label>
                        <select id="propertyRooms">
                            <option value="">--</option>
                            <?php for($i=1;$i<=10;$i++) echo "<option>$i</option>"; ?>
                            <option>+10</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>نوع العقد</label>
                    <select id="propertyContract">
                        <option value="">--</option>
                        <option>للبيع</option><option>للإيجار</option><option>للشراكة</option>
                    </select>
                </div>
            </div>

            <div class="section-title">📄 الوصف</div>
            <div class="form-group">
                <textarea id="description" rows="5" placeholder="اكتب وصفاً مفصلاً للإعلان..." required minlength="10" maxlength="5000"></textarea>
                <small style="color:var(--text-muted); font-size:0.78rem;"><span id="desc-counter">0</span>/5000 حرف</small>
            </div>

            <div class="section-title">📷 الصور (حتى <?= (int)env('MAX_IMAGES_PER_AD', 8) ?> صور)</div>
            <div class="dropzone" id="dropzone" onclick="document.getElementById('file-input').click()">
                <div class="dropzone-icon">📷</div>
                <p style="font-weight:800; margin: 0 0 0.25rem;">اضغط أو اسحب الصور هنا</p>
                <small style="color:var(--text-muted);">JPG, PNG, WebP (حتى 5MB لكل صورة)</small>
                <input type="file" id="file-input" multiple accept="image/*" style="display:none;" onchange="handleFiles(event)">
            </div>
            <div class="images-preview" id="images-preview"></div>

            <div class="section-title">📍 الموقع (اختياري)</div>
            <div class="form-group">
                <label>اسم الحي / المنطقة</label>
                <input type="text" id="locationName" placeholder="مثال: حي الصافية، شارع تعز">
            </div>
            <button type="button" onclick="useCurrentLocation()" class="btn-outline btn-sm">📍 استخدام موقعي الحالي</button>
            <input type="hidden" id="latitude">
            <input type="hidden" id="longitude">
            <div id="location-status" style="margin-top:0.5rem; font-size:0.85rem; color:var(--text-muted);"></div>

            <div style="margin-top: 2rem; display: flex; gap: 0.75rem; flex-wrap: wrap;">
                <button type="submit" class="btn-primary" id="submit-btn">
                    <?= $editMode ? '💾 حفظ التعديلات' : '🚀 نشر الإعلان' ?>
                </button>
                <a href="index.php" class="btn-outline">إلغاء</a>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/app.js"></script>
<script>
const MAX_IMAGES = <?= (int)env('MAX_IMAGES_PER_AD', 8) ?>;
const MAX_SIZE = <?= (int)env('MAX_UPLOAD_SIZE', 5242880) ?>;
let images = []; // {dataUrl, name}
let isEditMode = <?= $editMode ? 'true' : 'false' ?>;

async function init() {
    // Load cities
    try {
        const r = await apiRequest('cities');
        const sel = document.getElementById('city');
        r.data.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c; opt.textContent = c;
            sel.appendChild(opt);
        });
    } catch (e) {}

    // Description counter
    const desc = document.getElementById('description');
    const counter = document.getElementById('desc-counter');
    desc.addEventListener('input', () => counter.textContent = desc.value.length);

    // If edit mode, load data
    if (isEditMode) {
        await loadEditData();
    }

    // Dropzone events
    const dz = document.getElementById('dropzone');
    ['dragover','dragenter'].forEach(ev => dz.addEventListener(ev, e => { e.preventDefault(); dz.classList.add('dragover'); }));
    ['dragleave','dragend','drop'].forEach(ev => dz.addEventListener(ev, e => { e.preventDefault(); dz.classList.remove('dragover'); }));
    dz.addEventListener('drop', async e => {
        e.preventDefault();
        const files = Array.from(e.dataTransfer.files);
        await processFiles(files);
    });
}

async function loadEditData() {
    try {
        const id = document.getElementById('edit-id').value;
        const r = await apiRequest('ads&action=edit_data&id=' + id);
        const ad = r.data;
        document.getElementById('title').value = ad.title;
        document.getElementById('description').value = ad.description;
        document.getElementById('category').value = ad.category;
        document.getElementById('city').value = ad.city;
        document.getElementById('price').value = ad.price || '';
        document.getElementById('locationName').value = ad.locationName || '';
        document.getElementById('latitude').value = ad.latitude || '';
        document.getElementById('longitude').value = ad.longitude || '';

        if (ad.carBrand) document.getElementById('carBrand').value = ad.carBrand;
        if (ad.carYear) document.getElementById('carYear').value = ad.carYear;
        if (ad.carTransmission) document.getElementById('carTransmission').value = ad.carTransmission;
        if (ad.carMileage) document.getElementById('carMileage').value = ad.carMileage;
        if (ad.propertyType) document.getElementById('propertyType').value = ad.propertyType;
        if (ad.propertyRooms) document.getElementById('propertyRooms').value = ad.propertyRooms;
        if (ad.propertyContract) document.getElementById('propertyContract').value = ad.propertyContract;

        toggleSpecs();

        // Load existing images (as paths)
        if (ad.imagesUrls && ad.imagesUrls.length) {
            images = ad.imagesUrls.map((url, i) => ({ dataUrl: ad.images[i], displayUrl: url, name: 'existing_' + i }));
            renderPreviews();
        }

        document.getElementById('desc-counter').textContent = ad.description.length;
    } catch (e) {
        showToast('فشل تحميل بيانات الإعلان', 'error');
        setTimeout(() => window.location.href = 'my_ads.php', 1500);
    }
}

function toggleSpecs() {
    const cat = document.getElementById('category').value;
    document.getElementById('cars-specs').classList.toggle('hidden', cat !== 'cars');
    document.getElementById('realestate-specs').classList.toggle('hidden', cat !== 'realestate');
}

async function handleFiles(e) {
    await processFiles(Array.from(e.target.files));
    e.target.value = '';
}

async function processFiles(files) {
    for (const file of files) {
        if (images.length >= MAX_IMAGES) {
            showToast(`الحد الأقصى ${MAX_IMAGES} صور`, 'warning');
            break;
        }
        if (!file.type.startsWith('image/')) {
            showToast('فقط الصور مسموحة', 'warning');
            continue;
        }
        if (file.size > MAX_SIZE) {
            showToast(`${file.name}: حجم الصورة كبير`, 'warning');
            continue;
        }
        try {
            const dataUrl = await resizeImage(file, 1400, 0.85);
            images.push({ dataUrl, displayUrl: dataUrl, name: file.name });
        } catch (e) {
            showToast('فشل قراءة الصورة', 'error');
        }
    }
    renderPreviews();
}

function renderPreviews() {
    const c = document.getElementById('images-preview');
    c.innerHTML = '';
    images.forEach((img, idx) => {
        const div = document.createElement('div');
        div.className = 'preview-item' + (idx === 0 ? ' main' : '');
        div.innerHTML = `
            <img src="${img.displayUrl}" alt="">
            <button type="button" class="remove" onclick="removeImage(${idx})">×</button>
        `;
        c.appendChild(div);
    });
}

function removeImage(idx) {
    images.splice(idx, 1);
    renderPreviews();
}

function useCurrentLocation() {
    const status = document.getElementById('location-status');
    if (!navigator.geolocation) {
        status.textContent = '❌ المتصفح لا يدعم تحديد الموقع';
        return;
    }
    status.textContent = '⏳ جاري تحديد موقعك...';
    navigator.geolocation.getCurrentPosition(pos => {
        document.getElementById('latitude').value = pos.coords.latitude.toFixed(7);
        document.getElementById('longitude').value = pos.coords.longitude.toFixed(7);
        status.innerHTML = `✅ تم تحديد الموقع (${pos.coords.latitude.toFixed(4)}, ${pos.coords.longitude.toFixed(4)})`;
    }, err => {
        status.textContent = '❌ تعذّر تحديد الموقع';
    });
}

async function handleSubmit(e) {
    e.preventDefault();
    const btn = document.getElementById('submit-btn');
    btn.disabled = true; btn.textContent = '⏳ جاري الحفظ...';

    const data = {
        id: document.getElementById('edit-id').value || undefined,
        title: document.getElementById('title').value,
        description: document.getElementById('description').value,
        category: document.getElementById('category').value,
        city: document.getElementById('city').value,
        price: document.getElementById('price').value || null,
        carBrand: document.getElementById('carBrand').value,
        carYear: document.getElementById('carYear').value,
        carTransmission: document.getElementById('carTransmission').value,
        carMileage: document.getElementById('carMileage').value,
        propertyType: document.getElementById('propertyType').value,
        propertyRooms: document.getElementById('propertyRooms').value,
        propertyContract: document.getElementById('propertyContract').value,
        location_name: document.getElementById('locationName').value,
        latitude: document.getElementById('latitude').value || null,
        longitude: document.getElementById('longitude').value || null,
        images: images.map(i => i.dataUrl)
    };

    try {
        const endpoint = isEditMode ? 'ads&action=update' : 'ads&action=create';
        const r = await apiRequest(endpoint, 'POST', data);
        showToast(r.message, 'success');
        setTimeout(() => {
            window.location.href = `ad.php?id=${r.data.id}${r.data.slug ? '&slug='+encodeURIComponent(r.data.slug) : ''}`;
        }, 800);
    } catch (e) {
        btn.disabled = false;
        btn.textContent = isEditMode ? '💾 حفظ التعديلات' : '🚀 نشر الإعلان';
    }
}

document.addEventListener('DOMContentLoaded', init);
</script>
</body>
</html>
