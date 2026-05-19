<?php
require_once __DIR__ . '/../backend/config.php';
if (function_exists('secureSession')) { secureSession(); } else { session_start(); }
define('PAGE_TITLE', 'شروط الاستخدام — حراج اليمن الفاخر');
define('PAGE_DESC', 'الشروط والأحكام لاستخدام منصة حراج اليمن الفاخر.');
require __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/icons.php';
?>

<nav class="breadcrumbs">
    <a href="index.php">الرئيسية</a><span class="sep">›</span>
    <span class="current">شروط الاستخدام</span>
</nav>

<section class="hero animate-fadeInUp" style="padding:var(--sp-8) var(--sp-7);">
    <div class="hero-shape hero-shape-1"></div>
    <h1>شروط الاستخدام</h1>
    <p>آخر تحديث: <?= date('Y/m/d') ?></p>
</section>

<section class="section">
    <div class="card" style="max-width:920px;margin:0 auto;padding:var(--sp-8);line-height:1.95;font-size:15px;">
        <h2>1. القبول بالشروط</h2>
        <p>باستخدامك لمنصة <strong>حراج اليمن الفاخر</strong>، فإنك توافق على الالتزام بهذه الشروط والأحكام بالكامل. إذا كنت لا توافق على أي جزء منها، يرجى عدم استخدام المنصة.</p>

        <h2 style="margin-top:var(--sp-6);">2. التسجيل والحساب</h2>
        <p>يجب أن تكون قد بلغت 18 سنة على الأقل لإنشاء حساب. أنت مسؤول عن سرية بيانات حسابك وعن جميع الأنشطة التي تتم من خلاله. يجب تقديم معلومات صحيحة ومحدّثة عند التسجيل.</p>

        <h2 style="margin-top:var(--sp-6);">3. نشر الإعلانات</h2>
        <p>عند نشر إعلان، يجب أن يكون:</p>
        <ul style="padding-inline-start:24px;margin-top:8px;">
            <li>صادقاً ودقيقاً في وصف المنتج أو الخدمة</li>
            <li>متضمناً صوراً حقيقية للمنتج وليست مسروقة من الإنترنت</li>
            <li>مطابقاً للقوانين المعمول بها في الجمهورية اليمنية</li>
            <li>غير مخالف للأخلاق العامة أو الآداب الإسلامية</li>
        </ul>

        <h2 style="margin-top:var(--sp-6);">4. المحتوى المحظور</h2>
        <p>يُمنع نشر أي محتوى يتعلق بـ:</p>
        <ul style="padding-inline-start:24px;margin-top:8px;">
            <li>الأسلحة والمتفجرات والمواد الخطرة</li>
            <li>المخدرات والمسكرات</li>
            <li>المحتوى المسيء أو غير الأخلاقي</li>
            <li>الأنشطة الإرهابية أو التحريضية</li>
            <li>الحيوانات والآثار المحمية قانونياً</li>
            <li>أي منتج مقلّد أو منسوخ بشكل غير قانوني</li>
        </ul>

        <h2 style="margin-top:var(--sp-6);">5. المسؤولية</h2>
        <p>المنصة هي مجرد وسيط بين البائعين والمشترين، ولا تتحمل أي مسؤولية عن جودة المنتجات أو دقة الوصف أو إتمام الصفقات. ننصح بالحذر دائماً والتحقق من المنتج قبل الدفع.</p>

        <h2 style="margin-top:var(--sp-6);">6. الإعلانات المميزة</h2>
        <p>الإعلانات المميزة تخضع لرسوم رمزية يتم تحديدها في صفحة كل إعلان. الدفع يتم بشكل آمن، والمميزات تُفعّل خلال دقائق من إتمام الدفع.</p>

        <h2 style="margin-top:var(--sp-6);">7. الإيقاف والإلغاء</h2>
        <p>يحق لإدارة المنصة إيقاف أو حذف أي حساب أو إعلان مخالف لهذه الشروط دون إشعار مسبق وفي أي وقت.</p>

        <h2 style="margin-top:var(--sp-6);">8. الملكية الفكرية</h2>
        <p>جميع حقوق التصميم والمحتوى الخاص بالمنصة محفوظة لـ <strong>حراج اليمن الفاخر</strong>. يُمنع نسخ أو إعادة إنتاج أي جزء من المنصة بدون إذن خطي مسبق.</p>

        <h2 style="margin-top:var(--sp-6);">9. التعديلات</h2>
        <p>نحتفظ بحق تعديل هذه الشروط في أي وقت. التعديلات تصبح سارية فور نشرها على الموقع. استمرارك في استخدام المنصة بعد التعديل يُعدّ موافقة منك على الشروط الجديدة.</p>

        <h2 style="margin-top:var(--sp-6);">10. القانون الحاكم</h2>
        <p>تخضع هذه الشروط للقوانين المعمول بها في الجمهورية اليمنية، وأي نزاع ينشأ يتم حله أمام المحاكم اليمنية المختصة.</p>

        <div style="margin-top:var(--sp-7);padding:var(--sp-5);background:var(--bg-soft);border-radius:var(--r-md);border-inline-start:4px solid var(--brand-500);">
            <strong>للاستفسار حول هذه الشروط، يرجى التواصل معنا عبر <a href="contact.php" style="color:var(--brand-500);">صفحة الاتصال</a>.</strong>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
