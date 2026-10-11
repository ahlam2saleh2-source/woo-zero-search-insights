=== Woo Zero Search Insights ===
Contributors: azsoft4media
Tags: woocommerce, search, analytics, zero results, ecommerce, insights
Requires at least: 5.6
Tested up to: 6.8
Requires PHP: 7.4
WC requires at least: 5.0
WC tested up to: 10.1
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Discover what your customers search for — and never find. Full description in English & Arabic below.

== Description ==

= English =

**Woo Zero Search Insights** helps WooCommerce store owners understand real customer demand by tracking every search that returns zero results. This "golden data" reveals:

* Products your customers want but you don't sell yet — direct inventory opportunities.
* Keywords worth real marketing or SEO effort.
* The exact terms and local phrasing your audience actually uses.

**Key features**

* **Automatic tracking** — captures every failed WooCommerce search (standard + AJAX live search) with zero configuration.
* **Professional dashboard** — stat cards, daily trend chart, and a ranked table of top zero-result terms.
* **Full search log** — every query recorded with anonymized IP, device type, timestamp, and request type.
* **CSV export** — full or filtered export with UTF-8 BOM, Excel-ready and Arabic-safe.
* **Slack & Discord webhooks** — instant alerts when search gaps appear.
* **Flexible settings** — min/max term length, retention window, excluded terms, members/guests tracking.
* **Automatic cleanup** — daily scheduled task removes logs older than your retention period.
* **Privacy-first** — IP anonymization enabled by default (GDPR-friendly).
* **Instant actions** — delete a term or clear everything via AJAX, no page reloads.
* **6 admin themes** — Light / Dark / Ocean / Forest / Sunset / Midnight, plus full white-label support.
* **Bilingual** — complete Arabic & English interfaces, translation-ready (.pot included).
* **Multisite compatible.**

= العربية =

**Woo Zero Search Insights** إضافة لمتاجر WooCommerce تساعدك على فهم سلوك عملائك من خلال تتبع عمليات البحث التي لا تُرجع أي نتائج. هذه البيانات الذهبية تكشف لك:

* ما المنتجات التي يبحث عنها عملاؤك ولا تبيعها (فرص لتوسيع المخزون).
* أي الكلمات المفتاحية تستحق جهدًا تسويقيًا أو SEO.
* ما المصطلحات الفنية أو اللهجية المحلية التي يستخدمها جمهورك فعليًا.

**المزايا الرئيسية**

* **تتبع تلقائي**: يلتقط كل عمليات البحث في WooCommerce (العادية و AJAX) بدون أي إعداد مسبق.
* **لوحة معلومات احترافية**: بطاقات إحصائية ورسوم بيانية للاتجاه اليومي.
* **جدول أعلى المصطلحات بلا نتائج**: مرتب حسب التكرار مع آخر مرة بحث.
* **سجل كامل**: كل عملية بحث مع IP مجهول ونوع الجهاز والتاريخ ونوع الطلب.
* **تصدير CSV**: تصدير كامل أو مفلتر بترميز UTF-8 BOM متوافق مع Excel والعربية.
* **إشعارات Slack/Discord**: تنبيه فوري عند ظهور فجوات بحث جديدة.
* **إعدادات مرنة**: الطول الأدنى/الأقصى، مدة الاحتفاظ، استثناء مصطلحات، تتبع المسجلين/الزوار.
* **تنظيف تلقائي**: مهمة cron يومية تحذف السجلات الأقدم من المدة المحددة.
* **خصوصية**: إخفاء أجزاء من IP مفعّل افتراضيًا (متوافق مع GDPR).
* **إجراءات فورية**: حذف مصطلح أو تفريغ الكل عبر AJAX دون إعادة تحميل الصفحة.
* **6 ثيمات للوحة التحكم** (Light/Dark/Ocean/Forest/Sunset/Midnight) + دعم White-label كامل.
* **متعدد اللغات**: جاهز للترجمة (.pot متضمن) ويعمل على شبكات Multi-site.

== Installation ==

1. Upload the `woo-zero-search-insights` folder to `wp-content/plugins/` — or upload the zip file via Plugins → Add New → Upload.
   (ارفع مجلد الإضافة أو ملف zip من صفحة الإضافات في ووردبريس)
2. Activate WooCommerce if it is not already active. (فعّل WooCommerce إن لم يكن مفعّلًا)
3. Activate "Woo Zero Search Insights" from the Plugins page. (فعّل الإضافة من صفحة الإضافات)
4. Go to: Dashboard → Zero Search → Dashboard/لوحة المعلومات.
5. Start monitoring your zero-result searches! (ابدأ بمراقبة عمليات البحث بلا نتائج)

== Frequently Asked Questions ==

= Do you store customers' full IPs? / هل تُخزَّن عناوين IP كاملة؟ =
No, not by default. The plugin masks the last part of the IP (last octet in IPv4) for privacy. You can change this behavior in settings.
لا بشكل افتراضي — تخفي الإضافة آخر جزء من IP حفاظًا على الخصوصية، ويمكن تعديل ذلك من الإعدادات.

= Does it track AJAX / live search? / هل تتبع البحث الفوري AJAX؟ =
Yes. Live search requests via AJAX are captured along with standard search queries.
نعم — تلتقط الإضافة عمليات البحث الفوري عبر AJAX إضافة إلى البحث العادي.

= Does it conflict with SEO or search plugins? / هل تتعارض مع إضافات SEO أو البحث؟ =
No. It listens to standard WooCommerce hooks (`woocommerce_no_products_found`, `template_redirect`) without modifying search results at all.
لا — تستمع إلى خطافات WooCommerce القياسية دون أي تعديل على نتائج البحث.

= How do I export the data? / كيف أصدّر البيانات؟ =
Click "Export CSV" on the dashboard or on the logs page — you get an Excel-compatible file.
اضغط "تصدير CSV" من لوحة المعلومات أو صفحة السجلات وستحصل على ملف متوافق مع Excel.

= Is my data deleted when I deactivate the plugin? / هل تُحذف البيانات عند تعطيل الإضافة؟ =
No. Deactivation only removes scheduled tasks; your data stays intact. Full uninstall (delete) removes everything.
لا — التعطيل يزيل مهام الجدولة فقط وتبقى بياناتك، والحذف الكامل من صفحة الإضافات يمسح كل شيء.

== Screenshots ==

1. Dashboard — stat cards and daily trend chart (Ocean theme). / لوحة المعلومات — بطاقات إحصائية واتجاه يومي.
2. Top zero-result search terms + quick actions (Forest theme). / أعلى المصطلحات بلا نتائج + إجراءات سريعة.
3. Full search logs — successful and zero-result searches together (Sunset theme). / السجل الكامل — البحوث الناجحة والصفرية معًا.
4. Full Arabic (RTL) dashboard — bilingual AR/EN. / لوحة كاملة بالعربية (RTL) — ثنائي اللغة.
5. Settings — tracking toggles, GDPR IP anonymize, retention, term length (Forest theme). / الإعدادات — التتبع والخصوصية والاحتفاظ وحدود الطول.
6. Settings — Slack/Discord webhooks, theme & language switcher, widget, white-label. / الإعدادات — الإشعارات والثيمات واللغة وWidget وWhite-label.

== Changelog ==

= 1.0.1 =
* Fixed: Slack & Discord webhooks now actually fire (the hook was never triggered). / إصلاح: إشعارات Slack/Discord تعمل الآن فعليًا.
* Fixed: webhook message strings were missing from the translations dictionary. / إصلاح: مفاتيح نصوص الإشعارات المفقودة.
* Fixed: fatal error on sites without WooCommerce (translation helper loaded too late). / إصلاح: خطأ فادح في المواقع بدون WooCommerce.
* Fixed: uninstall now clears the correct scheduled task (wzsm_daily_cleanup) and cleans all sites on Multisite. / إصلاح: الحذف يمسح الجدولة الصحيحة وكل مواقع الشبكة.
* Fixed: duplicate AJAX handlers caused double confirmations and double requests. / إصلاح: معالجات AJAX مكررة كانت تسبب طلبات مزدوجة.
* Fixed: CREATE INDEX error on MySQL hosts (MariaDB-only syntax). / إصلاح: خطأ فهرسة على استضافات MySQL.
* Fixed: repeated searches now update results_count (a zero stays zero only until it isn't). / إصلاح: البحث المتكرر يحدّث عدد النتائج.
* Fixed: unique-terms counters now display their numbers on stat cards. / إصلاح: عدّادات المصطلحات الفريدة تعرض أرقامها.
* Fixed: admin footer brand line (was never visible) + respects white-label. / إصلاح: تذييل الإصدار يظهر الآن ويحترم White-label.
* Improved: Chart.js bundled locally (no external CDN required). / تحسين: Chart.js مضمّن محليًا.
* Improved: CSV headers and AJAX messages follow the interface language (AR/EN). / تحسين: ترويسات CSV ورسائل AJAX تتبع لغة الواجهة.
* Improved: Slack/Discord alerts throttled to one per term per hour (no spam). / تحسين: إشعار واحد لكل مصطلح كل ساعة (لا سبام).
* Improved: capture debug trail is now opt-in in Settings (performance). / تحسين: سجل التشخيص يُفعّل من الإعدادات (أداء أفضل).
* Improved: excluded-terms matching is multibyte-safe (Arabic). / تحسين: فحص المصطلحات المستثناة آمن مع العربية.
* Improved: Premium UI toggle now works from settings. / تحسين: مفتاح واجهة Premium يعمل من الإعدادات.

= 1.0.0 =
* First public release. / الإصدار العام الأول.
* Zero-result search tracking (standard + AJAX).
* Total Searches counts every search: results pages, instant/AJAX search and Store API — with a real failure rate.
* Professional dashboard with charts and stat cards.
* CSV export (Excel & Arabic-safe).
* Slack & Discord webhooks.
* 6 admin themes + white-label support.
* Comprehensive settings, scheduled cleanup, GDPR-friendly IP masking.
* Official WooCommerce compatibility declarations (HPOS / custom order tables + Cart & Checkout Blocks).
* UI language auto-detects the site language on first activation (Arabic/English), with manual override in settings.

== Upgrade Notice ==

= 1.0.1 =
Bug-fix release: webhooks fixed, local Chart.js, MySQL-safe indexing, multibyte-safe Arabic exclusions, multisite-safe uninstall. / إصدار إصلاحات: الإشعارات، تضمين Chart.js محليًا، توافق MySQL، أمان العربية، حذف آمن للشبكات.

= 1.0.0 =
Initial release. / الإصدار الأول.
