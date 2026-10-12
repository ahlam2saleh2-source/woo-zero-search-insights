=== Woo Zero Search Insights ===
Contributors: azsoft4media
Tags: woocommerce, search, analytics, zero results, ecommerce, insights
Requires at least: 5.6
Tested up to: 7.1
Requires PHP: 7.4
WC requires at least: 5.0
WC tested up to: 11.2
Stable tag: 1.2.0
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

* **Automatic tracking** — captures every failed WooCommerce search (standard + AJAX live search + REST Store API) with zero configuration.
* **Professional dashboard** — stat cards, daily trend chart with previous-period comparison, and a ranked table of top zero-result terms.
* **Customer notifications (NEW in 1.2.0)** — notify a single customer or everyone who searched a term, right from the logs: in-store notification or email, with editable AR/EN templates.
* **Searches by user / by product (NEW in 1.2.0)** — filter the log by a specific user (and see which of their searches failed) or view a grouped by-product report with the audience size for each term.
* **Email health check (NEW in 1.2.0)** — one-click diagnostics of wp_mail/SMTP readiness with a real test message and the exact failure reason.
* **Smart mappings (NEW in 1.1.0)** — map a zero-result term to a product/category/URL so visitors find something instead of an empty page.
* **Scheduled email digest (NEW in 1.1.0)** — daily or weekly summary straight to your inbox.
* **Telegram alerts (NEW in 1.1.0)** — alongside Slack & Discord, with per-channel test buttons and a notification threshold.
* **Bot filtering (NEW in 1.1.0)** — crawlers no longer inflate your stats.
* **Arabic-aware normalization (NEW in 1.1.0)** — "أحذية / احذيه / أحذيةَ" now count as ONE opportunity.
* **Full search log** — every query recorded with anonymized IP, device type, timestamp, and request type; searchable and date-filterable.
* **CSV export** — full or filtered export with UTF-8 BOM, Excel-ready and Arabic-safe.
* **Flexible settings** — min/max term length, retention window, excluded terms, members/guests tracking.
* **Automatic cleanup** — daily scheduled task removes logs older than your retention period.
* **Privacy-first** — IP anonymization enabled by default (GDPR-friendly), plus WP personal-data exporter/eraser-ready design.
* **Instant actions** — delete a term or clear everything via AJAX, no page reloads.
* **WP-CLI & REST (NEW in 1.1.0)** — `wp wzsi stats|top|logs|clear|prune` and read-only `wzsi/v1` endpoints for agencies.
* **Settings backup (NEW in 1.1.0)** — export/import settings as JSON across stores.
* **6 admin themes** — Light / Dark / Ocean / Forest / Sunset / Midnight, plus full white-label support.
* **Bilingual** — complete Arabic & English interfaces, translation-ready (.pot included).
* **Multisite compatible.**

= العربية =

**Woo Zero Search Insights** إضافة لمتاجر WooCommerce تساعدك على فهم سلوك عملائك من خلال تتبع عمليات البحث التي لا تُرجع أي نتائج. هذه البيانات الذهبية تكشف لك:

* ما المنتجات التي يبحث عنها عملاؤك ولا تبيعها (فرص لتوسيع المخزون).
* أي الكلمات المفتاحية تستحق جهدًا تسويقيًا أو SEO.
* ما المصطلحات الفنية أو اللهجية المحلية التي يستخدمها جمهورك فعليًا.

**المزايا الرئيسية**

* **تتبع تلقائي**: يلتقط كل عمليات البحث في WooCommerce (العادية و AJAX و REST Store API) بدون أي إعداد مسبق.
* **لوحة معلومات احترافية**: بطاقات إحصائية ورسوم بيانية للاتجاه اليومي مع مقارنة الفترة السابقة.
* **إشعارات الزبائن (جديد 1.2.0)**: أبلغ زبوناً واحداً أو كل من بحث عن مصطلح، مباشرة من صفحة السجلات: إشعار داخل المتجر أو بريد، بقوالب عربية/إنجليزية قابلة للتعديل.
* **البحث حسب المستخدم/المنتج (جديد 1.2.0)**: فلتر السجل بمستخدم محدد (وترى أي بحوثه بلا نتائج) أو عرض مجمع حسب المنتج مع حجم جمهور كل مصطلح.
* **فحص صحة البريد (جديد 1.2.0)**: تشخيص فوري لجاهزية wp_mail/SMTP مع رسالة تجريبية حقيقية وسبب الفشل بدقة.
* **الربط الذكي (جديد 1.1.0)**: اربط المصطلح الصفري بمنتج/تصنيف/رابط فيظهر للزائر بدل صفحة فارغة.
* **تقرير بريدي دوري (جديد 1.1.0)**: ملخص يومي أو أسبوعي يصل بريدك مباشرة.
* **إشعارات Telegram (جديد 1.1.0)**: بجانب Slack/Discord، مع زر اختبار لكل قناة وعتبة إشعارات ذكية.
* **فلترة الروبوتات (جديد 1.1.0)**: لا تتضخم أرقامك بزيارات عناكب الزحف.
* **تطبيع عربي ذكي (جديد 1.1.0)**: "أحذية / احذيه / أحذيةَ" تُحصى فرصة واحدة.
* **سجل كامل**: كل عملية بحث مع IP مجهول ونوع الجهاز والتاريخ ونوع الطلب — قابل للبحث والفلترة بالتاريخ.
* **تصدير CSV**: تصدير كامل أو مفلتر بترميز UTF-8 BOM متوافق مع Excel والعربية.
* **إعدادات مرنة**: الطول الأدنى/الأقصى، مدة الاحتفاظ، استثناء مصطلحات، تتبع المسجلين/الزوار.
* **تنظيف تلقائي**: مهمة cron يومية تحذف السجلات الأقدم من المدة المحددة.
* **خصوصية**: إخفاء أجزاء من IP مفعّل افتراضيًا (متوافق مع GDPR).
* **إجراءات فورية**: حذف مصطلح أو تفريغ الكل عبر AJAX دون إعادة تحميل الصفحة.
* **WP-CLI و REST (جديد 1.1.0)**: أوامر طرفية ونقاط REST للقراءة فقط للوكيلات والأتمتة.
* **نسخ الإعدادات (جديد 1.1.0)**: تصدير/استيراد JSON بين المتاجر.
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

1. Dark-mode dashboard — stat cards, previous-period delta and trend chart (Midnight theme). / لوحة الوضع الداكن — بطاقات ومقارنة ورسم بياني (ثيم Midnight).
2. Search logs filtered by a specific user — her zero-result searches with per-row Notify buttons (Ocean theme). / السجل مفلتراً بمستخدم محدد — بحوثه الصفرية مع زر إشعار لكل صف (ثيم Ocean).
3. Logs grouped by product — audience size per term (registered vs guests) and a group-notify action (Sunset theme). / العرض المجمع حسب المنتج — حجم الجمهور لكل مصطلح وزر إشعار جماعي (ثيم Sunset).
4. Customer notification composer — recipient, searched term, product picker with auto-attached link, language, delivery method and editable template (Forest theme). / نافذة إشعار الزبون — المستلم والمصطلح واختيار المنتج بلقائه واللغة وطريقة الإرسال والقالب (ثيم Forest).
5. Smart Mappings in dark theme — term-to-product management with hits counter. / الربط الذكي بالوضع الداكن — إدارة الربطات مع عداد الاستفادات.
6. Settings — customer notification templates in Arabic & English, fully editable (RTL). / الإعدادات — قوالب إشعارات الزبائن بالعربية والإنجليزية قابلة للتعديل بالكامل.
7. Health Check — email-readiness diagnostics with test send and the customer notifications log (Forest theme). / فحص الصحة — تشخيص جاهزية البريد مع إرسال تجريبي وسجل إشعارات الزبائن (ثيم Forest).
8. Light dashboard with the previous-period comparison chart (Ocean theme). / لوحة فاتحة مع رسم مقارنة الفترة السابقة (ثيم Ocean).
9. Export flow — choosing save location and file name in the browser's file browser. / مسار التصدير — اختيار مسار واسم الملف من مستعرض الملفات.

== Changelog ==

= 1.2.0 =
* New: Customer notifications — notify one customer or every registered user who searched a term, right from the logs page. / جديد: إشعارات الزبائن — أبلغ زبوناً واحداً أو كل المسجلين الذين بحثوا مصطلحاً، من صفحة السجلات مباشرة.
* New: Two delivery channels — in-store notification banner (auto dismiss) + HTML email; guests are never notified. / جديد: قناتا تسليم — إشعار داخل المتجر (شريط قابل للإخفاء) + بريد HTML؛ الضيوف لا يُشعرون.
* New: Logs grouped-by-product view with per-term audience (registered vs guests). / جديد: عرض مجمع حسب المنتج مع جمهور كل مصطلح (مسجلون/ضيوف).
* New: Filter logs by a specific user (or guests only) — see each user's failed searches. / جديد: فلترة السجل بمستخدم محدد (أو الضيوف فقط) — ترى بحوث كل مستخدم الفاشلة.
* New: Editable AR/EN notification templates with placeholders ({customer_name} {term} {product_name} {product_url} {site_name}). / جديد: قوالب إشعارات عربية/إنجليزية قابلة للتعديل مع حقول تعويض تلقائية.
* New: Email health check — wp_mail/SMTP diagnostics + real test message with the exact failure reason. / جديد: فحص صحة البريد — تشخيصات + رسالة تجريبية حقيقية مع سبب الفشل بدقة.
* New: Customer notifications log (who was notified, when, via which channel, and failures with reasons). / جديد: سجل إشعارات الزبائن (من أُشعر، متى، بأي قناة، والإخفاقات بأسبابها).
* New: Privacy integration extended — eraser clears in-store inbox + notification rows; exporter includes notifications. / جديد: توسعة الخصوصية — الماحو يمسح صندوق المتجر وسجل الإشعارات، والمصدّر يشمل الإشعارات.

= 1.1.0 =
* New: Smart Mappings — map any zero-result term to a product/category/URL, with three storefront behaviors (none / add-to-results / redirect) and a usage counter. / جديد: الربط الذكي — اربط المصطلح الصفري بمنتج أو تصنيف أو رابط مع ثلاثة سلوكيات وعداد استفادات.
* New: Scheduled email digest (daily/weekly) with top zero terms, stats and previous-period delta. / جديد: تقرير بريدي دوري يومي/أسبوعي بأعلى المصطلحات والإحصاءات والتغير عن الفترة السابقة.
* New: Telegram notifications + per-channel test buttons (Slack/Discord/Telegram). / جديد: إشعارات Telegram + زر اختبار لكل قناة.
* New: Notification threshold — alert only when a term repeats N times within 24 hours. / جديد: عتبة إشعارات — لا تنبيه إلا بعد تكرار المصطلح N مرة خلال 24 ساعة.
* New: Crawl-bot filtering (on by default, extendable via wzsi_bot_patterns). / جديد: فلترة روبوتات الزحف (مفعلة افتراضياً وقابلة للتوسعة).
* New: Arabic-aware term normalization — hamza/taa-marbuta/yaa folding + tashkeel removal, with a safe batched data migration. / جديد: تطبيع عربي ذكي للمصطلحات مع ترحيل بيانات دفعات آمن.
* New: Logs page search box and date/type filters. / جديد: بحث نصي وفلاتر تاريخ ونوع في صفحة السجلات.
* New: Previous-period comparison line in the trend chart + delta on the failure-rate card. / جديد: خط مقارنة الفترة السابقة في الرسم البياني + تغير معدل الفشل.
* New: WP-CLI commands (stats/top/logs/clear/prune) and read-only REST endpoints (wzsi/v1). / جديد: أوامر WP-CLI ونقاط REST للقراءة فقط.
* New: Settings export/import (JSON). / جديد: تصدير واستيراد الإعدادات JSON.
* Improved: 5-minute dashboard query cache with instant invalidation. / تحسين: كاش 5 دقائق لاستعلامات اللوحة مع إبطال فوري.

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

= 1.2.0 =
Feature release: customer notifications (single + group, in-store + email), user/product log views, editable AR/EN templates, email health check, notifications log. / إصدار مزايا: إشعارات الزبائن (فردي وجماعي، داخل المتجر والبريد)، عرض حسب المستخدم/المنتج، قوالب قابلة للتعديل، فحص صحة البريد، سجل الإشعارات.

= 1.1.0 =
Feature release: Smart Mappings, email digest, Telegram, bot filtering, Arabic normalization with auto-migration, log filters, chart comparison, WP-CLI/REST. / إصدار مزايا: الربط الذكي، التقرير البريدي، Telegram، فلترة الروبوتات، التطبيع العربي مع ترحيل تلقائي، فلاتر السجلات، مقارنة الرسم البياني، WP-CLI/REST.

= 1.0.1 =
Bug-fix release: webhooks fixed, local Chart.js, MySQL-safe indexing, multibyte-safe Arabic exclusions, multisite-safe uninstall. / إصدار إصلاحات: الإشعارات، تضمين Chart.js محليًا، توافق MySQL، أمان العربية، حذف آمن للشبكات.

= 1.0.0 =
Initial release. / الإصدار الأول.
