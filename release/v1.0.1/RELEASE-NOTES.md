# Release v1.0.1 — Woo Zero Search Insights

**إصدار جاهز للبيع والاستخدام. كل ما تحتاجه موجود في هذا المجلد.**
**Production-ready release. Everything you need is in this folder.**

---

## Contents — محتويات المجلد

| File / الملف | Description / الوصف |
|------|-------------|
| `woo-zero-search-insights-1.0.1.zip` | Installable plugin zip — ملف الإضافة الجاهز للتثبيت: Plugins → Add New → Upload Plugin |
| `woo-zero-search-insights-v1.0.1-package.zip` | Complete sales package — الحزمة الكاملة لـ Gumroad (كل ما دونها مضغوط داخلها) |
| `screenshots/` (6 files) | Product screenshots: dashboard, top terms, logs, Arabic RTL, settings — لقطات المنتج |
| `docs/installation-user-guide-AR-EN.pdf` | 16-page bilingual installation & user guide — دليل التثبيت والاستخدام (عربي + إنجليزي) |
| `docs/CHANGELOG-1.0.1.txt` | Full changelog — سجل التغييرات الكامل |
| `docs/LICENSE-GPLv2.txt` | GPLv2 license — نص الترخيص |
| `marketing/gumroad-listing-kit-AR-EN.pdf` | Ready-to-paste Gumroad listing copy (AR + EN) — نصوص صفحة البيع جاهزة للنسخ |
| `marketing/marketing-cover.png` | Product cover image 1344×768 — صورة الغلاف |
| `README-START-HERE.txt` | Quick start — ابدأ من هنا |

---

## What's new in 1.0.1 — الجديد في الإصدار 1.0.1

**19 issues fixed: 5 critical, 6 medium, 8 quality improvements.**

### العربية

- **إصلاحات حرجة:** إشعارات Slack/Discord لم تكن تعمل إطلاقاً (الخطاف لم يكن يُطلق) — تعمل الآن مع اختبار E2E حقيقي · انهيار فادح عند غياب ووكومرس · تنظيف خاطئ للجدولة عند إلغاء التثبيت (مع تنظيف Multisite) · معالجات AJAX مكررة تعطّل التفاعل · مفاتيح ترجمة مفقودة للإشعارات.
- **إصلاحات متوسطة:** توافق MySQL الكامل (فهرسة بدون `IF NOT EXISTS`) · استبدال النصوص العربية المثبتة بترجمة صحيحة في CSV وAJAX · placeholders ناقصة `%s` · بادئة تذييل خاطئة · `results_count` لا يتحدث عند تكرار البحث · خفض إزعاج الإشعارات (إشعار واحد لكل مصطلح كل ساعة).
- **تحسينات:** Chart.js محلي بدون CDN · تسجيل تشخيصي اختياري آمن · مطابقة آمنة للحروف العربية (multibyte) · ربط خيار الواجهة المميزة · تحديث readme وملف `.pot`.

### English

- **Critical:** Slack/Discord notifications never fired (hook not triggered) — now fully working (real E2E verified) · fatal error when WooCommerce is inactive · wrong cron cleanup on uninstall (now incl. Multisite + transients) · duplicated AJAX handlers · missing webhook i18n strings.
- **Medium:** full MySQL compatibility (index creation without `IF NOT EXISTS`) · hardcoded Arabic strings in CSV/AJAX replaced with proper i18n · missing `%s` placeholders · wrong footer prefix · `results_count` not updated on repeated searches · smart notification throttling (1 per term per hour).
- **Improvements:** Chart.js bundled locally (no CDN) · opt-in safe debug logging · multibyte-safe Arabic matching · premium UI option wired · updated readme & `.pot`.

---

## Install in 3 steps — التثبيت في 3 خطوات

1. Download `woo-zero-search-insights-1.0.1.zip`. — حمّل ملف الإضافة.
2. WordPress admin → Plugins → Add New → Upload Plugin → Install → Activate. — رفع ثم تفعيل.
3. Tools → Zero Search Insights. — افتح اللوحة من قائمة أدوات.

**Quality note / ملاحظة الجودة:** this release passed a 17-point live E2E test suite on a real WordPress 7.1 + WooCommerce 11.2 store (capture paths, webhooks, CSV, RTL, white-label, nonce security, no-WooCommerce fallback).
