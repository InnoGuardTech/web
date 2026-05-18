<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: auth.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>أضف إعلانك - حراج الفاخر</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .post-container {
            max-width: 800px;
            margin: 3rem auto;
            padding: 2rem;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        @media (max-width: 600px) {
            .form-grid { grid-template-columns: 1fr; }
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
        .full-width {
            grid-column: 1 / -1;
        }
        textarea.input-premium {
            resize: vertical;
            min-height: 120px;
        }
        .dynamic-block {
            background: rgba(13, 148, 136, 0.05);
            border: 1px solid rgba(13, 148, 136, 0.2);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .hidden { display: none; }
        
        .dropzone {
            border: 2px dashed var(--border-color);
            border-radius: var(--radius-lg);
            padding: 2rem;
            text-align: center;
            background: var(--bg-color);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .dropzone:hover {
            border-color: var(--primary);
            background: rgba(5, 150, 105, 0.05);
        }
        .preview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 10px;
            margin-top: 1rem;
        }
        .preview-item {
            position: relative;
            aspect-ratio: 1;
            border-radius: 0.5rem;
            overflow: hidden;
        }
        .preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .preview-item button {
            position: absolute;
            top: 5px;
            left: 5px;
            background: rgba(239, 68, 68, 0.9);
            color: white;
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            cursor: pointer;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <header class="glass-header">
        <div class="header-container">
            <a href="index.php" class="header-logo">
                <span class="header-logo-badge">حراج</span>
                <span>اليمن</span>
            </a>
            
            <div class="header-search" style="visibility:hidden;">
                <input type="text" placeholder="البحث...">
            </div>
            
            <div class="header-actions">
                <button onclick="toggleTheme()" style="background:none; border:none; cursor:pointer; font-size:1.1rem; color:white;">🌓</button>
                <a href="index.php" style="color:white; font-weight:bold; text-decoration:none; font-size:0.85rem;">الرئيسية</a>
            </div>
        </div>
    </header>

    <main class="post-container premium-card animate-fade-in">
        <div style="margin-bottom: 2rem;">
            <h1 style="margin: 0 0 0.5rem 0;">نشر إعلان جديد 🚀</h1>
            <p style="margin: 0; color: var(--text-muted); font-size: 0.875rem;">يرجى كتابة مواصفات السلعة وصورها بصدق.</p>
        </div>

        <form id="ad-form" onsubmit="submitAd(event)">
            <div class="form-grid">
                <div class="form-group full-width">
                    <label>عنوان الإعلان *</label>
                    <input type="text" id="title" class="input-premium" required placeholder="مثال: تويوتا كامري 2024 فل كامل...">
                </div>

                <div class="form-group">
                    <label>القسم / التصنيف *</label>
                    <select id="category" class="input-premium" required onchange="toggleSpecs()">
                        <option value="all">اختر القسم...</option>
                        <option value="cars">🚗 حراج السيارات</option>
                        <option value="realestate">🏠 عقارات</option>
                        <option value="electronics">📱 أجهزة وإلكترونيات</option>
                        <option value="livestock">🐏 مواشي وحيوانات</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>المدينة *</label>
                    <select id="city" class="input-premium" required>
                        <option value="الكل">اختر المدينة...</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>السعر المطلوب (اختياري)</label>
                    <input type="number" id="price" class="input-premium" placeholder="مثال: 55000">
                </div>
            </div>

            <!-- Cars Specs -->
            <div id="cars-specs" class="dynamic-block hidden animate-fade-in">
                <h4 style="margin:0 0 1rem 0; color:var(--primary);">🚗 مواصفات السيارة</h4>
                <div class="form-grid">
                    <div class="form-group">
                        <label>الماركة</label>
                        <select id="carBrand" class="input-premium">
                            <option value="تويوتا">تويوتا</option>
                            <option value="هيونداي">هيونداي</option>
                            <option value="مرسيدس">مرسيدس</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>ناقل الحركة</label>
                        <select id="carTransmission" class="input-premium">
                            <option value="auto">أوتوماتيك</option>
                            <option value="manual">عادي</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>سنة الصنع</label>
                        <select id="carYear" class="input-premium">
                            <option value="2024">2024</option>
                            <option value="2023">2023</option>
                            <option value="2022">2022</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>الممشى (كم)</label>
                        <input type="number" id="carMileage" class="input-premium">
                    </div>
                </div>
            </div>

            <!-- Real Estate Specs -->
            <div id="realestate-specs" class="dynamic-block hidden animate-fade-in">
                <h4 style="margin:0 0 1rem 0; color:var(--primary);">🏠 المواصفات العقارية</h4>
                <div class="form-grid">
                    <div class="form-group">
                        <label>نوع العقار</label>
                        <select id="propertyType" class="input-premium">
                            <option value="villa">فيلا</option>
                            <option value="apartment">شقة</option>
                            <option value="land">أرض</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>عدد الغرف</label>
                        <select id="propertyRooms" class="input-premium">
                            <option value="3">3</option>
                            <option value="4">4</option>
                            <option value="5+">5+</option>
                        </select>
                    </div>
                    <div class="form-group full-width">
                        <label>نوع العقد</label>
                        <select id="propertyContract" class="input-premium">
                            <option value="sell">بيع</option>
                            <option value="rent">إيجار</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>التفاصيل ووصف السلعة *</label>
                <textarea id="description" class="input-premium" required placeholder="اكتب كل التفاصيل بصدق وأمانة..."></textarea>
            </div>

            <div class="form-group">
                <label>ألبوم الصور (سحب وإفلات)</label>
                <div class="dropzone" id="dropzone" onclick="document.getElementById('file-input').click()">
                    <input type="file" id="file-input" multiple accept="image/*" class="hidden" onchange="handleFiles(event)">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">📸</div>
                    <div>انقر لاختيار الصور أو اسحبها هنا</div>
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.5rem;">سيتم تحويل الصور تلقائياً لرموز آمنة</div>
                </div>
                <div class="preview-grid" id="preview-grid"></div>
            </div>

            <button type="submit" id="submit-btn" class="btn-primary full-width" style="margin-top: 1rem; width: 100%;">🚀 نشر الإعلان الآن</button>
        </form>
    </main>

    <script src="assets/js/app.js"></script>
    <script>
        let uploadedImages = [];

        async function init() {
            try {
                const cityRes = await apiRequest('cities');
                const citySelect = document.getElementById('city');
                cityRes.data.forEach(city => {
                    const opt = document.createElement('option');
                    opt.value = city;
                    opt.textContent = city;
                    citySelect.appendChild(opt);
                });
            } catch (e) {}
        }

        function toggleSpecs() {
            const cat = document.getElementById('category').value;
            document.getElementById('cars-specs').classList.add('hidden');
            document.getElementById('realestate-specs').classList.add('hidden');
            
            if (cat === 'cars') document.getElementById('cars-specs').classList.remove('hidden');
            if (cat === 'realestate') document.getElementById('realestate-specs').classList.remove('hidden');
        }

        function handleFiles(e) {
            const files = e.target.files || e.dataTransfer.files;
            if (!files) return;

            for (let file of files) {
                if (!file.type.startsWith('image/')) continue;
                const reader = new FileReader();
                reader.onload = function(evt) {
                    uploadedImages.push(evt.target.result);
                    renderPreviews();
                };
                reader.readAsDataURL(file);
            }
        }

        function renderPreviews() {
            const grid = document.getElementById('preview-grid');
            grid.innerHTML = '';
            uploadedImages.forEach((img, idx) => {
                const div = document.createElement('div');
                div.className = 'preview-item';
                div.innerHTML = `
                    <img src="${img}">
                    <button type="button" onclick="deleteImage(${idx})">✕</button>
                `;
                grid.appendChild(div);
            });
        }

        function deleteImage(idx) {
            uploadedImages.splice(idx, 1);
            renderPreviews();
        }

        // Drag and drop setup
        const dropzone = document.getElementById('dropzone');
        dropzone.addEventListener('dragover', e => { e.preventDefault(); dropzone.style.borderColor = 'var(--primary)'; });
        dropzone.addEventListener('dragleave', e => { dropzone.style.borderColor = 'var(--border-color)'; });
        dropzone.addEventListener('drop', e => { e.preventDefault(); dropzone.style.borderColor = 'var(--border-color)'; handleFiles(e); });

        async function submitAd(e) {
            e.preventDefault();
            const btn = document.getElementById('submit-btn');
            btn.disabled = true;
            btn.innerHTML = 'جاري النشر... ⏳';

            const payload = {
                title: document.getElementById('title').value,
                category: document.getElementById('category').value,
                city: document.getElementById('city').value,
                price: document.getElementById('price').value,
                description: document.getElementById('description').value,
                images_base64: uploadedImages
            };

            if (payload.category === 'cars') {
                payload.carBrand = document.getElementById('carBrand').value;
                payload.carTransmission = document.getElementById('carTransmission').value;
                payload.carYear = document.getElementById('carYear').value;
                payload.carMileage = document.getElementById('carMileage').value;
            } else if (payload.category === 'realestate') {
                payload.propertyType = document.getElementById('propertyType').value;
                payload.propertyRooms = document.getElementById('propertyRooms').value;
                payload.propertyContract = document.getElementById('propertyContract').value;
            }

            try {
                const res = await apiRequest('ads', 'POST', payload);
                showToast(res.message, 'success');
                setTimeout(() => window.location.href = `ad.php?id=${res.data.id}`, 1500);
            } catch (err) {
                btn.disabled = false;
                btn.innerHTML = '🚀 نشر الإعلان الآن';
            }
        }

        document.addEventListener('DOMContentLoaded', init);
    </script>
</body>
</html>
