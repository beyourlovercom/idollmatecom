# iDollmate R8-G/H — actual SKU inventory, offline scale gate and real supplier source intake

**Date:** 2026-10-31 (Asia/Taipei). **Status:** **R8-G read-only catalog baseline and offline 2,000-row capacity checks PASS**. **R8-H commercially authorized SKU source not available / NOT READY**. This is a source-first engineering gate, not a site write or Owner product-import approval.

## Corrected progress versus historical Issue #4 text
- **R8-A:** Woo-owned schema/categories/attributes/V1 fields drafted; only `mini-dolls`, `full-body-dolls`, and `pa_material=tpe,silicone` actually exist. The broader `torso-dolls`, `anime-style-dolls`, `custom-dolls`, `hybrid` and other global terms are *proposals*, not globally approved or installed; Owner/editorial taxonomy decision remains.
- **R8-B:** offline CSV validator, SKU/type/price/stock/provenance checks, repeat/dedupe and bounded 2,000-row test exist; latest 31/31 Python Catalog tests PASS on Windows Local. Earlier Issue text is not a reliable statement of today's R8-C/D/E completion.
- **R8-C:** prior Owner-approved **4-row actual WordPress protected Draft QA Canary** existed and was recovered from the original tag/journal issue (see `R8_C_FOUR_QA_DEV_CANARY_POST_REPAIR_ACCEPTANCE_2026-10-29.md`); do not create it again. Its journal applied, original records preserved; **real destructive rollback on these exact four is intentionally NOT executed**.
- **R8-D/E:** live admin QA and protected product display implemented, matching Hostinger theme Local browser checks 98/98, actual target Owner-installed Discovery 0.1.2 and functional target post-deploy checks PASS. Bounded R8-E deployment **closed** `release/R8_E_F4_F6_MANUAL_DEPLOY_ACCEPTANCE_2026-10-31.md`. This does **not** complete R8 as a whole.

## 2026-10-31 freshly read actual protected WordPress catalog
- Direct, gated WP-CLI `r8_woo_dev_snapshot.php` run via the existing authorized host alias, **read-only by stdin**. No PHP file, ZIP, DB update, media or import written to the host.
- Private snapshot saved only on China Mac `~/AI/idollmate/r8-private/r8-live-sku-snapshot-20261031.json`, **3,046 bytes**, SHA256 `595bc54fb680b68818846038347256cb4c9b81ff871ad891c1ae2f2239f7dbcb`. Its canonical content was copied privately to isolated Windows Local `D:\ai\idollmate-isolated\_qa\r8-live-sku-snapshot-20261031.json`. Do **not** commit the full Woo snapshot or product fingerprints to the public repository.
- Current records: **8** total, comprising 2 simple, 2 variable parent and 4 child variation records; the parents/simple are Draft and variation records have Woo internal status publish, **not live published top-level storefront products**. All eight are prior R7/R8 protected QA records.
- Current installed term dictionary: `product_cat = full-body-dolls, mini-dolls, uncategorized`; `pa_material = silicone, tpe`; Woo Brands **0**; no evidence of installed `hybrid` or other proposed filter taxonomies.
- `blog_public=0`, Coming Soon=yes. **No 2,000-row original supplier product file** or accompanying private copyright/license evidence was identified within the scoped iDollmate repository and local project file search. Historical YourDoll URL/reference indices are **competitor research metadata, NOT licensed product catalog source**, and are *never* reused as imported merchandise, images or copy.

## Real snapshot conflicts + isolated performance
- Existing `R8_QA_ONLY_SAMPLE_IMPORT.csv` **must not be applied again**: live snapshot Preview returned **four case-insensitive SKU collisions** (simple, parent, TPE, Silicone), `ready_for_owner_review=false`, `dev_write_performed=false`.
- New, private **2,000-row local-only synthetic** CSV (names `QA Synthetic ... DO NOT SELL`, SKUs `IDM-R8-SYNTH-*`, source_refs `QA-SYNTHETIC-UNLICENSED-NOT-FOR-IMPORT-*`) was generated under `D:\ai\idollmate-isolated\_qa\`, **outside WordPress webroot**. Existing structural batch preview over the **actual 8-row snapshot**: 2,000 rows, 0 policy errors, 0 installed-taxonomy blockers, 0 existing SKU conflicts, 0 updates/publish/deletes, `dev_write_performed=false`; core Preview measured **0.0627 sec**.
- **Important distinction:** the earlier generic schema preview does not itself prove legal ownership. The 2,000 synthetic rows are **NOT actual supplier inventory, NOT licensed images/copy, and absolutely NOT import-authorized**. No Confirm was performed; no ledger or WordPress write. Their unique `QA/SYNTH` flags must trigger the new rights gate.
- Offline results stored privately in `_qa\r8-g-live-snapshot-offline-scale-report-20261031.json`, `_qa\r8_g_live_snapshot_offline_scale.py`, `_qa\r8-local-only-synthetic-2000-do-not-import-20261031.csv`.

## New independent pre-import rights metadata gate
Added `scripts/catalog/r8_source_rights_preflight.py`, with purely local CSV inputs and **no network or WordPress writes**, and two *empty/header-only* CSV templates in `docs/templates`:
1. `R8_REAL_SUPPLIER_SKU_INTAKE_TEMPLATE.csv`: exact canonical 20-column R8 Woo CSV; no example synthetic row that could be mistaken for a real product.
2. `R8_PRIVATE_RIGHTS_EVIDENCE_TEMPLATE.csv`: `source_ref,content_origin,rights_basis,evidence_record_id,evidence_sha256,reviewed_by,reviewed_date,review_status`. Evidence files themselves remain **private**, never checked into Git. `original` requires basis `original_owned`; `licensed` requires `supplier_authorized` or `licensed`. Every catalog source reference must map to exactly one review entry; revoked/pending/mismatched/duplicate or missing entries block; legacy QA and `IDM-R8-SYNTH-*` test rows also block.
- The rights script combines the original R8 CSV/Woo SKU/taxonomy Preview with metadata coverage and produces a **bounded aggregate audit report** without exposing source content, customer records, or private evidence.
- **Limits:** anyone can claim `review_status=approved` in a CSV; a SHA256 string alone doesn't demonstrate actual rights. A human reviewer must separately inspect the real authorization document and verify its digest, terms, supplier/creator authority, and permitted image/title/copy usage. **Even a metadata PASS never grants a WordPress write:** report always returns `legal_rights_independently_proven=false`, `production_import_approved=false`, `owner_write_pass_received=false`.
- This guard augments, rather than replaces, the existing Owner approval/actual WordPress guarded Apply and rollback checks. The actual importer **does not automatically enforce this new independent metadata tool**; the pre-import execution runbook must require its PASS and external Owner signoff before proposing a write.

### Local-only usage after receiving real lawful supplier files
```powershell
python scripts/catalog/r8_source_rights_preflight.py --catalog D:\private\supplier_products.csv --rights-manifest D:\private\supplier_rights_review.csv --snapshot D:\ai\idollmate-isolated\_qa\r8-live-sku-snapshot-20261031.json --report D:\private\r8_rights_preview.json
```
This `20261031` SKU snapshot is for **preliminary read-only review only**, not a future write permit. Immediately before any potential approved import, capture a new live Woo snapshot and require identical sources/policy/plan ID plus explicit Owner approval. No 2,000-row bulk write in one unreviewed operation. R8-C first-actual-batch cap was **25 rows**, with a separate real Supplier Canary proposal required.

## Current critical path / next gate
1. **R8-H SOURCE OPEN:** Owner supplies a **commercially legitimate** original/authorized SKU file. Required: SKU/parent mappings, original titles, actual USD prices, MTO/stock policy and private evidence linking ownership/licensing of text/images. No guessing 2,000 supplier records; quantity and legal rights unverified.
2. **R8-A EDITORIAL OPEN:** approve final category hierarchy, required `pa_material` and other facet term slugs, any new terms on target through a separate approval.
3. **R8-H VALIDATION:** rerun the 20-column canonical CSV parser, current Woo SKU/slug/term preview, rights metadata review and explicit independent source evidence check. The catalog data can be imported only after all blockers are cleared.
4. **R8-I separate Owner gate:** new lawful **Draft-only, ≤25 SKU first Canary** with exact plan/CSV hash, no media, no publish, no stock/price overwrites and separately gated rollback. Do not reuse the applied 4-row QA batch or merge PR #3/#5.
5. **R8-J performance:** 2,000-row real-data dry-run and paginated archive tests, then approved small batches, not fake content at scale. No payment tests or Shopify/DNS changes.

**Current verdict:** R8-G baseline/offline scale PASS; R8-H source/license/editorial review OPEN; **production import 0, no new catalog items created; PR #3 and PR #5 remain Draft/unmerged**.

## Additional rights gate negative regression and source-worktree verification
- **40/40 R8 Python tests PASS** on isolated Windows after the evidence gate was added (31 existing + 9 source rights tests); `git diff --check` clean.
- Synthetic **2,000-row rights rejection** was validated against the actual 8-record Woo snapshot, alongside the original 4-row reimport rejection. A bug in the first rights report dropped later error types from its first-30 display; fixed by **independently counting all errors by code while limiting detail preview to 30 lines**, with an added 2,000-row regression test.
- Actual live-snapshot outcomes: 4 historic QA rows **BLOCKED** (4 Woo SKU collisions and 11 metadata findings); 2,000 synthetic rows **BLOCKED** (0 Woo structural errors but **4,002 rights/source metadata findings** including unavailable rights manifest). Both had `dev_write_performed=false`, and no Confirm command ran.
- Existing Drive was also searched by project/product-source keywords; no identified iDollmate-specific supplier catalog was found among accessible results. Other unrelated BYL product sheets were **not treated as iDollmate source evidence**.
