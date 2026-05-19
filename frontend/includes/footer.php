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
                <p style="font-size:13px;color:var(--muted);line-height:1.8;">منصة الإعلانات المبوبة الأكثر فخامة في اليمن. بيع واشترِ بكل سهولة وأمان.</p>
            </div>
            <div class="footer-col">
                <h4>روابط مهمة</h4>
                <a href="index.php">الرئيسية</a>
                <a href="post.php">أضف إعلان</a>
                <a href="favorites.php">المفضلة</a>
                <a href="commission.php">العمولة</a>
            </div>
            <div class="footer-col">
                <h4>الفئات</h4>
                <a href="index.php?category=cars">سيارات</a>
                <a href="index.php?category=realestate">عقارات</a>
                <a href="index.php?category=electronics">إلكترونيات</a>
                <a href="index.php?category=furniture">أثاث</a>
            </div>
            <div class="footer-col">
                <h4>المساعدة</h4>
                <a href="#">سياسة الخصوصية</a>
                <a href="#">شروط الاستخدام</a>
                <a href="#">اتصل بنا</a>
                <a href="sitemap.php">خريطة الموقع</a>
            </div>
        </div>
        <div class="footer-bottom">© <?= date('Y') ?> حراج اليمن الفاخر — جميع الحقوق محفوظة.</div>
    </div>
</footer>

<nav class="mobile-nav">
    <a href="index.php" class="<?= $_pageBase==='index'?'active':'' ?>"><?= icon('home', ['size'=>22]) ?><span>الرئيسية</span></a>
    <a href="favorites.php" class="<?= $_pageBase==='favorites'?'active':'' ?>"><?= icon('heart', ['size'=>22]) ?><span>المفضلة</span></a>
    <a href="post.php" class="<?= $_pageBase==='post'?'active':'' ?>" style="position:relative;top:-6px;">
        <span style="width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,var(--brand-500),var(--brand-700));color:#fff;display:grid;place-items:center;box-shadow:var(--sh-md);"><?= icon('plus', ['size'=>22]) ?></span>
    </a>
    <a href="messages.php" class="<?= $_pageBase==='messages'?'active':'' ?>"><?= icon('message', ['size'=>22]) ?><span>الرسائل</span></a>
    <a href="<?= $_me_f ? 'user.php?id='.$_me_f['id'] : 'auth.php' ?>" class="<?= in_array($_pageBase, ['user','settings','auth']) ?'active':'' ?>"><?= icon('user', ['size'=>22]) ?><span>حسابي</span></a>
</nav>

<script src="assets/js/app.js?v=<?= @filemtime(__DIR__ . '/../assets/js/app.js') ?: time() ?>"></script>
</body>
</html>
