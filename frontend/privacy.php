<?php
require_once __DIR__ . '/../backend/config.php';
if (function_exists('secureSession')) { secureSession(); } else { session_start(); }
define('PAGE_TITLE', 'سياسة الخصوصية — حراج اليمن الفاخر');
define('PAGE_DESC', 'كيف نحمي بياناتك ونحافظ على خصوصيتك في منصة حراج اليمن الفاخر.');
require __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/icons.php';
?>

<nav class="breadcrumbs">
    <a href="index.php">الرئيسية</a><span class="sep">›</span>
    <span class="current">سياسة الخصوصية</span>
</nav>

<section class="hero animate-fadeInUp" style="padding:var(--sp-8) var(--sp-7);">
    <div class="hero-shape hero-shape-1"></div>
    <h1>سياسة الخصوصية</h1>
    <p>خصوصيتك أولويتنا — آخر تحديث: <?= date('Y/m/d') ?></p>
</section>

<section class="section">
    <div class="card" style="max-width:920px;margin:0 auto;padding:var(--sp-8);line-height:1.95;font-size:15px;">
        <p style="font-size:16px;color:var(--text-soft);">
            في <strong>حراج اليمن الفاخر</strong> نأخذ خصوصيتك بشكل جدي جداً. هذه السياسة توضح كيف نجمع، نستخدم، ونحمي بياناتك الشخصية.
        </p>

        <h2 style="margin-top:var(--sp-6);">1. البيانات التي نجمعها</h2>
        <ul style="padding-inline-start:24px;margin-top:8px;">
            <li><strong>بيانات الحساب:</strong> الاسم، رقم الجوال، البريد الإلكتروني، صورة الملف الشخصي</li>
            <li><strong>بيانات الإعلانات:</strong> العنوان، الوصف، السعر، الصور، الموقع</li>
            <li><strong>بيانات تقنية:</strong> عنوان IP، نوع المتصفح، نظام التشغيل، الصفحات المزارة</li>
            <li><strong>المحادثات:</strong> الرسائل المتبادلة بين المستخدمين عبر النظام</li>
        </ul>

        <h2 style="margin-top:var(--sp-6);">2. كيف نستخدم بياناتك</h2>
        <ul style="padding-inline-start:24px;margin-top:8px;">
            <li>تقديم خدمات المنصة وإدارة حسابك</li>
            <li>تحسين التجربة وتطوير ميزات جديدة</li>
            <li>التواصل معك بخصوص حسابك وإعلاناتك</li>
            <li>إرسال إشعارات (يمكنك إيقافها في أي وقت)</li>
            <li>منع الاحتيال وحماية المنصة</li>
            <li>الامتثال للقوانين والتعاون مع الجهات القضائية إذا لزم</li>
        </ul>

        <h2 style="margin-top:var(--sp-6);">3. مشاركة البيانات</h2>
        <p>نحن <strong>لا نبيع</strong> بياناتك الشخصية أبداً لأي طرف ثالث. قد نشارك بعض البيانات في الحالات التالية فقط:</p>
        <ul style="padding-inline-start:24px;margin-top:8px;">
            <li>بموافقتك الصريحة</li>
            <li>مع مزودي الخدمات الموثوقين (استضافة، تحليلات)</li>
            <li>عند طلب قانوني من جهة مختصة</li>
            <li>لحماية حقوق المنصة أو المستخدمين</li>
        </ul>

        <h2 style="margin-top:var(--sp-6);">4. حماية البيانات</h2>
        <p>نستخدم أحدث التقنيات لحماية بياناتك، بما في ذلك:</p>
        <ul style="padding-inline-start:24px;margin-top:8px;">
            <li>تشفير SSL/TLS لجميع الاتصالات</li>
            <li>تشفير كلمات المرور بخوارزميات متقدمة (bcrypt)</li>
            <li>جدران حماية ومراقبة مستمرة</li>
            <li>نسخ احتياطية منتظمة</li>
            <li>وصول محدود للبيانات لموظفي الفريق المختصين فقط</li>
        </ul>

        <h2 style="margin-top:var(--sp-6);">5. ملفات تعريف الارتباط (Cookies)</h2>
        <p>نستخدم ملفات تعريف الارتباط لتحسين تجربتك، حفظ تفضيلاتك (مثل الوضع الليلي)، وتحليل أداء المنصة. يمكنك تعطيلها من إعدادات متصفحك، لكن قد يؤثر ذلك على بعض الميزات.</p>

        <h2 style="margin-top:var(--sp-6);">6. حقوقك</h2>
        <p>لديك الحق في:</p>
        <ul style="padding-inline-start:24px;margin-top:8px;">
            <li>الوصول لبياناتك ومعرفة ما نخزنه عنك</li>
            <li>تصحيح أي بيانات غير دقيقة</li>
            <li>حذف حسابك وبياناتك (مع مراعاة القيود القانونية)</li>
            <li>سحب موافقتك على معالجة البيانات</li>
            <li>الاعتراض على معالجة معينة</li>
        </ul>

        <h2 style="margin-top:var(--sp-6);">7. الأطفال</h2>
        <p>المنصة موجهة للبالغين فقط (18+). لا نجمع بياناتٍ من القاصرين عن قصد. إذا اكتشفنا ذلك، سنحذف الحساب فوراً.</p>

        <h2 style="margin-top:var(--sp-6);">8. التحديثات</h2>
        <p>قد نُحدّث هذه السياسة من حين لآخر. سنُعلمك بأي تغييرات جوهرية عبر إشعار في الموقع أو بريد إلكتروني.</p>

        <h2 style="margin-top:var(--sp-6);">9. التواصل</h2>
        <p>لأي سؤال يتعلق بالخصوصية أو طلب حذف بياناتك، تواصل معنا:</p>
        <div style="margin-top:var(--sp-4);padding:var(--sp-5);background:var(--brand-50);border-radius:var(--r-md);">
            📧 <strong>privacy@haraj-yemen.com</strong><br>
            💬 <a href="contact.php" style="color:var(--brand-600);font-weight:700;">صفحة الاتصال</a>
        </div>
    </div>
</section>

<style>
[data-theme="dark"] div[style*="background:var(--brand-50)"]{background:rgba(59,108,246,.12) !important;}
</style>

<?php require __DIR__ . '/includes/footer.php'; ?>
