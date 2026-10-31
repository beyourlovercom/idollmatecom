# YourDoll → iDollmate owner-authorized private reconstruction: D0–D2 source baseline

**Date:** 2026-10-31 Asia/Taipei. **Source ownership:** Owner explicitly states `yourdoll.com` is their own website, and authorizes a high-fidelity **private Windows Local** backup/replica with `beyourlovercom/skills-webdev/skills/duplicate-site-skills` (main). This supersedes the earlier working assumption that YourDoll was a third-party competitor. Publication and SEO indexing remain independent Owner gates.

**Target layout:** Windows Local isolated `D:\ai\idollmate-owner-mirror\` for reference backup/visual reconstruction; existing `D:\ai\idollmate-isolated\` WordPress site remains unmodified. The WordPress final target `https://dev.idollmate.com` is not a staging hop: do not erase/reinstall or deploy without separate explicit approval. `https://idollmate.com` is an entirely independent Shopify site: never touch it. PR #3 / #5 remain Draft/unmerged.

## ACTUAL new local deliverable
- The previously verified, private China Mac source archive was **transferred to Windows only** over the user's connected encrypted Tailscale network by one-shot receiver pinned to China Mac IP and exact file size/hash. The receiver exited after successful SHA verification.
- Private Windows archive `D:\ai\idollmate-owner-mirror\source-private\yourdoll-r2-r6-historical-93pages-20261028.tar.gz`; 8,560,097 bytes; SHA256 `c176bdba32794e24bafe6be41d60c4f1280a33007f94f2d0e86eb196ef40aa22`. It is **historical sample evidence, not a current-site/full-site backup**.
- Archive extracted safely to `D:\ai\idollmate-owner-mirror\source-private\yourdoll-historical-originals-20261028\`, with traversal/symlink guard; 101 original files, including **93 HTML snapshots**, all 93 manifest SHA256 and lengths independently verified. Original file snapshots marked read-only where supported.
- Private machine-readable audit: `D:\ai\idollmate-owner-mirror\source-private\owner-archive-audit-20261031.json`. Do not publish originals or private asset URL inventory in this public repo.
- Representative saved reference HTMLs: `snapshots/01-home.html` (997,122 bytes, 488 image tags and 90 stylesheet references); `snapshots/02-all-sex-dolls.html` (948,779 bytes, 456 image tags and 102 stylesheet references); `snapshots/05-product-sex-doll-6yd133.html` (1,293,019 bytes, 708 image tags and 103 stylesheet references). Original pages are large dynamic WooCommerce/WoodMart output, not self-contained offline visual files.

## Measured gaps — do not confuse with 100% backup
- The earlier historical source inventory includes **3,459 distinct old URL paths**, but this is *not* an exhaustive current sitemap; only 93 historical snapshots were obtained (plus a 22,008-resource URL index, **0 archived binary assets**).
- Fresh normal HTTP GET from authorized China Mac returned **403** for source homepage, one WordPress image, one JS and one font resource. No access-control bypass or unapproved scraping attempted. Current live page/sitemap/version, full theme CSS/JS, photos/video, database and dynamically rendered store behavior are **not yet backed up**.
- Google Drive file search did not identify any ready-made YourDoll full-site ZIP in accessible items.
- **D0 private-use ownership permission:** user-provided PASS. **D1 current-source access:** BLOCKED_SOURCE_403. **D2 historical 93-page sample integrity:** PASS; **complete current-source backup:** OPEN. **D3 actual high-fidelity storefront rebuild:** NOT STARTED; do not mislabel the archived HTML as finished storefront.
- Realistic near-100% visual/structural fidelity requires obtaining the Owner's original WordPress hosting backup (site root/wp-content/wp-content/uploads/theme/plugins plus database; credentials and customer/order/payment/secrets kept out of replica), or legitimate origin-site administrator export with all needed media. Never try Cloudflare challenge bypass. Treat checkout and sensitive data independently.

## Visual-first next sequence
1. Owner-provided source hosting/WordPress backup or authorized hosting admin access; read-only source origin audit / immutable backup / current-page and asset inventory. **This is the primary blocker**, not Catalog OS.
2. In isolated Windows Local, render actual **homepage**, **all dolls collection**, **representative variable product detail**, preserving original Header/Footer/product cards/hero/gallery/menus from the complete source. First-viewport screenshots desktop 1440px + mobile 375px; compare against genuine source, record visual differences. Do not fabricate missing photos.
3. Expand authorized source categories/pages by route manifest and resource hashes; do not falsely claim 100% when asset or runtime compatibility differs.
4. After Owner visual PASS, separately approve replacing only the existing WordPress front end at `dev.idollmate.com`. Keep Coming Soon/noindex, QA records and unrelated Shopify untouched until separate gates.

**No WordPress/Shopify/DNS production write, no original-site change, no paid services and no PR merge were performed here.**
