# R8-E / R8-F4–F6 — Manual Discovery 0.1.2 release and post-release acceptance

**Date:** 2026-10-31 (Asia/Taipei). **Status:** **R8-E bounded engineering scope CLOSED — Owner manually released Discovery 0.1.2; F5 functional acceptance PASS; PHP error-log visibility LIMITED**. This is not an approval to merge PR #3/#5 or release unrelated R8 files.

## 0. Deployment history and approval
- Owner expressly approved R8-F4 → F6 with response `pass`: **only** `https://dev.idollmate.com` WordPress plugin `idollmate-discovery` 0.1.1 → 0.1.2, **no new backups**, **no PR merges**, no product, theme, customer, payment, DNS or Shopify write.
- The automated self-hosted DC release path was previously blocked before writing. Owner then asked about manual install, received **exact verified ZIP path on China Mac** and reported **`done`** after manual WordPress Plugin upload and **Replace current with uploaded**. The actual update was **performed manually by Owner**, not by assistant/DC.
- The WordPress target `dev.idollmate.com` is the project's **final WordPress WooCommerce site** (historically labeled `dev`). Separate `https://idollmate.com` Shopify was never targeted.

## F4 — post-install verified file and runtime readback: PASS
Read-only authorized SSH → verified WordPress `home=https://dev.idollmate.com`; actual live WP CLI output:
- `idollmate-discovery`: **version 0.1.2, status active**.
- Installed PHP SHA256: **`0b8a126785a29d99351f196eb19bc58b5fc7dc89d9935aabdc7c3391f192905d`** — exact approved original-LF Git candidate.
- Installed CSS SHA256: **`efb9b680c95b6d0c7652ef959904880e1c506fa985b5faf48581127766efbdce`** — exact approved candidate and byte-identical to original installed CSS.
- Authorized package: **6,247 bytes**, ZIP SHA256 **`6053a965238180c70f941b848a27466c840edecbe643123001f429ba32146489`**; old Windows CRLF ZIP SHA `95d1556d...` is retired and **must never be installed**.
- `woocommerce_coming_soon=yes`, `blog_public=0`, `woocommerce_currency=USD`; WP **7.1.4**, Woo **11.2.1**, Hostinger AI theme **2.1.15**.
- No Core/theme update, SQL migration or other plugin update executed by the assistant.

## F5 — actual target functional and isolation acceptance: PASS
1. **Read-only WordPress/Woo post-release contract — 40/40 PASS**: exact WordPress home, theme/runtime, live 0.1.2 PHP/CSS hashes, global noindex/Coming Soon/USD, approved R8 QA journal `cfc3ead963a72e0aa44740d5` applied and exact `[32,33,34,35]` IDs, 4 exact SKU and batch ownership, simple/variable parent 32/33 Draft + Hidden with no image/gallery, TPE/Silicone child variation parent=33, expected prices USD599/999/1099, **0 published top-level products**, **0 order-item references** to QA product IDs, preserved R7 original product IDs `13,15,16,17`. The variation posts' own `publish` internal status is expected; their **parent product is Draft/Hidden**.
2. **Actual WordPress admin QA callback — 11/11 PASS** executed under an existing administrator role in read-only WP-CLI: **7 distinct filter sets** `all=2`, `mini=1`, `full=1`, `tpe=2`, `silicone=1`, `mini+silicone=0`, `invalid=0`; exact four SKU → ID match, 2 native Draft preview buttons in all filter, price range USD599/999/1099, no public exposure.
3. **Actual logged-in Chrome admin QA page — visual PASS**: existing authorized China Mac browser session opened `/wp-admin/edit.php?post_type=product&page=idollmate-r8-qa`. The page title and screenshot verified two QA cards, categories, material filter, prices, Draft/Hidden and two preview buttons. A single temporary tab was opened/closed, with the original tab restored. Private screenshot: China Mac `/tmp/idm-r8f5-live-admin-qa-20261031.png`.
4. **Actual logged-in Chrome native Draft previews — 2/2 PASS**: WordPress generated preview URLs for `32`, `33`; browser page titles respectively `Original Example Simple Doll` and `Original Example Configurable Doll`, both on target domain with `preview=true`. Visual readback of configured product shows **$999–$1,099**, material dropdown, no authorized product image/placeholder, specs, and cart controls. Temporary tabs closed; original tabs restored. Screenshots privately stored as `/tmp/idm-r8f5-actual-preview-32-20261031.png`, `/tmp/idm-r8f5-actual-preview-33-20261031.png`.
5. **Actual external anonymous HTTP — 4/4 PASS**: target homepage `/` **200**, `/shop/` **200**, anonymous `/product/idm-r8-example-simple/` **404**, anonymous `/product/idm-r8-example-parent/` **404** (final retry after one transient Schannel handshake error). The browser authorized Draft preview does **not** imply public access.
6. Pre-release exact Hostinger AI 2.1.15 Windows Local suite had **98/98 PASS** across runtime, anonymous, manager Draft QA, variation pages, mobile and pagination; documented in `docs/uat/R8_E_HOSTINGER_AI_THEME_LOCAL_PARITY_2026-10-31.md`.

### PHP error log limitation — no false PASS claim
- Target `WP_DEBUG=OFF` and `WP_DEBUG_LOG=false`; inspected target standard paths `wp-content/debug.log`, site-root `error_log`, and domain `logs/error.log` — not present. Recursive limited-depth host-domain name search found no readable `error_log`/`php_error.log`/`debug.log`.
- **Raw PHP error-log zero-error status cannot be independently established**, so log-only verification is **LIMITED / NOT MEASURED**. Runtime HTTP/WP/Chrome page functioning, installed hash and protected data checks passed; this supports scoped release functional acceptance but is not proof there are no unseen PHP warnings or errors in Hostinger control-panel logs.
- No plugin/runtime errors were observed in the directly executed checks; there is no need for unapproved unrelated hosting reconfiguration.

## F6 — handoff and closure
- **R8-E engineering/release scope CLOSED** for its bounded **Discovery 0.1.2** manual deployment and verified functional checks. Do not interpret this as the entire R8 Catalog OS or PR #3/#5 being closed/merged.
- **F2 BACKUPS SKIPPED** exactly as the Owner requested — **no fresh full-site, database or plugin backup was created** during release. Previously saved private read-only historical 0.1.1 plugin archive exists on China Mac and was integrity-verified before deployment, but no fresh full DB recovery point exists. This residual recovery risk is explicitly accepted by the Owner's no-backup instruction.
- Source GitHub PR #3 and #5 remain **Draft / open / unmerged**; source-only PR docs updates do not redeploy code. No Shopify modification.
- If a later issue occurs, restore the exact prior 0.1.1 plugin copy under separate change scope and recheck protected batch/privacy. There is no authorized database restore snapshot from this deployment.
- **Next independent track:** R8 Catalog OS data-source readiness, controlled 2,000-SKU import preview/conflicts, authorization and bounded real Draft canary; no automatic production write.
- **Release closeout verdict:** `R8_E_DISCOVERY_0_1_2_MANUAL_RELEASE_FUNCTIONAL_PASS_LOG_VISIBILITY_LIMITED`.
