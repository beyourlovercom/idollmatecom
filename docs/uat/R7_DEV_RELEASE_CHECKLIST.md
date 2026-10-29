# R7 Dev Installation & Acceptance Checklist (not yet executed)
Date: 2026-10-28
Current status: **source QA done, on-site UAT pending explicit Owner pass**.

## Source / scope
- Source branch `feat/r7-original-catalog-v1`; no production or Dev deployment performed.
- Two original source plugins:
  1. `wordpress/plugins/idollmate-catalog/`: admin-only Woo product fulfillment metadata, no public pricing logic.
  2. `wordpress/plugins/idollmate-home/`: `[idollmate_home]` original responsive home shortcode, uses actual Woo shop/category/product data.
- WooCommerce built-in `product_brand` exists; no duplicate manufacturer taxonomy.
- WooCommerce TM Extra Product Options 7.6.2 already active; no interference, replacement, or license assertions.
- Active Dev theme: `hostinger-ai-theme` 2.1.15. Do not auto-switch theme.

## Preflight (read-only)
- [x] WordPress 7.1.3 and WooCommerce 11.2.1 identified.
- [x] PHP lint for plugin files passed on China Mac.
- [x] 8 catalog isolated smoke checks PASS.
- [x] 10 homepage isolated smoke checks PASS.
- [x] Public GitHub diff includes no competitor HTML, image, PDF, font, private archive or API secret.
- [ ] Confirm WooCommerce and theme compatibility on an **isolated** WP sandbox or approved Dev site, not only PHP stubs.
- [ ] Confirm WC product brand and legacy product editor route still exist at deployment.

## P0 indexing discrepancy (must resolve before publishing any test content)
The 2026-10-28 read-only CLI returned `wp option get blog_public` = **1** (indexing permitted), contrary to the earlier archived baseline of 0. The publicly fetched homepage did not show a `robots noindex` meta tag, and `robots.txt` advertised `/wp-sitemap.xml`.
- [ ] After explicit Dev approval, set `blog_public` to **0** under WP Settings → Reading, or approved WP-CLI equivalent.
- [ ] Verify homepage, /shop/, /product/ and policy pages emit robots noindex/nofollow as appropriate; check relevant X-Robots headers, canonical links, sitemap.
- [ ] Do not rely on a mere robots.txt Disallow as a deindexing mechanism. If confidentiality is required, add authentication/access control, separately approved.
- [ ] Do not touch `idollmate.com` production / Shopify DNS.

## Controlled Dev installation (requires Owner pass)
1. Capture current plugin list, WP files, DB snapshot and rollback reference. Avoid changing Hostinger host plan or incurring cost.
2. Package each plugin from approved Git SHA, lint and verify SHA-256. Ensure packaging excludes test/third-party reference material.
3. Install both plugins to **Dev only**, one at a time. No default product/category or admin user creation.
4. Confirm no new top-level Admin menu and native Woo product editing still works; new fields appear in classic shipping tab. Product block editor support is **not claimed**.
5. Create one original **draft** test product with consented placeholder data, save allowed + invalid metadata, verify no price/stock changes, then remove test data.
6. Create a **draft** home preview page using `[idollmate_home]`. Test on desktop/mobile; do not automatically set it as front page until separately accepted.
7. Verify Woo options plugin add-on price and product variation totals don't drift; defer real checkout until payment **test mode** and taxes/shipping setup validated.
8. Check search indexing, PHP error logs, performance and rollback.

## Rollback
- Deactivate `idollmate-home`, then `idollmate-catalog`; existing Woo SKU/stock/price unaffected.
- Restore earlier page front-page assignment if one was changed; restore backups only if needed, with Owner confirmation.
- Optional `_idm_*` metadata remains inert on deactivation, no destructive database migration.
- Revert Dev modifications only; production remains untouched.

## Release gates
**NOT APPROVED:** plugin deployment, theme switch, real payment gateway, paid theme purchase, production DNS change, production publish.
**APPROVED FOR REVIEW:** code branch, docs, PR and isolated validation.
