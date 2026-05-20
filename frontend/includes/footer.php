<?php
require_once __DIR__ . '/icons.php';
$_me_f = (isset($_SESSION['user_id']) && function_exists('getCurrentUser')) ? getCurrentUser() : null;
$_pageBase = basename($_SERVER['PHP_SELF'], '.php');
?>
</main>

<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-col">
                <h4>حراج اليمن الفاخر</h4>
                <p>منصة الإعلانات المبوبة الأفخم في اليمن. اشترِ وبِع بكل سهولة وأمان. تجربة مستخدم استثنائية ودعم متواصل على مدار الساعة.</p>
                <div class="footer-social" aria-label="تواصل اجتماعي">
                    <a href="#" aria-label="فيسبوك" title="Facebook"><?= icon('facebook', ['size'=>18]) ?></a>
                    <a href="#" aria-label="تويتر" title="Twitter"><?= icon('twitter', ['size'=>18]) ?></a>
                    <a href="#" aria-label="إنستغرام" title="Instagram"><?= icon('instagram', ['size'=>18]) ?></a>
                    <a href="https://wa.me/967700000000" aria-label="واتساب" title="WhatsApp"><?= icon('phone', ['size'=>18]) ?></a>
                </div>
            </div>
            <div class="footer-col">
                <h4>روابط مهمة</h4>
                <a href="index.php">الرئيسية</a>
                <a href="post.php">أضف إعلان</a>
                <a href="favorites.php">المفضلة</a>
                <a href="commission.php">العمولة</a>
                <a href="about.php">عن المنصة</a>
            </div>
            <div class="footer-col">
                <h4>الفئات</h4>
                <a href="index.php?category=cars">سيارات</a>
                <a href="index.php?category=realestate">عقارات</a>
                <a href="index.php?category=electronics">إلكترونيات</a>
                <a href="index.php?category=furniture">أثاث</a>
                <a href="index.php?category=jobs">وظائف</a>
            </div>
            <div class="footer-col">
                <h4>المساعدة</h4>
                <a href="faq.php">الأسئلة الشائعة</a>
                <a href="contact.php">اتصل بنا</a>
                <a href="terms.php">شروط الاستخدام</a>
                <a href="privacy.php">سياسة الخصوصية</a>
                <a href="sitemap.php">خريطة الموقع</a>
            </div>
        </div>
        <div class="footer-bottom">
            © <?= date('Y') ?> <strong>حراج اليمن الفاخر</strong> — جميع الحقوق محفوظة 🇾🇪 — صُنع بـ <span style="color:#ef4444;">♥</span> لخدمة اليمنيين
        </div>
    </div>
</footer>

<nav class="mobile-nav">
    <a href="index.php" class="<?= $_pageBase==='index'?'active':'' ?>"><?= icon('home', ['size'=>22]) ?><span>الرئيسية</span></a>
    <a href="favorites.php" class="<?= $_pageBase==='favorites'?'active':'' ?>"><?= icon('heart', ['size'=>22]) ?><span>المفضلة</span></a>
    <a href="post.php" class="<?= $_pageBase==='post'?'active':'' ?>" style="position:relative;top:-6px;">
        <span style="width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,var(--brand-500),var(--brand-700));color:#fff;display:grid;place-items:center;box-shadow:0 8px 20px rgba(59,108,246,.4);"><?= icon('plus', ['size'=>22]) ?></span>
    </a>
    <a href="messages.php" class="<?= $_pageBase==='messages'?'active':'' ?>"><?= icon('message', ['size'=>22]) ?><span>الرسائل</span></a>
    <a href="<?= $_me_f ? 'user.php?id='.$_me_f['id'] : 'auth.php' ?>" class="<?= in_array($_pageBase, ['user','settings','auth']) ?'active':'' ?>"><?= icon('user', ['size'=>22]) ?><span>حسابي</span></a>
</nav>

<script src="assets/js/app.js?v=<?= @filemtime(__DIR__ . '/../assets/js/app.js') ?: time() ?>"></script>
<script src="assets/js/modern-ui.js?v=<?= @filemtime(__DIR__ . '/../assets/js/modern-ui.js') ?: time() ?>"></script>
<script src="assets/js/improvements.js?v=<?= @filemtime(__DIR__ . '/../assets/js/improvements.js') ?: time() ?>"></script>
</body>
</html>
