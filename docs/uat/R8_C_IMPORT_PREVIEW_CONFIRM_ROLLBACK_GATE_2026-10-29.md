# R8-C — Import Preview / explicit Confirm / batch-scoped Rollback

**Status on 2026-10-29:** SOURCE AND OFFLINE SIMULATION COMPLETED. Real WooCommerce write adapter is **SOURCE ONLY, NOT DEPLOYED, NOT RUNTIME-ACCEPTED**. **No real Dev product/taxonomy writes have occurred.** Requires independent Owner `pass` before ANY Dev write.

## Implemented

| Gate | Source | What is actually verified |
|---|---|---|
| CSV sanity and 2,000-row scale | `r8_catalog_preflight.py` | 16 unit tests PASS, zero database/network access |
| Read-only live Woo inventory and taxonomy snapshot | `r8_woo_dev_snapshot.php` | Actually ran **read-only** on `dev.idollmate.com`, output saved **privately** on China Mac; no product/customer/price data copied to GitHub |
| SKU / slug / taxonomy collision preview | `r8_catalog_batch.py preview` | Real private Dev snapshot compared with four-row synthetic CSV |
| Explicit Confirm | `r8_catalog_batch.py confirm` | **Local JSON simulator only**, typed phrase and exact source/snapshot/policy plan ID, immutable batch entry, repeat confirm idempotent |
| Batch rollback | `r8_catalog_batch.py rollback` | **Local simulator only**, exact batch ID and rollback phrase, reject modified item, repeat rollback idempotent |
| Future Woo Draft CRUD apply / rollback | `r8_woo_dev_guarded_apply.php` | **PHP syntax check only. NEVER executed or deployed as import script.** Independent Owner token, protected Dev site guard, live re-snapshot comparison, 25-row first canary limit, no updates/publish, Woo-created batch tags, audit option, refuse edited/order-linked items before rollback |
| CI | `.github/workflows/r8-catalog-preflight.yml` | Python lint, PHP adapter lint, 30 isolated Python tests, including 2,000 SKU preflight |

### Actual read-only Dev snapshot and conflict preview

Baseline: `https://dev.idollmate.com`, `blog_public=0`, `woocommerce_coming_soon=yes`. **Four existing R7 test SKUs** (parent IDs #13 and #15 are Draft; #16 and #17 are internal Woo variation records under Draft parent). The only current `product_cat` term is `uncategorized`; **no** `pa_material` global attribute or other R8 taxonomies have been installed.

Four-row QA sample: 1 simple, 1 variable parent, 2 variations. **SKU collision count 0**, but **five precise blockers**:
1. `CATEGORY_NOT_INSTALLED`: `mini-dolls` on the simple product
2. `ATTRIBUTE_TERM_MISSING`: `pa_material/tpe` on the simple product
3. `CATEGORY_NOT_INSTALLED`: `full-body-dolls` on the variable parent
4. `ATTRIBUTE_TERM_MISSING`: `pa_material/tpe` on the first variation
5. `ATTRIBUTE_TERM_MISSING`: `pa_material/silicone` on the second variation

**Result: `ready_for_owner_review=false`; Dev write=0.** Tool did not create missing terms, and never attempted to insert products.

Private read-only snapshot and report remain under China Mac `~/AI/idollmate/r8-private/`. Only aggregate blocker evidence belongs in GitHub. No customer, payment, supplier credential, reference image binary or copyrighted media is exported.

### Completed OFFLINE end-to-end demonstration (synthetic taxonomy)

An **artificial local copy** of the Dev snapshot was given two fictional future categories and `pa_material` terms. This is NOT a change to Dev:
- Preview: four rows, no blockers, eligible for **simulation**.
- Incorrect Confirm phrase: **DENIED**; nothing created.
- Exact Confirm phrase: **4 simulated draft entries** in a private local JSON ledger.
- Confirm repeated: `already_applied`, **0 additional entries**.
- Exact batch-scoped Rollback: **4 simulated entries removed**.
- Rollback repeated: `already_rolled_back`, **0 additional removals**.
- End-state simulation ledger: **0 products**, batch status `rolled_back`.
- All results report `simulation_only=true`, `dev_write_performed=false`.

This is evidence of the planning/confirmation/rollback **state machine**, NOT proof that real WooCommerce CRUD batch rollback has run.

## Source-only Woo executor safeguard (not activated)

The PHP adapter has an explicit fail-closed sequence:
1. Can only start in WP-CLI on exact `dev.idollmate.com` with **noindex and Coming Soon on**. No WP admin menu/UI and no public URL.
2. Approved plan must be Draft-only, zero updates/publish/delete; separate `R8_OWNER_DEV_WRITE_PASS` env token and exact `R8_CONFIRM_PHRASE` required. **These flags are not granted by the current user instruction.**
3. Verify policy hash, source CSV SHA-256, canonical snapshot hash, recomputed plan ID and live read-only Woo SKU/taxonomy snapshot **before the first write**.
4. For first Dev Canary, accept at most **25 rows**; no automatic 2,000 SKU insertion.
5. Recheck unique SKUs including existing variations; require existing taxonomy/attribute terms and supported material/size codes. For first Canary, reject all nonempty external image reference URLs (even metadata) to minimize scope.
6. Create **new Draft parent/simple products only**. Native Woo variation records use Woo's internal variation status under an unpublished Draft parent. Existing R7 test products and real Woo stock are not modified; no new terms, no product media or emails.
7. Tag records with the R8 batch ID and source marker; maintain per-batch journal and product fingerprint.
8. Rollback is separate Owner-approved CLI invocation and prechecks *all surviving* batch-tagged products before deletion: no publication, edits, cross-batch IDs or order references. Delete only the batch's own products, variations before parents. If an interrupted batch/fingerprint discrepancy exists, **stop for manual review**, not blind rollback.

**Known implementation risk**: PHP guarded apply/rollback has only syntax/static guard checks. It has **not** undergone real Woo CRUD canary testing, browser UAT, Hostinger failure-interruption recovery, or PHP functional mock suite. Do NOT claim live importer/rollback PASS.

## Independent Owner gate BEFORE first real Dev write

Only after all conditions below are met:
- [ ] Owner approves actual initial `product_cat` categories and Woo global `pa_material` terms; separately approve term creation on Dev. Existing Dev has none.
- [ ] Confirm source and image usage rights for **original/sample products**. No copying YourDoll artwork, text or source material; original URL-only references remain private.
- [ ] Review exact CSV and Preview report with zero SKU/slug/attribute blockers, source CSV SHA-256, site snapshot and batch ID.
- [ ] Code review guarded adapter against current Woo 11.2.x/ThemeComplete and source file protections. Verify rollback proof in a fully isolated WordPress/Woo sandbox, NOT Production.
- [ ] Owner provides new explicit `pass` for **one bounded Dev canary (1–25 original-source rows)** with batch ID and approved taxonomy plan. Not an approval to import 2,000 SKUs or enable public products.
- [ ] Before actual write: ensure `blog_public=0`, Woo Coming Soon yes, and existing R7 IDs / SKUs unchanged, and obtain a new live snapshot to catch races.
- [ ] After test: read back actual Woo product IDs, metadata/variation prices; verify idempotent repeat, batch rollback and re-read R7 unchanged; stop on PHP 137/503 and restore from tagged batch or escalate. Do not delete anyone else's records.
- [ ] PR #5 and parent PR #3 remain Draft/unmerged. A separate Owner `pass` is required for any merge or Production change.

## Scope / next sequence

**R8-C source scaffold and offline acceptance are complete; actual Dev integration remains gated.** The current implementation is CLI-first; a WordPress backend **Preview → Confirm UI** is not yet installed or claimed delivered. If the Owner prefers browser-admin workflow, add a separate authenticated UI to present preview and approval code after the source acceptance gate, and never expose a public unauthenticated upload endpoint.

**R8-D** facets, taxonomy browsing and SEO can proceed source-first in parallel while real Dev batch writes await approval. Payment merchant testing remains out of R8.

[Source ownership](../architecture/R8_CATALOG_SCHEMA_V1.md) | [R7 closure](R7_ENGINEERING_SCOPE_CLOSURE_AND_R8_HANDOFF_2026-10-29.md) | [R8 A/B preflight](R8_A_B_OFFLINE_PREFLIGHT_2026-10-29.md).

## Independent R8-C acceptance review — 2026-10-29

**Outcome:** SOURCE/READ-ONLY UAT **PASS with blockers**; first real Woo Draft Canary **NOT AUTHORIZED / NOT TESTED**.

### Discovered and fixed in this review

- **Actual rollback correctness defect:** the guarded Woo adapter previously required `live_snapshot === preimport_snapshot` even during rollback, which would always reject a legitimately completed import because the batch's own new products appeared in the live snapshot. Fixed to compare the original snapshot while permitting **ONLY batch-tagged newly added IDs** and rejecting any changed baseline product/category/attribute.
- **Deletion-scope defect:** rollback previously did not require an exact match between all database batch-tagged IDs and the per-batch journal IDs. Fixed; missing/extra/duplicate IDs now block all deletions.
- Extracted pure PHP helpers (`r8_woo_batch_guards.php`) and added **16/16 PHP guard assertions** covering normal owned additions, in-progress journal recovery, foreign additions, changed/deleted baseline products, taxonomy changes, wrong batch, duplicate records, missing/extra journal IDs and repeat/rolled-back states.
- Added a Woo **USD** currency gate to align CSV USD prices with runtime currency; require successful batch-journal creation and persistence before declaring apply successful, refusing silent continuation after an unexpected journal write failure.
- **No guarded PHP apply or rollback was deployed or executed** against the real Dev database. PHP lint is not sufficient evidence to claim actual Woo CRUD rollback PASS.

### Latest actual read-only protected Dev preview

Read-only Woo snapshot was independently refreshed after the source changes:
- Domain `https://dev.idollmate.com`; `blog_public=0`; `woocommerce_coming_soon=yes`.
- Existing Woo records: **4** (2 Draft QA parents and 2 Woo internal child variations), unchanged as expected.
- Installed categories: **`uncategorized` only**; global `pa_material` terms: **none**.
- Four-line synthetic CSV preview: **0 SKU conflicts**, **5 pre-existing taxonomy blockers**, `ready_for_owner_review=false`, `dev_write_performed=false`.
- `CATEGORY_NOT_INSTALLED` for `mini-dolls` and `full-body-dolls`.
- `ATTRIBUTE_TERM_MISSING` for `pa_material:tpe` (simple + TPE variation) and `pa_material:silicone` (Silicone variation).
- No taxonomy term, real product, image or customer record was created, changed or deleted.

### Acceptance test status

| Test | Evidence | State |
|---|---|---|
| 2,000 SKU/offline preflight, invalid rows and Preview/Confirm/Rollback simulation | **30/30 Python tests** | PASS |
| Real PHP rollback baseline & journal integrity helper, isolated no-DB tests | **16/16 PHP tests** | PASS |
| PHP syntax check, real source with Owner gate, snapshot/currency/journal guards | `php -l`, static review | PASS for syntax; runtime not proven |
| Python/PHP canonical Dev snapshot SHA parity | Same hash from one original JSON document | PASS |
| Read-only real Dev SKU / taxonomy preview | 4 existing SKUs / 5 taxonomy blockers | PASS for detection; import BLOCKED |
| Woo real Dev Draft create, repeat confirm, real rollback | **No write authorized or run** | NOT TESTED |
| R7 unchanged, Dev safe | Read-back `noindex=0`, Coming Soon=yes, Draft, BACS=no | PASS |

### Exact next separate approval gate

**Gate T — taxonomy install (separate Dev write):** Obtain Owner `pass` for **only** `product_cat` terms `mini-dolls` and `full-body-dolls` plus Woo global attribute `pa_material` with terms `tpe` and `silicone` (with customer-visible labels approved first). Do not publish categories/products or touch Production.

**Gate C — first real Dev import Canary (later approval):** After taxonomy readback & re-preview show **zero blockers**, obtain a **second explicit Owner `pass`** identifying plan ID, CSV SHA, exact rows (max 25), Draft-only, rollback strategy and source licensing. First evaluate source adapter compatibility and recovery under a fresh non-Production Woo staging fixture. Only then run Dev, verify Woo IDs/attributes/price and perform controlled batch rollback.

For both gates, current user instruction **"进入验收"** authorizes read-only validation and offline simulations only, **not Dev database writes**. R8 PR #5 remains Draft against the R7 Draft branch; no Production Shopify access or payment work.
