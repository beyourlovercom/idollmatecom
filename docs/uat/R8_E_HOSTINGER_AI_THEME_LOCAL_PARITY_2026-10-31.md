# R8-E — Real Hostinger AI Theme 2.1.15 parity on isolated Windows Local

**Execution date:** 2026-10-31 Asia/Taipei. **Verdict:** **Theme file identity PASS, real Local Woo customer/manager browser acceptance PASS; broader production-runtime parity is limited.** This is *not* authority to merge PRs or deploy to the WordPress production target.

## Authoritative environment identification — no remote writes
- The pre-existing China Mac SSH alias `jutdo-hostinger` was confirmed from the former R7 checkout orchestrator pointing explicitly to `$HOME/domains/dev.idollmate.com/public_html`. **The target was independently verified** by its WP-CLI `home=https://dev.idollmate.com` and active `stylesheet=hostinger-ai-theme`. Do not infer server access from unrelated hPanel website names.
- Target read-only runtime: WordPress **7.1.4**, WooCommerce **11.2.1**, PHP **8.3.36**, Hostinger AI theme **2.1.15**, `idollmate-discovery` **0.1.1**, global `blog_public=0`, `woocommerce_coming_soon=yes`.
- Target also has **ThemeComplete Extra Product Options 7.6.2** and **LiteSpeed Cache 7.9.2** active; these are *not* included in the theme-only copy or installed for this Local theme acceptance. The target had **0 `wp_template`**, **0 `wp_template_part`** and **0 `wp_global_styles` records** at the read-only check.
- All reads and stream transfer were from the exact `wp-content/themes/hostinger-ai-theme` folder; no customer records, real Hostinger token files or uploads were copied, and nothing was written to the Hostinger filesystem or database.

## Exact private theme mirror and file authenticity
- Server folder: **3,648 regular files**, **0 symlinks**, `du -sb` **33,950,262 bytes**.
- A streaming SSH tar.gz was saved **on China Mac only** to `~/AI/idollmate/r8-private/hostinger-ai-theme-2.1.15-dev-idollmate-READONLY-20261031.tar.gz` (private; never commit to GitHub).
- TAR.GZ bytes: **9,488,551**, SHA256 **`ebfbf9d0b7a3db2546c090b7656e4f506d30050016bfc0f82783c88244b866a3`**.
- Delivered via a **one-time, single-IP-restricted private Tailscale** transfer from China Mac to Windows; the transfer server automatically shut down after successful receipt. Windows private copy: `D:\ai\idollmate-isolated\_qa\hostinger-ai-theme-2.1.15-READONLY-20261031.tar.gz`. Windows SHA256 and byte count were identical.
- Windows extract preflight checked all members remained under `hostinger-ai-theme/`, no traversal, no symlinks, only files/directories and exactly 3,648 files. Original folder installed to `D:\ai\idollmate-isolated\app\public\wp-content\themes\hostinger-ai-theme` and **only that Local site** activated the theme.
- Independent target vs Windows installed files SHA256:
  - `style.css`: **`9b4c412ecaae17f35f04785f3ea954a7c8a20e7975838a67934655554d9ac4fe`** on both.
  - `functions.php`: **`675071904e8dc72d2dd63515c091ac1ef86a40d014562cc882d344350c14b24d`** on both.
- **No original theme file was modified** for local QA; the original 3,648-file theme copy remains private and unchanged.

## Hostinger-only Local bootstrap adaptation — with failed attempts preserved
- The first WP-CLI run after switching to this theme failed: Windows paths caused `Hostinger\WpHelper\Utils::$apiTokenFile` to remain uninitialized in Hostinger's own library. The first anonymous Chrome check saw HTTP **500**. **These initial attempts failed.**
- Exact cross-platform remediation was placed in **Local `wp-config.php` only** (original file backed up outside webroot), not in the copied theme or Production: define `HOSTINGER_API_TOKEN_OVERRIDE` to a **nonfunctional synthetic local string**; `HOSTINGER_SITE_URL_OVERRIDE` to `http://idollmate-isolated.local`; `HOSTINGER_AI_WEBSITES_WP_TOKEN` to a **non-existent Local QA path**; and `WP_HTTP_BLOCK_EXTERNAL=true`. **No real Hostinger access token was read or exported.**
- After this, the fresh Local theme's initial default AI Builder not-finished state caused a shop-manager login and every admin path to be redirected to an inaccessible Builder admin route and return HTTP **403**. **This attempt also failed.**
- Hostinger's unchanged original `includes/Redirects.php` shows the builder-onboarding redirect. Its `Builder\Helper::has_finished_builder_onboarding()` expects a generated-site flag or a nonempty `hostinger_ai_version`. The target's value was read-only verified as **`1793143758`**; the same builder-finished option was set **only on isolated Local**. No account role/capability escalation, theme source edit or host API call was needed.
- Subsequent real Local readback: active `hostinger-ai-theme` **2.1.15**, WordPress **7.1.4**, Woo **11.2.1**, `blog_public=0`, Coming Soon yes; backend routes now work normally.

## Executed REAL Chrome and Woo checks on matching Hostinger theme
| Test | Verdict | Scope |
|---|---:|---|
| Woo WP-CLI runtime | **21/21 PASS** | Exact four original local Draft/Hidden QA SKU/batch, 7 restricted filters, prices, preview URL and no images |
| Anonymous Chrome | **11/11 PASS** | 1440px/390px Woo routes 200, anonymous Draft 404, QA admin restricted, no SKU leak and noindex |
| Authenticated Chrome on **original hidden Draft QA** | **18/18 PASS** | Native local `shop_manager` login; 2 cards, 3 prices, filter sets, actual authorized native Woo simple/variable Draft previews, TPE $999, Silicone $1,099, 390px no overflow |
| Temporary **published Local-only** simple/variable product | **18/18 PASS** | 1440/390 real HTML/JS native Woo variation selector, USD599/999/1099, local Woo placeholder and zero horizontal overflow |
| Real Woo shop pagination (30 separate original synthetic Local-only products) | **30/30 PASS** | 1440/390 page 1/page 2, TPE 2 pages preserving filter, Silicone-only, invalid facet fail-closed, no horizontal overflow |
- Local JSON evidence files (outside webroot): `_qa\r8-browser-results.json`, `_qa\r8-anonymous-browser-results.json`, `_qa\r8-hostinger-product-browser-results.json`, `_qa\r8-hostinger-pagination-browser-results.json`.
- Windows screenshots: `_qa\screenshots\admin-r8-{1440,390}.png`, `simple-draft-1440.png`, `variable-draft-{1440,390}.png`, `hostinger-simple-{1440,390}.png`, `hostinger-variable-{tpe,silicone}-{1440,390}.png`, `hostinger-pagination-{unfiltered-page1,tpe-page2}-{1440,390}.png`. Earlier Twenty Twenty-Five QA screenshots preserved under `screenshots\twentytwentyfive-baseline-before-hostinger31`.
- A 390px screenshot was manually reviewed: native Woo placeholder, title, variation dropdown, selected Silicone price, add-to-cart control, metadata and additional info are visible without horizontal overflow.
- The original source-only R8 tests (Discovery PHP 43/43, Woo safeguards 31/31, Python 31/31) remained unchanged and previously PASS; they were not unnecessarily re-engineered.

## Fresh independent cleanup and final Local readback
- Hostinger same-theme public product test: journal-owned four IDs **54–57** deleted and **fresh independent process** confirmed 4 deleted, 0 remaining, 0 published, Coming Soon yes, noindex 0; original four hidden QA SKUs intact.
- Hostinger same-theme multi-page test: first rollback attempt was **refused safely** because the copied local test cleanup script still required previous-date ownership tag 20261030. Corrected to match exactly the *actual* 20261031 fixture ownership, reran; **30 deleted, zero remaining, 0 published; Coming Soon yes, noindex 0**. No ownership checks were disabled or bypassed.
- Temporary `shop_manager` user and outside-webroot credential JSON were deleted, independently verified absent.
- Final WP-CLI `HOSTINGER_THEME_LOCAL_FINAL_READBACK_PASS`: Hostinger AI 2.1.15 active, WordPress 7.1.4, Woo11.2.1, **34 temporary published products deleted**, **0 published top-level**, **4 original Draft/Hidden QA SKUs present**, noindex and Coming Soon preserved, fake token only and WP outbound HTTP blocked.
- Existing other Local sites and all READY machines remained untouched; no system or Chrome restart.

## Environment differences and release decision
- **Full original theme file clone + real 1440/390 product/layout compatibility: PASS.** The former **Theme Gate B is closed for R8-E's bounded Discovery 0.1.2 manager-only release candidate**; no theme-code modification is proposed by 0.1.2.
- **NOT identical hosting environment:** Local PHP **8.2.29** vs real host **8.3.36**; target has Extra Product Options 7.6.2 and LiteSpeed Cache 7.9.2 beyond Local's test dependency set; only the Hostinger generated-state marker was copied, not sensitive hosting configuration, product media or unrelated database records. Thus do not claim pixel-identical total storefront or actual target PHP8.3 execution of 0.1.2.
- Final actual target deployment gate still requires a fresh **authenticated read-only target inventory**, protected **verified full site/DB backup and timestamped original plugin-folder backup**, exact package/hash proof and **separate Owner `pass`**. Preserve noindex, Coming Soon and all Draft QA products. **No release, PR merge, Shopify or Production write occurred in this task.**
