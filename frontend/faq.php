<?php
require_once __DIR__ . '/../backend/config.php';
if (function_exists('secureSession')) { secureSession(); } else { session_start(); }
define('PAGE_TITLE', 'الأسئلة الشائعة — حراج اليمن الفاخر');
define('PAGE_DESC', 'إجابات على أكثر الأسئلة شيوعاً حول استخدام منصة حراج اليمن الفاخر.');
require __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/icons.php';

$faqs = [
    ['كيف يمكنني إضافة إعلان جديد؟', 'يمكنك إضافة إعلانك بسهولة من خلال الضغط على زر "أضف إعلان" في أعلى الصفحة، ثم تعبئة بيانات المنتج بالكامل ورفع صور واضحة، ثم النشر مباشرة بشكل مجاني.'],
    ['هل النشر في المنصة مجاني؟', 'نعم، نشر الإعلانات الأساسية مجاني تماماً. لدينا خيارات اختيارية لتمييز الإعلانات لجعلها تظهر في الصدارة وزيادة فرص بيعها.'],
    ['كيف أحمي نفسي من عمليات الاحتيال؟', 'لا تشارك بياناتك المصرفية مع أي شخص، التق المشتري في مكان عام وآمن، تحقق من المنتج قبل الدفع، استخدم نظام محادثات المنصة فقط، وأبلغنا فوراً عن أي إعلان مشبوه.'],
    ['كيف يمكنني التواصل مع البائع؟', 'بعد فتح صفحة الإعلان، اضغط على زر "أرسل رسالة" لبدء محادثة آمنة مع البائع عبر نظام المحادثات الداخلي، أو استخدم رقم الجوال إذا أضافه البائع.'],
    ['ما هي مدة عرض الإعلان؟', 'يظل إعلانك نشطاً لمدة 60 يوماً افتراضياً. يمكنك تجديده مجاناً عند انتهائه أو حذفه في أي وقت من لوحة "إعلاناتي".'],
    ['كيف أحذف إعلاني أو أعدّله؟', 'اذهب إلى صفحة "إعلاناتي" من قائمة الحساب، ثم اختر الإعلان المراد تعديله أو حذفه، واضغط الزر المناسب. التعديلات تظهر فوراً.'],
    ['ما هي الإعلانات المميزة وكيف أحصل عليها؟', 'الإعلانات المميزة تظهر في أعلى نتائج البحث وعلى الصفحة الرئيسية، وتحصل على مشاهدات أكثر بـ 5 أضعاف. يمكنك تمييز إعلانك من صفحته بدفع رسوم رمزية.'],
    ['هل يمكنني استخدام المنصة بدون تسجيل؟', 'يمكنك تصفح الإعلانات بدون تسجيل، لكن لإضافة إعلان أو إرسال رسائل أو حفظ المفضلة، يجب إنشاء حساب مجاني.'],
    ['ماذا أفعل إذا واجهت مشكلة تقنية؟', 'تواصل معنا مباشرة عبر صفحة "اتصل بنا" أو الواتساب، وفريق الدعم سيرد عليك خلال دقائق على مدار الساعة.'],
    ['هل بياناتي الشخصية آمنة؟', 'نعم، نستخدم أحدث تقنيات التشفير لحماية بياناتك، ولا نشاركها أبداً مع أي طرف ثالث. اقرأ سياسة الخصوصية للمزيد.'],
];
?>

<nav class="breadcrumbs">
    <a href="index.php">الرئيسية</a><span class="sep">›</span>
    <span class="current">الأسئلة الشائعة</span>
</nav>

<section class="hero animate-fadeInUp" style="padding:var(--sp-9) var(--sp-7);">
    <div class="hero-shape hero-shape-1"></div>
    <h1>الأسئلة <span style="background:linear-gradient(135deg,#f5d27a,#e8b94e);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;">الشائعة</span></h1>
    <p>كل ما تحتاج معرفته عن استخدام منصة حراج اليمن الفاخر في مكان واحد.</p>
</section>

<section class="section">
    <div style="max-width:860px;margin:0 auto;">
        <?php foreach ($faqs as $i => $f): ?>
            <div class="faq-item card reveal" style="margin-bottom:14px;padding:0;overflow:hidden;<?= $i % 4 ? '' : '' ?>">
                <button class="faq-q" onclick="toggleFaq(this)" style="width:100%;display:flex;align-items:center;justify-content:space-between;gap:12px;padding:18px 22px;background:transparent;text-align:start;font-weight:700;font-size:15.5px;color:var(--text);cursor:pointer;border:none;">
                    <span><?= icon('help-circle', ['size'=>20, 'class'=>'faq-icon']) ?> <?= htmlspecialchars($f[0]) ?></span>
                    <span class="faq-arrow"><?= icon('chevron-down', ['size'=>20]) ?></span>
                </button>
                <div class="faq-a" style="max-height:0;overflow:hidden;transition:all .4s var(--ease-in-out);">
                    <div style="padding:0 22px 18px;color:var(--text-soft);line-height:1.85;font-size:14.5px;border-top:1px solid var(--line-soft);padding-top:14px;">
                        <?= htmlspecialchars($f[1]) ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="section reveal">
    <div style="background:var(--grad-brand-deep);border-radius:var(--r-2xl);padding:var(--sp-8);text-align:center;color:#fff;">
        <h2 style="color:#fff;margin-bottom:10px;">لم تجد إجابتك؟ 💬</h2>
        <p style="color:rgba(255,255,255,.9);max-width:520px;margin:0 auto var(--sp-5);">فريق الدعم جاهز للإجابة على جميع استفساراتك على مدار الساعة.</p>
        <a href="contact.php" class="btn btn-gold btn-lg"><?= icon('message-circle', ['size'=>20]) ?> تواصل معنا الآن</a>
    </div>
</section>

<style>
.faq-q:hover{background:var(--bg-soft);}
.faq-q .faq-icon{color:var(--brand-500);vertical-align:middle;margin-inline-end:8px;}
.faq-arrow{transition:transform .35s var(--ease-spring);color:var(--muted);display:inline-flex;}
.faq-item.open .faq-arrow{transform:rotate(180deg);color:var(--brand-500);}
.faq-item.open .faq-q{background:var(--bg-soft);}
.faq-item.open .faq-a{max-height:400px;}
</style>

<script>
function toggleFaq(btn){
    const item = btn.closest('.faq-item');
    item.classList.toggle('open');
}
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
