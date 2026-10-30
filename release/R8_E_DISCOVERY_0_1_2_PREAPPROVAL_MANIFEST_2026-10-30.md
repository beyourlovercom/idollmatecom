# R8-E discovery 0.1.2 — PRE-APPROVAL WordPress target release candidate

**Date:** 2026-10-30. **Status:** prepared package, **NOT AUTHORIZED TO DEPLOY**. Final production approval is blocked by OPEN A/B in `docs/uat/R8_E_WINDOWS_LOCAL_BROWSER_AND_RELEASE_GATES_2026-10-30.md`.

## Target and exact scope
- **Only deployment target:** `https://dev.idollmate.com`, the official final WordPress/WooCommerce target. The domain prefix is historical. **Do not deploy to or modify `https://idollmate.com` Shopify.**
- Candidate source: `beyourlovercom/idollmatecom` branch `feat/r8-catalog-os-v1` (stacked Draft PR #5 on Draft PR #3); effective software change is limited to `wordpress/plugins/idollmate-discovery/idollmate-discovery.php`.
- Version: `idollmate-discovery` **0.1.1 → 0.1.2**. Change: remove numeric Dev ID 32–35 assumptions in manager-only R8 QA, resolve exactly approved four Woo synthetic products by SKU, exact `_idm_r8_batch_id`, journal IDs, parent/variation relation, Draft/Hidden parent state, and fail-closed. Does not add product media, theme code, new tables, migrations, payment, shipping or public publication.
- Package includes only:
  - `idollmate-discovery/idollmate-discovery.php` **SHA256 `48b1392d28bcf9076c712165293cf4d2554bbec5d9d5024f1a5c9e0e79d006c6`**
  - `idollmate-discovery/assets/discovery.css` **SHA256 `dcdbc25b3bba8227f16649ba84753b913dd5ae2bbc56b19628efa4c9c179e86b`** (unchanged CSS, included for package completeness).
- Pre-approval ZIP created on Windows, *not on remote*: `D:\ai\idollmate-isolated\_qa\idollmate-discovery-0.1.2-PREAPPROVAL.zip`, **6274 bytes**, SHA256 `95d1556d8e130730ea7099882c17fd58103f8e5fb8431619bda1f6fbe2e6bc90`. Archive membership checked: exactly the two files above under `idollmate-discovery/`. If a future rebuild changes the ZIP hash, reapprove the exact new hash.
- Repository governance/docs/tests are for review only, **not** copied to WordPress webroot. In particular do not deploy or run `scripts/catalog/r8_woo_dev_guarded_apply.php`, do not re-Apply the historical batch, and do not deploy any unapproved R7 files.

## Mandatory release preflight (all read-only until Owner's separate PASS)
1. **Resolve OPEN A/B:** authenticated real Chrome Draft/Hidden Woo preview and manager QA screenshots, plus target-theme equivalent visuals/layout and plugin compatibility. Existing local functional tests and Twenty Twenty-Five Chrome results do not resolve them.
2. Confirm via authorized server access the exact WordPress install root and host; record runtime WP, PHP, Woo, active `hostinger-ai-theme` and dependencies; detect drift from historical WP7.1.4/Woo11.2.1, Hostinger AI 2.1.15. Public header currently indicated PHP8.3.36 and noindex/nofollow but authenticated runtime data require independent revalidation.
3. Read only: `blog_public=0`, Woo `woocommerce_coming_soon=yes`, USD currency, discovery currently `0.1.1` with exact installed file checksum, original QA batch `cfc3ead963a72e0aa44740d5` journal `applied`, exact SKUs `IDM-R8-EXAMPLE-SIMPLE/PARENT/TPE/SIL` and target Woo IDs (historically 32–35); parent Draft/Hidden; no published QA top-level product, no product changes/deletions/orders. Reject unexpected drift.
4. Verify GitHub Draft head and commit immutability, CI/tests, `git diff --check`, WordPress package checksum and access/security. PR #3 and PR #5 remain Draft and unmerged.
5. Verify enough disk capacity for site backup. Capture full protected WP **filesystem + DB** snapshot with hosting tools and independently inspect backup manifest/checksum; copy the **exact current live plugin folder** to a separate timestamped rollback archive outside webroot. Record current plugin PHP/CSS hashes, active-state flag and plugin version. No backup secrets or customer data in public GitHub.
6. Verify read-only restore plan with the real host path. If any backup fails, **STOP**; no plugin update.

## Controlled write phase — only after explicit Owner PASS
1. Declare exact approved `site, plugin, source SHA, ZIP SHA, backup ID`; do not touch Shopify, DNS, themes, Woo products, SEO visibility, payment or orders. Notify the owner of the bounded release.
2. Update **only** `wp-content/plugins/idollmate-discovery/` from verified 0.1.2 ZIP using a host-appropriate non-destructive install/update method after plugin-folder backup; keep activation unchanged. No WP core/theme/plugin-wide update, Woo SKU import, SQL change, domain rewrite, rewrite flush or broad cache purge.
3. Read back the installed plugin directory SHA256s and active version `0.1.2`, and check PHP log for fatal errors.
4. Re-run the restricted admin QA seven filter sets (2 total, 1 mini, 1 full-body, 2 TPE, 1 Silicone, 0 mini+Silicone, 0 invalid), USD 599/999/1099 and native Draft preview; anonymous Draft access 404; Woo pages 200; existing four SKU/journal unchanged. Preserve noindex/Coming Soon. Watch frontend and backend fatal errors.
5. Record the actual target run, timestamps, logs, hashes and acceptance results separately from Local evidence.

## Fast rollback if post-deployment gates fail
- Trigger on fatal/plugin-load error, unexpected SKU/status exposure, QA journal inconsistency, UI regression, price drift or security violation.
- **Stop changes immediately; do not delete products or run batch rollback.** Restore the pre-release **actual live plugin-folder backup** to `wp-content/plugins/idollmate-discovery/` using the host-specific controlled procedure. Verify installed files and plugin version match **captured live hashes**, not assumed public Git version.
- This release has **no schema/data migration**, so an ordinary plugin-only failure should not require a DB restore. Retain verified full WP and DB backups for recovery if independent evidence finds data corruption; any destructive DB restore needs **separate Owner approval**.
- Re-read plugin activation, Woo pages, Draft/Hidden four SKU, batch journal, noindex/Coming Soon, logs, and the original admin QA after rollback. Document post-rollback exact checksums and Owner notification; do not claim success before readback.
- The local synthetic published fixture journals are **not production rollback scripts** and must never run against the target.

## Current verdict
**Ready as a sealed candidate package / precise manifest, NOT READY FOR PRODUCTION DEPLOYMENT.** Unresolved authenticated browser + Hostinger theme parity gates are explicit; full target authenticated read-only preflight/backup IDs have not been executed yet. **No Owner release PASS requested or assumed.**
