# Woo Zero Search Insights v1.2.0 — Release Notes

**Date:** 2026-10-12 · **Type:** Feature release · **Author:** az-soft4media

## Headline
The loop is now closed **with the customer**: the moment a searched-for product exists, the store can reach everyone who wanted it — one click, one customer or a whole audience, in-store or by email, in their language.

## What's new
| Feature | What it does |
|---------|--------------|
| 🔔 **Customer notifications** | Notify ONE customer (button next to any failed search) or ALL registered users who searched a term (grouped view) |
| 🏪 + ✉️ **Two channels** | In-store banner (next visit, dismissible, product link) + HTML email (auto-RTL) — admin picks per send; guests clearly excluded |
| 🌍 **Editable AR/EN templates** | Commercial defaults, per-recipient placeholders, per-user language on auto |
| 👤 / 📦 **Searches by user & by product** | Filter the log by user (see their failed searches) or grouped by product with audience size (registered vs guests) |
| ✉️ **Email health check** | wp_mail/SMTP diagnostics + real test message with the exact failure reason |
| 📜 **Notifications log** | Recipient, term, channel, language, status, failure reasons — on Health Check |
| 🔒 **Privacy extended** | Eraser clears in-store inbox + notification rows; exporter includes them |

## Compatibility
WordPress 5.6+ (tested 7.1) · WooCommerce 5.0+ (tested 11.2) · PHP 7.4+ · HPOS + Blocks declared · Multisite · SQLite (official integration)

## Upgrade path
Direct overwrite from 1.0.x / 1.1.0 — the notifications table auto-creates via safe dbDelta; nothing is deleted on deactivation.

## Quality
42 new E2E tests — **70/70 total passing**, zero PHP warnings, nonce + capability on every endpoint, full AR/EN i18n (+ .pot).

## Assets
- `woo-zero-search-insights-1.2.0.zip` — installable plugin
- `woo-zero-search-insights-v1.2.0-package.zip` — full sales package
- `docs/installation-user-guide-AR-EN.pdf` — bilingual guide (incl. notifications chapter)
- `marketing/gumroad-listing-kit-AR-EN.pdf` — ready-made listing copy
- `screenshots/` — 9 official screenshots (distinct themes: Midnight dark, Ocean, Sunset, Forest, light-AR, export dialog)
