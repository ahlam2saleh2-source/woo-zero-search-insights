# Woo Zero Search Insights v1.1.0 — Release Notes

**Date:** 2026-10-12 · **Type:** Feature release · **Author:** az-soft4media

## Headline
The plugin no longer only *reports* search gaps — it now **closes them**. Smart Mappings lets you bind any zero-result term to an existing product so the next visitor finds something instead of an empty page.

## What's new
| Feature | What it does |
|---------|--------------|
| 🔗 **Smart Mappings** | Map zero terms → product / category / URL. Three storefront behaviors (none / add-to-results / redirect), usage counter, dedicated admin page |
| ✉️ **Email digest** | Daily/weekly HTML summary (auto-RTL), stats + top 10 zero terms + delta, test-send button |
| 📱 **Telegram + threshold** | New channel, per-channel test buttons, alert only after N repeats in 24h |
| 🤖 **Bot filtering** | Crawlers & monitoring tools excluded from stats (on by default, `wzsi_bot_patterns` filter) |
| 🇸🇦 **Arabic normalization** | Hamza/taa-marbuta/yaa folding + tashkeel removal, batched auto-migration |
| 🔎 **Log filters** | Text search + from/to dates + type filter, pagination-safe |
| 📈 **Chart comparison** | Dashed previous-period line + colored delta on the failure-rate card |
| ⌨️ **WP-CLI & REST** | `wp wzsi stats|top|logs|clear|prune`, read-only `wzsi/v1` endpoints (no PII) |
| 💾 **Settings backup** | JSON export/import across stores |
| ⚡ **Performance** | Version-keyed 5-min dashboard cache with instant invalidation |

## Compatibility
WordPress 5.6+ (tested 7.1) · WooCommerce 5.0+ (tested 11.2) · PHP 7.4+ · HPOS + Blocks declared · Multisite · SQLite (official integration)

## Upgrade path
Direct overwrite from 1.0.x — mappings table auto-created, old terms re-normalized in safe batches (progress on Health Check page). Nothing is deleted on deactivation.

## Assets
- `woo-zero-search-insights-1.1.0.zip` — installable plugin
- `woo-zero-search-insights-v1.1.0-package.zip` — full sales package
- `docs/installation-user-guide-AR-EN.pdf` — 18-page bilingual guide
- `marketing/gumroad-listing-kit-AR-EN.pdf` — ready-made listing copy
- `screenshots/` — 7 official screenshots (incl. Smart Mappings)
