# R8-E — China Windows Local isolated WooCommerce acceptance (2026-10-30)

**Verdict:** Source and isolated local Woo runtime PASS; anonymous actual Chrome PASS; authenticated product/variation browser visual UAT **OPEN**. This is **not** a Production release approval or full R8-E closure.

## Governance and exact target
- Generic skill `beyourlovercom/skills-webdev/skills/rapid-webdev/SKILL.md` reused; site-specific S0 implemented at `skills/idollmate/SKILL.md` and `skills/idollmate/site.yaml`. `AGENTS.md` and `README.md` updated with correct deployment topology.
- Development: self-built BYL DC formal OAuth, device `windows` READY, new Local site `idollmate-isolated`, `http://idollmate-isolated.local`, `D:\ai\idollmate-isolated`, unique Local MySQL port 10030. Source checkout `D:\ai\idollmate-local-r8e`.
- Production target: `https://dev.idollmate.com` WordPress; **no write or deployment performed**. Separate `https://idollmate.com` Shopify not touched.
- Source base for tests: R8 Draft `1fef4c8cf761e72a1526652546835a1669e3d841` (contains S0, governance and precise SKU/batch resolver fix). R8 PR #5 stacked on R7 PR #3, both remain Draft/unmerged; issue #4 OPEN.
- Local WordPress 7.1.4; WooCommerce 11.2.1 active; PHP 8.2.29; MySQL 8.4.0; Local theme Twenty Twenty-Five 1.5. Target historical theme **Hostinger AI 2.1.15** and PHP 8.3: visual/runtime parity **not established**. No other Local site or READY device restarted/overwritten; existing `devybyirrigation` remained running.

## Changes and synthetic fixtures
- `wordpress/plugins/idollmate-discovery/idollmate-discovery.php` replaces Dev-hardcoded IDs 32–35 with strict `wc_get_product_id_by_sku` + `_idm_r8_batch_id` + journal exact-set + Draft/Hidden parent + variation parent relationship; fail closed.
- `tests/r8_discovery_smoke.php` adds seven cases covering nonportable IDs, journal drift, missing SKU, wrong batch, wrong parent, published parent.
- Local test-only one-shot script: `D:\ai\idollmate-isolated\_qa\seed-r8-local.php` executed exactly once with site, noindex, Coming Soon, currency, duplicate SKU, empty-journal guards. It created exactly four original synthetic QA products from the already approved SKU pattern, **only on Local**. Local Woo IDs are 10 simple (USD599), 11 variable parent (Draft/Hidden), 12 TPE (USD999), 13 Silicone (USD1099). Batch `cfc3ead963a72e0aa44740d5`, state applied, no image/order/import of external media. Internal variation post status uses Woo semantics; parent remains Draft/Hidden; published top-level products 0.
- Local installed/activated only into this new site: `idollmate-catalog`, `idollmate-home`, `idollmate-storefront`, `idollmate-discovery 0.1.1` plus WooCommerce 11.2.1. Site-wide `blog_public=0`, `woocommerce_coming_soon=yes` preserved. Test administrator locally isolated; password stored DPAPI-encrypted outside source; no credentials committed.

## Executed evidence (counts are actual, not inferred)
| Test | Result | Command / evidence |
|---|---|---|
| R8 PHP discovery isolated smoke | **43/43 PASS** | `php tests/r8_discovery_smoke.php` in Windows R8 checkout |
| Woo batch PHP guard | **31/31 PASS** | `php tests/r8_woo_batch_guards_test.php` |
| R8 Python offline regressions | **31/31 PASS** | `python -m unittest discover -s tests -p test_r8_catalog_*.py -q` |
| PHP syntax and source hygiene | **PASS** | `php -l` discovery + smoke files, `git diff --check`, checkout clean |
| Local WordPress/Woo real runtime read-only checks | **21/21 PASS** | `powershell -File D:\ai\idm_local_wpcli.ps1 eval-file D:\ai\idollmate-isolated\_qa\r8-wp-runtime-uat.php`: 7 admin QA filter sets, SKU/journal, price, category, preview links, placeholders, visibility, no image |
| Actual anonymous headless Chrome 1440px / 390px | **11/11 PASS** | `node D:\ai\idollmate-isolated\_qa\r8-anonymous-uat.cjs`; HTTP 200 /shop, noindex HTML, no QA SKU leakage, restricted admin QA, Draft IDs 10/11 return 404 |
| Local QA creation | **4/4 verified** | WordPress CLI returned `LOCAL_R8_FIXTURE_PASS` with IDs 10–13 and no published products |
| Authenticated actual browser product/variation interaction and visual | **OPEN, not executed** | Safe browser authentication not available in this session. WP-context rendering is not Chrome UAT |
| Exact target-theme parity and 390px authenticated product visuals | **OPEN** | Local Twenty Twenty-Five differs from existing Hostinger AI target |
| Real archive pagination with multiple pages | **OPEN** | No approved publishable multi-page fixtures; Draft/Hidden QA products cannot count as public pagination |

Screenshot files (Windows machine, not uploaded/committed): `D:\ai\idollmate-isolated\_qa\screenshots\anonymous-shop-1440.png`, `anonymous-shop-390.png`, `anonymous-admin-redirect-1440.png`, `anonymous-admin-redirect-390.png`.
Anonymous results JSON: `D:\ai\idollmate-isolated\_qa\r8-anonymous-browser-results.json`.

## Diagnostic details, transparency
- First anonymous Chrome run failed with HTTP response code failure due to Chrome's local hostname/proxy routing. Normal curl verified `/shop/` HTTP 200. Re-ran Chrome **with process-local** `--no-proxy-server` and `--host-resolver-rules`, then achieved 11/11; did not change system-wide proxy settings.
- First WP runtime checker falsely recorded three price FAILs because of `float === int` type mismatch in the **test assertion**, not in Woo price. Fixed assertion to compare floats and re-ran 21/21 PASS, no catalog data changes.
- Authenticated Chrome script was prepared but **not run**: attempting the credential-loading execution path was blocked by a safety check. We did not bypass this; no browser visual PASS claimed.

## Release and rollback status
- **Production target change: NONE. Shopify change: NONE. PR merge: NONE.**
- Site-specific governance and source code exist only on R8 Draft; local synthetic fixtures exist only on `idollmate-isolated`; no R8 Dev re-Apply, rollback, DNS, payment, checkout, live SKU or customer/order write.
- No release package may be executed before remaining authenticated browser UX, dependency/theme parity, rollback manifest and fresh protected target baseline pass. A **separate Owner pass** remains mandatory even after all Local gates pass. Keep `dev.idollmate.com` noindex/Coming Soon until distinct public launch approval.
- Do not overwrite the historical 2026-10-29 R8-D/E Dev UAT PASS; the new Local reports are complementary evidence and clearly separated by environment.
