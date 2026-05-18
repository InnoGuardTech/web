<?php
require_once __DIR__ . '/../config.php';
$userId = (int)($_GET['id'] ?? 0);
define('PAGE_TITLE', 'الملف الشخصي - ' . SITE_NAME);
include __DIR__ . '/includes/header.php';
?>
<style>
.profile-header { background: linear-gradient(135deg, var(--primary), var(--primary-hover)); color: white; padding: 2rem 1.5rem; border-radius: var(--radius-xl); margin-bottom: 1.5rem; }
.profile-top { display: flex; align-items: center; gap: 1.25rem; flex-wrap: wrap; }
.profile-avatar { width: 100px; height: 100px; border-radius: 50%; border: 4px solid var(--accent); overflow: hidden; flex-shrink: 0; }
.profile-avatar img { width: 100%; height: 100%; object-fit: cover; }
.profile-info { flex: 1; min-width: 200px; }
.profile-info h1 { margin: 0 0 0.4rem; font-weight: 900; font-size: 1.5rem; }
.profile-info .sub { opacity: 0.85; font-size: 0.9rem; margin-bottom: 0.5rem; }
.profile-stats { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 0.85rem; margin-top: 1.25rem; }
.stat-box { background: rgba(255,255,255,0.1); border-radius: var(--radius-md); padding: 0.85rem; text-align: center; }
.stat-box .num { font-size: 1.4rem; font-weight: 900; }
.stat-box .label { font-size: 0.75rem; opacity: 0.85; }
.profile-grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 1.25rem; }
@media (max-width: 768px) { .profile-grid { grid-template-columns: 1fr; } }
.review-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 0.9rem; margin-bottom: 0.6rem; }
.review-header { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.4rem; }
.review-avatar { width: 36px; height: 36px; border-radius: 50%; overflow: hidden; }
.review-avatar img { width: 100%; height: 100%; object-fit: cover; }
</style>

<div class="container animate-fade-in">
    <div id="profile-container" class="hidden">
        <div class="profile-header">
            <div class="profile-top">
                <div class="profile-avatar"><img id="p-avatar" src="" alt=""></div>
                <div class="profile-info">
                    <h1 id="p-name">...</h1>
                    <div class="sub">⭐ <span id="p-rating">...</span> (<span id="p-count">0</span> تقييم) · انضم في <span id="p-joined">...</span></div>
                    <div id="p-bio" style="opacity:0.9; font-size:0.9rem;"></div>
                </div>
            </div>
            <div class="profile-stats">
                <div class="stat-box"><div class="num" id="s-total">0</div><div class="label">إجمالي الإعلانات</div></div>
                <div class="stat-box"><div class="num" id="s-active">0</div><div class="label">نشطة</div></div>
                <div class="stat-box"><div class="num" id="s-sold">0</div><div class="label">تم البيع</div></div>
                <div class="stat-box"><div class="num" id="s-views">0</div><div class="label">إجمالي المشاهدات</div></div>
            </div>
        </div>

        <div class="profile-grid">
            <div class="section-block premium-card">
                <h3 style="margin:0 0 1rem; font-weight:900; color:var(--primary); border-bottom:2px solid var(--accent); padding-bottom:0.5rem;">📋 إعلانات المستخدم</h3>
                <div id="user-ads" class="ad-list" style="max-height:600px; overflow-y:auto;"></div>
            </div>
            <div class="section-block premium-card">
                <h3 style="margin:0 0 1rem; font-weight:900; color:var(--primary); border-bottom:2px solid var(--accent); padding-bottom:0.5rem;">⭐ التقييمات</h3>
                <div id="reviews-list" style="max-height:500px; overflow-y:auto;"></div>
                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $userId): ?>
                <button class="btn-outline btn-block" onclick="openReviewForm()" style="margin-top:1rem;">✍️ أضف تقييم</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div id="loading" style="text-align:center; padding:4rem;">جاري التحميل...</div>
</div>

<script src="assets/js/app.js"></script>
<script>
const USER_ID = <?= $userId ?>;
const ME = <?= isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0 ?>;

async function load() {
    try {
        const r = await apiRequest('user&action=profile&id=' + USER_ID);
        const d = r.data;
        document.getElementById('loading').style.display = 'none';
        document.getElementById('profile-container').classList.remove('hidden');

        document.getElementById('p-avatar').src = d.user.avatar_url;
        document.getElementById('p-name').textContent = d.user.name;
        document.getElementById('p-rating').textContent = parseFloat(d.user.rating).toFixed(1);
        document.getElementById('p-count').textContent = d.user.ratingCount || 0;
        document.getElementById('p-joined').textContent = d.user.joinedDate;
        document.getElementById('p-bio').textContent = d.user.bio || '';

        document.getElementById('s-total').textContent = d.stats.total;
        document.getElementById('s-active').textContent = d.stats.active;
        document.getElementById('s-sold').textContent = d.stats.sold;
        document.getElementById('s-views').textContent = formatNumber(d.stats.views);

        const adsBox = document.getElementById('user-ads');
        adsBox.innerHTML = d.ads.length ? d.ads.map(a => `
            <a href="ad.php?id=${a.id}" class="ad-row" style="padding:0.6rem;">
                <div class="ad-row-main">
                    <img class="ad-row-thumb" style="width:60px; height:60px;" src="${a.image}" alt="">
                    <div class="ad-row-content">
                        <h3 class="ad-row-title" style="font-size:0.88rem;">${escapeHtml(a.title)}</h3>
                        <div class="ad-row-meta" style="font-size:0.7rem;">
                            <div class="ad-row-meta-item">📍 ${a.city}</div>
                            <div class="ad-row-meta-item">⏱️ ${a.date}</div>
                            ${a.status === 'sold' ? '<span class="status-badge status-sold">تم البيع</span>' : ''}
                        </div>
                    </div>
                </div>
                <div class="ad-row-side"><div class="ad-row-price" style="font-size:0.9rem;">${a.price}</div></div>
            </a>`).join('') : '<div style="text-align:center; padding:2rem; color:var(--text-muted);">لا توجد إعلانات</div>';

        const revBox = document.getElementById('reviews-list');
        revBox.innerHTML = d.reviews.length ? d.reviews.map(r => `
            <div class="review-card">
                <div class="review-header">
                    <div class="review-avatar"><img src="${r.authorAvatar}" alt=""></div>
                    <div>
                        <div style="font-weight:800;">${escapeHtml(r.author)}</div>
                        <div style="font-size:0.75rem; color:var(--text-muted);">${getStarsHTML(r.rating)} · ${r.date}</div>
                    </div>
                </div>
                <div style="font-size:0.88rem;">${escapeHtml(r.content)}</div>
            </div>`).join('') : '<div style="text-align:center; padding:2rem; color:var(--text-muted);">لا توجد تقييمات</div>';
    } catch(e) {
        document.getElementById('loading').innerHTML = '<h3>المستخدم غير موجود</h3><a href="index.php">العودة</a>';
    }
}

function openReviewForm() {
    openModal(`
        <div class="modal-header"><h3>⭐ أضف تقييماً</h3><button class="modal-close" onclick="closeModal()">×</button></div>
        <form class="modal-body" onsubmit="submitReview(event)">
            <div class="form-group">
                <label>التقييم</label>
                <select id="r-rating">
                    <option value="5">⭐⭐⭐⭐⭐ ممتاز</option>
                    <option value="4">⭐⭐⭐⭐ جيد جداً</option>
                    <option value="3">⭐⭐⭐ جيد</option>
                    <option value="2">⭐⭐ سيء</option>
                    <option value="1">⭐ سيء جداً</option>
                </select>
            </div>
            <div class="form-group">
                <label>التعليق</label>
                <textarea id="r-content" rows="3" placeholder="شارك تجربتك مع هذا البائع..." required minlength="5"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-outline" onclick="closeModal()">إلغاء</button>
                <button type="submit" class="btn-primary">إرسال</button>
            </div>
        </form>
    `);
}

async function submitReview(e) {
    e.preventDefault();
    try {
        await apiRequest('user&action=add_review', 'POST', {
            target_id: USER_ID,
            rating: document.getElementById('r-rating').value,
            content: document.getElementById('r-content').value
        });
        showToast('✓ تم إرسال التقييم', 'success');
        closeModal();
        load();
    } catch (e) {}
}

document.addEventListener('DOMContentLoaded', load);
</script>
</body></html>
