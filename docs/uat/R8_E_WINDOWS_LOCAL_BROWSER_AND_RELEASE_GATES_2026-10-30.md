# R8-E — Windows Local actual Chrome and controlled release preflight (2026-10-30)

**Classification:** actual Local execution results, not a remote Production approval. **Overall: PARTIAL PASS; release blocked by two remaining verifications.**

## Frozen environments
- Only development instance: China `windows` device through formal BYL DC OAuth; `D:\ai\idollmate-isolated`, `http://idollmate-isolated.local`, separate MySQL port 10030, WordPress 7.1.4, WooCommerce 11.2.1, PHP 8.2.29, Twenty Twenty-Five 1.5, `idollmate-discovery` updated **on Local only** to 0.1.2.
- Source: `D:\ai\idollmate-local-r8e`, R8 stacked Draft PR #5 against R7 Draft PR #3. Neither PR was merged.
- Official WordPress *production target*: `https://dev.idollmate.com`; read-only public HTTP 200, PHP header 8.3.36, public theme `hostinger-ai-theme` **2.1.15** (verified by its actual public `style.css` on 2026-10-30), homepage robot directive **noindex,nofollow**.
- Distinct Shopify `https://idollmate.com`: no access/write/deployment/DNS changes.
- The target's WooCommerce/plugin live version, target QA journal and exact installed file hashes were **not independently re-read via authenticated WP-CLI in this round**. The last historical real-Dev readback is `docs/uat/R8_D_E_DISCOVERY_AND_PRODUCT_UAT_2026-10-29.md`; never confuse those dates.

## Browser UAT (genuine headless Google Chrome, isolated Local only)
Local test programs live outside WP webroot at `D:\ai\idollmate-isolated\_qa`. Chrome uses process-scoped `--no-proxy-server` and a single local-host resolver rule, not system browser modifications. External host requests in the test context are blocked.

1. **Anonymous privacy gate** (prior handoff): 11/11 PASS, 1440/390 widths, noindex, anonymous blocked from manager QA, Draft products 404, no visible original QA SKUs.
2. **Real multi-page Woo archive** (new): **30/30 PASS** in 1440/390 Chrome. Original synthetic 30 published Local-only disposable SKUs `IDM-R8-LOCAL-PAGE-001..030` were created behind global noindex, with Coming Soon temporarily disabled **on this Local only**. Verified shop page 1/page 2, TPE page 1/page 2 with query parameter preserved (20 TPE fixtures), Silicone-only results (10), unknown material fail-closed, HTTP 200, zero horizontal overflow. The archive rendered 16/14 items across pages. Results: `_qa\pagination-browser-results.json`. Screenshots: `screenshots\pagination-unfiltered-page1-{1440,390}.png` and `screenshots\pagination-tpe-page2-{1440,390}.png`.
3. **Real customer-facing native Woo product detail + variation** (new): **18/18 PASS**, 1440/390 Chrome. Distinct synthetic temporary simple USD599 and variable TPE USD999 / Silicone USD1099. Verified native material selector, selected variation prices, two real customer viewport sizes, no horizontal overflow, images refer only to Woo's local native `woocommerce-placeholder.webp`. Results: `_qa\r8-product-browser-results.json`. Screenshots: `screenshots\local-simple-{1440,390}.png`, `local-variable-{tpe,silicone}-{1440,390}.png`. **This validates actual Local Woo customer template, NOT authenticated Draft previews or Hostinger AI theme visual parity.**
4. **Real Local admin-role PHP context** (prior handoff; rerun after 0.1.2): **21/21 PASS** using `_qa\r8-wp-runtime-uat.php`, exact four original QA SKU/Batch lookup and seven material/category filters, prices, native preview URLs, protected Draft/Hidden and no images.
5. **Isolated source** after 0.1.2 bump: R8 discovery 43/43, Woo guard 31/31, Python offline 31/31 PASS; source `git diff --check` clean.

## Data restoration (exact scope)
- Pagination QA: `_qa\r8-pagination-rollback.php` returned `LOCAL_PAGINATION_ROLLBACK_PASS`, **30 deleted, 0 remaining, 0 published**; `woocommerce_coming_soon=yes` and `blog_public=0`.
- Temporary product preview: `_qa\r8-product-browser-rollback.php` deleted exact journal-owned IDs 46–49, but its immediate *in-process final assertion* raised an error. **Do not present that invocation as PASS.** Separate read-only fresh CLI call `_qa\r8-product-rollback-fresh-verify.php` returned `LOCAL_PRODUCT_BROWSER_FRESH_ROLLBACK_PASS`: all 4 deleted, 0 published, Coming Soon yes, blog_public 0, original four Draft batch SKU retained. This proves the *current restored state*, not the original rollback command's exit-code success. In-process stale Woo query/cache is a possible explanation, not independently proven.
- Original Local R8 QA data remain Draft/Hidden (local IDs 10 simple, 11 parent, 12 TPE, 13 Silicone; exact batch `cfc3ead963a72e0aa44740d5`). The historical target IDs 32–35 were **not** used in Local.
- No orders, real customer information, paid merchant, unlicensed imagery, unrelated Local site or target-host write.

## Remaining actual blockers — do not mark PASS
- **OPEN A: authenticated actual Chrome QA detail / variations and manager card screenshots on the original Draft/Hidden batch.** Safe authenticated browser session was unavailable; an automation credential path and a Local native one-click-admin toggle were denied by safety checks. No bypass attempted. In-process authenticated WP rendering and published *temporary* browser fixtures are supporting tests, not substitutes.
- **OPEN B: full original Hostinger AI 2.1.15 target-theme parity / page-layout visual test.** Public `style.css` and target homepage prove the theme name/version but not clone/export or identical templates/styles. Local still Twenty Twenty-Five, and target PHP 8.3.36 differs from Local PHP 8.2.29. The unchanged frontend hooks already had historical target Dev functional evidence, but new 0.1.2 target behavior has not been approved or deployed.

## Decision
**Do not deploy or request Owner Production release PASS yet.** Prepare only an auditable candidate ZIP, manifest, preflight/backup/rollback plan. Exact authorized production release request becomes appropriate after OPEN A/B pass or the Owner gives a new explicit decision changing these acceptance criteria. Separately keep noindex and Coming Soon; any later launch/indexing is another authorization.
