<?php
require_once __DIR__ . '/../backend/config.php';
if (function_exists('secureSession')) { secureSession(); } else { session_start(); }
define('PAGE_TITLE', 'عن المنصة — حراج اليمن الفاخر');
define('PAGE_DESC', 'تعرّف على حراج اليمن الفاخر، رؤيتنا، رسالتنا، وقيمنا في خدمة السوق اليمني.');
require __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/icons.php';
?>

<nav class="breadcrumbs">
    <a href="index.php">الرئيسية</a><span class="sep">›</span>
    <span class="current">عن المنصة</span>
</nav>

<section class="hero animate-fadeInUp" style="padding:var(--sp-9) var(--sp-7);">
    <div class="hero-shape hero-shape-1"></div>
    <div class="hero-shape hero-shape-2"></div>
    <h1>عن <span style="background:linear-gradient(135deg,#f5d27a,#e8b94e);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;">حراج اليمن الفاخر</span></h1>
    <p>المنصة اليمنية الأولى للإعلانات المبوبة الفاخرة — تجمع البائعين والمشترين في بيئة آمنة وموثوقة.</p>
</section>

<section class="section reveal">
    <div class="card" style="padding:var(--sp-8);">
        <h2 style="margin-bottom:var(--sp-4);display:flex;align-items:center;gap:10px;">
            <span style="width:6px;height:30px;background:var(--grad-brand);border-radius:3px;"></span>
            رسالتنا
        </h2>
        <p style="font-size:16px;line-height:1.9;color:var(--text-soft);">
            نحن في <strong>حراج اليمن الفاخر</strong> نسعى لإحداث ثورة في طريقة بيع وشراء المنتجات والخدمات في اليمن.
            نقدم منصة عصرية فاخرة تجمع بين الأمان الكامل والتصميم الأنيق وسرعة الأداء الاستثنائية،
            لنوفر للمستخدم اليمني تجربة لا مثيل لها في السوق المحلي.
        </p>
    </div>
</section>

<section class="section reveal">
    <div class="features-grid">
        <div class="feature-card reveal reveal-delay-1">
            <div class="feature-ico"><?= icon('eye', ['size'=>26]) ?></div>
            <h3>رؤيتنا</h3>
            <p>أن نكون المنصة الرقمية الأولى والأكثر ثقة للإعلانات المبوبة في اليمن والمنطقة العربية.</p>
        </div>
        <div class="feature-card gold reveal reveal-delay-2">
            <div class="feature-ico"><?= icon('target', ['size'=>26]) ?></div>
            <h3>هدفنا</h3>
            <p>تسهيل عمليات البيع والشراء بين اليمنيين، وتمكين رواد الأعمال والأفراد من الوصول إلى جمهور واسع.</p>
        </div>
        <div class="feature-card success reveal reveal-delay-3">
            <div class="feature-ico"><?= icon('award', ['size'=>26]) ?></div>
            <h3>قيمنا</h3>
            <p>الشفافية، الأمان، الجودة، والتركيز على المستخدم — هذه القيم تقود كل قرار نتخذه يومياً.</p>
        </div>
    </div>
</section>

<section class="section reveal">
    <div class="section-head"><h2><span class="accent"></span>لماذا نحن مختلفون؟</h2></div>
    <div class="features-grid">
        <div class="feature-card reveal reveal-delay-1">
            <div class="feature-ico"><?= icon('shield-check', ['size'=>26]) ?></div>
            <h3>أمان لا يُضاهى</h3>
            <p>تشفير كامل، حماية بيانات، مراجعة بشرية للإعلانات المشبوهة، ودعم 24/7.</p>
        </div>
        <div class="feature-card gold reveal reveal-delay-2">
            <div class="feature-ico"><?= icon('zap', ['size'=>26]) ?></div>
            <h3>سرعة استثنائية</h3>
            <p>أداء فائق وأوقات تحميل أقل من ثانية، حتى على شبكات الإنترنت البطيئة.</p>
        </div>
        <div class="feature-card success reveal reveal-delay-3">
            <div class="feature-ico"><?= icon('sparkles', ['size'=>26]) ?></div>
            <h3>تصميم فاخر</h3>
            <p>تصميم بمعايير عالمية، يجعل تجربة الاستخدام ممتعة وأنيقة على كل جهاز.</p>
        </div>
        <div class="feature-card danger reveal reveal-delay-4">
            <div class="feature-ico"><?= icon('heart', ['size'=>26]) ?></div>
            <h3>محلي بقلب يمني</h3>
            <p>منصة مصنوعة في اليمن، لليمنيين، تفهم تفاصيل السوق المحلي والاحتياجات الفعلية.</p>
        </div>
    </div>
</section>

<section class="section reveal">
    <div style="background:var(--grad-brand-deep);border-radius:var(--r-2xl);padding:var(--sp-8);text-align:center;color:#fff;">
        <h2 style="color:#fff;margin-bottom:12px;">انضم لرحلتنا</h2>
        <p style="color:rgba(255,255,255,.9);max-width:540px;margin:0 auto var(--sp-5);">كن جزءاً من أكبر مجتمع تجاري إلكتروني في اليمن — سجّل الآن وابدأ.</p>
        <a href="auth.php" class="btn btn-gold btn-lg"><?= icon('user', ['size'=>20]) ?> سجّل الآن مجاناً</a>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
