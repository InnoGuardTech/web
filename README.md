# 🏆 حراج اليمن الفاخر — v4.1 Premium Edition

> منصة الإعلانات المبوبة الأفخم في اليمن — تصميم استثنائي، أمان متقدم، وتجربة استخدام لا مثيل لها.

[![Version](https://img.shields.io/badge/version-4.1.0-blue.svg)](https://github.com/InnoGuardTech/web)
[![PHP](https://img.shields.io/badge/PHP-7.4+-purple.svg)]()
[![License](https://img.shields.io/badge/license-MIT-green.svg)]()
[![Status](https://img.shields.io/badge/status-Production%20Ready-brightgreen.svg)]()

---

## ✨ ما الجديد في v4.1؟

### 🔧 تحسينات تقنية جذرية
- **تنظيف المستودع**: حذف جميع الملفات المكررة والاحتياطية لضمان استقرار الكود.
- **إصلاح `config.php`**: حذف الدوال المكررة وتحسين أداء النظام.
- **لوحة إدارة موحدة**: دمج التحسينات المتقدمة في ملف `admin.php` الأساسي.
- **سكربت إعداد موحد**: استخدام سكربت واحد شامل لإعداد قاعدة البيانات MySQL.

### 🎨 تصميم فاخر متطور
- **نظام تصميم احترافي v4.0** مستوحى من Apple وStripe وLinear.
- **Glassmorphism** و **Animations** سلسة في جميع أنحاء المنصة.
- **Dark Mode** محسّن مع توهج وألوان مخصصة.

---

## ⚙️ التثبيت

### 1. استنساخ المستودع
```bash
git clone https://github.com/InnoGuardTech/web.git
cd web
```

### 2. إعداد البيئة
```bash
cp .env.example .env
# قم بتعديل إعدادات قاعدة البيانات في ملف .env
```

### 3. تثبيت قاعدة البيانات
```bash
php scripts/db_setup.php
```

---

## 🗂️ بنية المشروع النظيفة

```
web/
├── 📁 backend/              # API + Business Logic
│   ├── lib/                 # المكتبات (auth, security, upload)
│   ├── config.php           # مساعدات API
│   └── auth.php             # نظام المصادقة الموحد
├── 📁 frontend/             # الواجهة الأمامية
│   ├── 📁 assets/           # CSS, JS, Images
│   ├── 📁 includes/         # Header, Footer, Icons
│   ├── index.php            # الصفحة الرئيسية
│   ├── admin.php            # لوحة الإدارة المحسّنة
│   └── auth.php             # واجهة تسجيل الدخول
├── 📁 scripts/
│   └── db_setup.php         # إعداد قاعدة البيانات الموحد
├── config.php               # الإعدادات الرئيسية للمنصة
└── .env.example             # نموذج إعدادات البيئة
```

---

## 🔐 الأمان
- ✅ تشفير كلمات المرور بـ bcrypt.
- ✅ حماية CSRF و SQL Injection.
- ✅ تنظيف المدخلات وحماية XSS.
- ✅ Rate limiting على العمليات الحساسة.

---

## 🤝 المساهمة
نرحب بالمساهمات! يرجى فتح Pull Request لأي تحسينات مقترحة.

---

<div align="center">

### Built with ❤️ for Yemen 🇾🇪

**حراج اليمن الفاخر** © 2026 — جميع الحقوق محفوظة

</div>
