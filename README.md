# 🏆 حراج اليمن الفاخر — v3.0

> منصة الإعلانات المبوبة الأفخم في اليمن — تصميم حديث، أمان عالي، تجربة سلسة.

## ✨ المزايا الجديدة في v3.0

### 🎨 إعادة تصميم شاملة (Design System v3.0)
- **نظام ألوان فاخر جديد**: أزرق ملكي (#3b6cf6) + ذهبي (#c19a3e) + خلفيات هادئة.
- **خطوط احترافية**: Cairo + Tajawal بأوزان متعددة.
- **مكتبة أيقونات SVG موحدة** (`includes/icons.php`) — 60+ أيقونة بنمط Lucide، بسيطة وأنيقة.
- **تصميم Glassmorphism في الهيدر** مع backdrop-blur وشفافية ديناميكية.
- **Skeleton loading + Toast notifications + Modal confirmations** سلسة.
- **Mobile Bottom Nav** للتنقل السريع على الجوال.
- **Dark Mode فاخر** بإحساس فضائي (#0b0f1a).
- **Responsive Design** كامل من 320px إلى 4K.

### 🛒 إدارة الإعلانات الكاملة
- ✅ **إنشاء، تعديل، حذف، عرض** الإعلانات
- ✅ **Bump (رفع)** — إعادة نشر الإعلان مرة كل 24 ساعة
- ✅ **Mark as Sold** — تعليم الإعلان كمبيع
- ✅ **Archive / Reactivate** — أرشفة وإعادة تنشيط
- ✅ **رفع متعدد للصور** (حتى 8 صور، 5MB/صورة) بـ Drag & Drop

### 👤 إدارة الحساب
- ✅ **تعديل الملف الشخصي** (الاسم، النبذة)
- ✅ **تغيير كلمة المرور** (مع تأكيد القديمة)
- ✅ **Forgot Password + OTP** عبر SMS (Development mode يعرض الرمز)
- ✅ **توثيق الجوال** بـ OTP
- ✅ **حذف الحساب** (Soft Delete مع GDPR)

### 🔍 بحث وتصفية متقدمة
- ✅ Pagination (20 إعلان/صفحة)
- ✅ Sorting: الأحدث، الأقدم، الأرخص، الأغلى، الأكثر مشاهدة
- ✅ فلتر السعر (Min/Max)
- ✅ فلتر سنة الصنع (للسيارات)
- ✅ فلتر المدينة والفئة
- ✅ Full-text search

### 📱 المشاركة والموقع
- ✅ أزرار مشاركة: WhatsApp, X (Twitter), Facebook, Telegram, نسخ الرابط
- ✅ Open Graph + Twitter Card tags
- ✅ QR Code تلقائي لكل إعلان
- ✅ Schema.org JSON-LD (Product markup)

### 💬 محادثات حية
- ✅ قائمة محادثات + بحث
- ✅ إرسال نصوص وصور (5MB max)
- ✅ Read receipts (✓✓)
- ✅ Polling كل 3 ثوانٍ (3s SSE-like)
- ✅ Online/Offline status

### 🔔 الإشعارات
- ✅ نظام إشعارات داخلي (in-app)
- ✅ عداد غير المقروء في الهيدر
- ✅ Mark all as read

### 🛡️ الأمان
- ✅ **CSRF Protection** على جميع POST endpoints
- ✅ **Rate Limiting** (5 محاولات/5 دقائق للـ login)
- ✅ **Session Hardening**: HttpOnly, Secure, SameSite=Lax, regenerate periodically
- ✅ **Password Hashing**: `password_hash` + `password_verify`
- ✅ **SQL Injection Prevention**: PDO Prepared Statements
- ✅ **XSS Protection**: `htmlspecialchars` everywhere
- ✅ **حذف `quick_switch` و `demo_users` endpoints الخطرين**
- ✅ **.env** خارج Git tracking مع `.env.example`

### 📊 لوحة الإدارة الشاملة
- ✅ إحصاءات مباشرة: المستخدمون، الإعلانات، البلاغات، العمولات، الرسائل
- ✅ إدارة المستخدمين (حظر/فك حظر/بحث)
- ✅ إدارة الإعلانات (عرض/حذف)
- ✅ معالجة البلاغات
- ✅ مراجعة العمولات

### 🔎 SEO
- ✅ Meta description / keywords / OG / Twitter tags
- ✅ Schema.org Product JSON-LD
- ✅ `sitemap.php` ديناميكي
- ✅ `robots.txt`
- ✅ SEO-friendly URLs مع Arabic slugs

---

## 🛠️ التقنيات

- **Backend**: PHP 8.4, MySQL/MariaDB, PDO
- **Frontend**: Vanilla JS (ES2020), CSS Custom Properties, RTL
- **Database**: 16 جدول مع Foreign Keys و Full-text indexes
- **Architecture**: API REST + Session Auth + CSRF

## 📁 هيكل المشروع

```
haraj-yemen/
├── backend/
│   ├── lib/                # security, env, upload, mailer
│   ├── ads.php             # CRUD + bump + sold + archive
│   ├── auth.php            # login + register + OTP + forgot
│   ├── chat.php            # threads + messages + images
│   ├── admin.php           # stats + users + reports
│   ├── user.php            # profile + update_profile + reviews
│   ├── notifications.php
│   ├── reports.php
│   ├── commission.php
│   ├── csrf.php
│   ├── presence.php
│   ├── categories.php / cities.php
│   ├── config.php          # API helpers
│   └── router.php          # API router
├── frontend/
│   ├── assets/css/style.css   # Premium Design System v3.0
│   ├── assets/js/app.js       # Core JS (API, toast, CSRF, helpers)
│   ├── includes/
│   │   ├── header.php          # Unified header with sticky nav
│   │   ├── footer.php          # Footer + mobile bottom nav
│   │   └── icons.php           # SVG icon library (60+ icons)
│   ├── index.php           # Homepage with hero + filters + grid
│   ├── auth.php            # Login + Register + Forgot
│   ├── ad.php              # Ad detail + gallery + share + chat
│   ├── post.php            # Create/Edit ad (8 image upload)
│   ├── my_ads.php          # Owner's ad management
│   ├── settings.php        # 4 tabs: profile/password/verify/danger
│   ├── messages.php        # Chat UI with sidebar + body
│   ├── notifications.php   # Notification list
│   ├── favorites.php       # Favorites grid
│   ├── user.php            # Public profile + stats + ads
│   ├── admin.php           # Admin dashboard (6 views)
│   ├── commission.php      # Commission info + proof form
│   └── sitemap.php         # Dynamic XML sitemap
├── scripts/db_setup.php
├── .env / .env.example
├── .htaccess
└── README.md
```

## 🚀 التشغيل المحلي

```bash
# 1. Clone
git clone https://github.com/InnoGuardTech/web.git
cd web

# 2. Setup environment
cp .env.example .env
# Edit .env with your DB credentials

# 3. Install MariaDB and create DB
sudo mysql -e "CREATE DATABASE haraj_db CHARACTER SET utf8mb4;
CREATE USER 'haraj_user'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL ON haraj_db.* TO 'haraj_user'@'localhost'; FLUSH PRIVILEGES;"

# 4. Initialize DB schema + demo data
php scripts/db_setup.php

# 5. Start dev server
php -S localhost:8000 -t .

# 6. Open in browser
# http://localhost:8000/frontend/index.php
```

## 🧪 حسابات تجريبية

| الدور | الجوال | كلمة المرور |
|------|--------|-------------|
| 👑 Admin | 777111111 | Admin@123 |
| 🏪 Seller | 777222222 | User@123 |
| 🏪 Seller | 777333333 | User@123 |
| 🛒 Buyer | 777444444 | User@123 |

## 📋 جدول الـ Endpoints

```
GET  /backend/router.php?route=csrf                    # Get CSRF token
POST /backend/router.php?route=auth&action=login       # Login
POST /backend/router.php?route=auth&action=register    # Register
POST /backend/router.php?route=auth&action=forgot_password
POST /backend/router.php?route=auth&action=reset_password
POST /backend/router.php?route=auth&action=send_otp
POST /backend/router.php?route=auth&action=verify_otp
POST /backend/router.php?route=auth&action=change_password
POST /backend/router.php?route=auth&action=delete_account
POST /backend/router.php?route=auth&action=logout

GET  /backend/router.php?route=ads&action=list          # +filters
GET  /backend/router.php?route=ads&action=my_ads
GET  /backend/router.php?route=ads&action=favorites
POST /backend/router.php?route=ads&action=create
POST /backend/router.php?route=ads&action=update
POST /backend/router.php?route=ads&action=delete
POST /backend/router.php?route=ads&action=bump
POST /backend/router.php?route=ads&action=mark_sold
POST /backend/router.php?route=ads&action=archive
POST /backend/router.php?route=ads&action=reactivate
POST /backend/router.php?route=ads&action=toggle_favorite
POST /backend/router.php?route=ads&action=add_comment

GET  /backend/router.php?route=chat&action=threads
GET  /backend/router.php?route=chat&action=messages
POST /backend/router.php?route=chat&action=send
POST /backend/router.php?route=chat&action=send_image

GET  /backend/router.php?route=user&action=profile&id={id}
POST /backend/router.php?route=user&action=update_profile

GET  /backend/router.php?route=notifications&action=list
POST /backend/router.php?route=notifications&action=mark_all_read

POST /backend/router.php?route=reports&action=create

GET  /backend/router.php?route=admin&action=stats
GET  /backend/router.php?route=admin&action=users
GET  /backend/router.php?route=admin&action=ads
GET  /backend/router.php?route=admin&action=reports
GET  /backend/router.php?route=admin&action=commissions
POST /backend/router.php?route=admin&action=ban_user
POST /backend/router.php?route=admin&action=unban_user
POST /backend/router.php?route=admin&action=delete_ad
POST /backend/router.php?route=admin&action=resolve_report
POST /backend/router.php?route=admin&action=approve_commission

GET  /backend/router.php?route=categories&action=list
GET  /backend/router.php?route=cities&action=list

POST /backend/router.php?route=commission&action=submit
```

## 🔐 ملاحظات الإنتاج

قبل النشر للإنتاج:

1. **غيّر `.env`**:
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - استخدم بيانات قاعدة بيانات قوية
2. **فعّل HTTPS** وضع `COOKIE_SECURE=true`
3. **استخدم PHPMailer/SMTP حقيقي** بدلاً من log
4. **استخدم Twilio/Vonage** لإرسال SMS الفعلي
5. **فعّل Web Push** بمفاتيح VAPID
6. **اضبط Apache/Nginx** لاستخدام `.htaccess` المرفق
7. **اعمل Backups دورية** لقاعدة البيانات

## 📝 الترخيص

© 2026 InnoGuardTech — جميع الحقوق محفوظة.

---

**Built with ❤️ for Yemen 🇾🇪**
