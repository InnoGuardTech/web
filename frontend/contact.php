<?php
require_once __DIR__ . '/../backend/config.php';
if (function_exists('secureSession')) { secureSession(); } else { session_start(); }
define('PAGE_TITLE', 'اتصل بنا — حراج اليمن الفاخر');
define('PAGE_DESC', 'تواصل مع فريق دعم حراج اليمن الفاخر. نحن هنا لخدمتك على مدار الساعة.');
require __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/icons.php';
?>

<nav class="breadcrumbs">
    <a href="index.php">الرئيسية</a><span class="sep">›</span>
    <span class="current">اتصل بنا</span>
</nav>

<section class="hero animate-fadeInUp" style="padding:var(--sp-9) var(--sp-7);">
    <div class="hero-shape hero-shape-1"></div>
    <h1>نحن هنا <span style="background:linear-gradient(135deg,#f5d27a,#e8b94e);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;">لخدمتك</span></h1>
    <p>سواء كان لديك سؤال، اقتراح، أو تحتاج مساعدة — فريقنا جاهز للرد عليك في أي وقت.</p>
</section>

<div style="display:grid;grid-template-columns:1.2fr 1fr;gap:var(--sp-6);margin-top:var(--sp-6);" id="contactGrid">
    <div class="card reveal" style="padding:var(--sp-7);">
        <h2 style="margin-bottom:var(--sp-5);">أرسل لنا رسالة</h2>
        <form id="contactForm" onsubmit="submitContact(event)">
            <div class="filter-group">
                <label class="field-label">الاسم الكامل *</label>
                <input type="text" class="input" name="name" required placeholder="مثال: أحمد محمد">
            </div>
            <div class="filter-group">
                <label class="field-label">البريد الإلكتروني *</label>
                <input type="email" class="input" name="email" required placeholder="you@example.com">
            </div>
            <div class="filter-group">
                <label class="field-label">رقم الجوال</label>
                <input type="tel" class="input" name="phone" placeholder="+967 7XX XXX XXX">
            </div>
            <div class="filter-group">
                <label class="field-label">الموضوع *</label>
                <select class="select" name="subject" required>
                    <option value="">اختر الموضوع</option>
                    <option value="support">دعم فني</option>
                    <option value="report">الإبلاغ عن إعلان</option>
                    <option value="suggestion">اقتراح</option>
                    <option value="business">شراكة تجارية</option>
                    <option value="other">أخرى</option>
                </select>
            </div>
            <div class="filter-group">
                <label class="field-label">الرسالة *</label>
                <textarea class="textarea" name="message" required placeholder="اكتب رسالتك هنا..." style="min-height:140px;"></textarea>
            </div>
            <button type="submit" class="btn btn-lg btn-block"><?= icon('send', ['size'=>18]) ?> إرسال الرسالة</button>
        </form>
    </div>

    <div style="display:flex;flex-direction:column;gap:var(--sp-4);">
        <div class="card reveal reveal-delay-1" style="padding:var(--sp-5);">
            <div style="display:flex;gap:14px;align-items:center;">
                <div class="feature-ico" style="width:48px;height:48px;border-radius:12px;background:var(--brand-50);color:var(--brand-600);display:grid;place-items:center;flex-shrink:0;"><?= icon('mail', ['size'=>24]) ?></div>
                <div>
                    <div style="font-size:12.5px;color:var(--muted);">البريد الإلكتروني</div>
                    <a href="mailto:support@haraj-yemen.com" style="font-weight:700;color:var(--text);">support@haraj-yemen.com</a>
                </div>
            </div>
        </div>
        <div class="card reveal reveal-delay-2" style="padding:var(--sp-5);">
            <div style="display:flex;gap:14px;align-items:center;">
                <div class="feature-ico" style="width:48px;height:48px;border-radius:12px;background:rgba(16,185,129,.12);color:#059669;display:grid;place-items:center;flex-shrink:0;"><?= icon('phone', ['size'=>24]) ?></div>
                <div>
                    <div style="font-size:12.5px;color:var(--muted);">واتساب / جوال</div>
                    <a href="https://wa.me/967700000000" style="font-weight:700;color:var(--text);">+967 700 000 000</a>
                </div>
            </div>
        </div>
        <div class="card reveal reveal-delay-3" style="padding:var(--sp-5);">
            <div style="display:flex;gap:14px;align-items:center;">
                <div class="feature-ico" style="width:48px;height:48px;border-radius:12px;background:rgba(212,160,44,.12);color:#b8851c;display:grid;place-items:center;flex-shrink:0;"><?= icon('map-pin', ['size'=>24]) ?></div>
                <div>
                    <div style="font-size:12.5px;color:var(--muted);">العنوان</div>
                    <div style="font-weight:700;">صنعاء — الجمهورية اليمنية 🇾🇪</div>
                </div>
            </div>
        </div>
        <div class="card reveal reveal-delay-4" style="padding:var(--sp-5);">
            <div style="display:flex;gap:14px;align-items:center;">
                <div class="feature-ico" style="width:48px;height:48px;border-radius:12px;background:rgba(239,68,68,.12);color:#dc2626;display:grid;place-items:center;flex-shrink:0;"><?= icon('clock', ['size'=>24]) ?></div>
                <div>
                    <div style="font-size:12.5px;color:var(--muted);">ساعات العمل</div>
                    <div style="font-weight:700;">دعم 24/7</div>
                </div>
            </div>
        </div>
        <div class="card reveal" style="padding:var(--sp-5);background:var(--grad-brand-deep);color:#fff;border:none;">
            <h3 style="color:#fff;margin-bottom:10px;">تابعنا على</h3>
            <div style="display:flex;gap:10px;">
                <a href="#" class="icon-btn" style="background:rgba(255,255,255,.15);color:#fff;"><?= icon('facebook', ['size'=>20]) ?></a>
                <a href="#" class="icon-btn" style="background:rgba(255,255,255,.15);color:#fff;"><?= icon('twitter', ['size'=>20]) ?></a>
                <a href="#" class="icon-btn" style="background:rgba(255,255,255,.15);color:#fff;"><?= icon('instagram', ['size'=>20]) ?></a>
                <a href="#" class="icon-btn" style="background:rgba(255,255,255,.15);color:#fff;"><?= icon('telegram', ['size'=>20]) ?></a>
            </div>
        </div>
    </div>
</div>

<style>
@media(max-width:900px){#contactGrid{grid-template-columns:1fr !important;}}
</style>

<script>
function submitContact(e){
    e.preventDefault();
    const btn = e.target.querySelector('button[type="submit"]');
    const old = btn.innerHTML;
    btn.disabled = true; btn.innerHTML = '<span class="spinner" style="width:18px;height:18px;border-width:2px;"></span> جاري الإرسال...';
    setTimeout(()=>{
        toast('تم إرسال رسالتك بنجاح! سنرد عليك خلال 24 ساعة.', 'success');
        e.target.reset();
        btn.disabled = false; btn.innerHTML = old;
    }, 1200);
}
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
