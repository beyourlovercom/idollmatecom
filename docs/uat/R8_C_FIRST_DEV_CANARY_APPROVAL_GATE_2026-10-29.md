# R8-C source safety review and first Dev Canary approval gate — 2026-10-29

## Verified scope (read-only; no new Dev product/category writes)
- Source branch: `feat/r8-catalog-os-v1`, stacked Draft PR #5 on unmerged R7 Draft PR #3.
- GitHub Issue #4 open; R7 Issue #2 closed. Neither PR merged or released.
- China Mac source tests: 30/30 Python; PHP isolated rollback guards 19/19; adapter, snapshot, guard and test PHP lint PASS.
- Actual Hostinger read-only WP-CLI snapshot: site `https://dev.idollmate.com`, `blog_public=0`, Coming Soon yes; R7 products 13 and 15 Draft, child variations 16/17 internal; only `uncategorized`; no Woo global R8 attribute terms.
- Independent runtime settings readback: Woo USD, BACS disabled/unset, zero shipping zones.
- Snapshot was **read only**. No products, categories, terms, images, orders or Production records were written.
- CI needs re-check against the new source-safety commit before it may be marked current.

## Safety audit repairs — source only, not deployed
- The earlier six-field product snapshot missed protected edits of price, stock, descriptions, media, category/brand term assignments and Woo attributes. The snapshot now contains a protected-field SHA-256 digest for every product and blocks any unreadable Woo product record.
- Rollback refuses legacy snapshots with missing/invalid integrity digests and compares unchanged R7 protected-state digests before considering any batch deletion.
- Imported batch fingerprints include sale/active price, post modification time, content, stock status, tax, media and brand association in addition to the original fields.
- Fingerprint persistence and existing-term brand assignment require verified readback, rather than silently succeeding when a DB write fails.
- Additional negative tests prove weak legacy snapshots, protected pricing/stock, and content/media digests are rejected; pure rollback guards now 19/19 PASS.
- These are **source safeguards**, not a live Woo database transaction or evidence of crash-recovery success.

## Proposed FIRST Canary dataset — four QA-only original examples, not commercial catalog
File: `docs/uat/R8_QA_ONLY_SAMPLE_IMPORT.csv`
Source CSV SHA-256: `e03ee78f41976d8c68692536bf0e3e13f1b7b70cdaccd0ee6840da3155bd85e2`

| SKU | Type | Price USD | Taxonomy | Intended status |
| --- | --- | ---: | --- | --- |
| IDM-R8-EXAMPLE-SIMPLE | simple | 599.00 | mini-dolls / tpe | Draft, hidden |
| IDM-R8-EXAMPLE-PARENT | variable parent | none | full-body-dolls | Draft, hidden |
| IDM-R8-EXAMPLE-TPE | child variation | 999.00 | pa_material:tpe | Internal variation under Draft parent |
| IDM-R8-EXAMPLE-SIL | child variation | 1,099.00 | pa_material:silicone | Internal variation under Draft parent |

All four rows are synthetic QA-only original text, with empty image URLs, no brand and factory made-to-order (no invented stock). They are **not** licensed/supplier-verified commercial products. Owner must explicitly accept QA fixtures or replace CSV with authorized original SKUs before a real import. Replacing CSV forces a new source SHA and plan ID.

## Gate T — separate taxonomy approval; NOT granted
Proposed only: `product_cat:mini-dolls`, `product_cat:full-body-dolls`, Woo global `pa_material` with `tpe` and `silicone`. Labels/slug hierarchy are provisional and require Owner/editorial approval. Gate T authorizes only named Dev taxonomy creations; no product writes, no media, no published content. No taxonomy will be created based on this report alone.

## Gate C — later, independently approved exact batch; NOT granted
1. After Gate T approval and **actual** readback of installed terms, generate a fresh live snapshot using the reviewed strengthened snapshot emitter; verify no R7 integrity drift and repeat SKU/slug/term preflight.
2. The existing real-Dev four-row Preview correctly has five taxonomy blockers. It is **not** approval-ready; its previous plan hash is stale against the strengthened source. Do not invent or pre-authorize a plan ID.
3. Review final original/QA source rights, exact CSV SHA, live snapshot SHA, policy SHA, plan ID, exact row count, zero blocker Preview, and rollback recovery plan.
4. Independent approval format: `pass R8-C Dev Canary <fresh_plan_id> 4` (and separately confirmed original QA fixture and taxonomy scope).
5. WP-CLI requires exact authorized `R8_OWNER_DEV_WRITE_PASS` token plus `R8 CONFIRM <fresh_plan_id> 4`; no automated execution is allowed before Gate C.
6. First-run readback must verify four new IDs, parent Draft/hidden, the two variation prices, unchanged R7 product integrity, zero image ingestion, complete batch journal, and repeat-apply rejection.
7. A separately confirmed batch-scoped `R8 ROLLBACK <fresh_plan_id>` is required to test actual deletion. Confirm journal IDs equal DB-tagged IDs, no product edits/orders, delete children before parent, and compare post-rollback snapshot to the exact fresh pre-import snapshot.
8. On PHP 137, HTTP 503, journal/DB discrepancy or any failed integrity check: STOP, preserve evidence and reconcile manually; never blind-repeat a destructive action.
9. No main merge, no Production Shopify changes, no DNS, no payment changes.

**Current outcome:** R8-C source safety enhanced; real Dev Canary, actual Woo CRUD, interrupted-run recovery, and admin-browser Preview/Confirm UAT remain NOT PASS.
