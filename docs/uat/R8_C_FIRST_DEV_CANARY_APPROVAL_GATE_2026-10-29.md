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

## Continued source-only acceptance — no Gate T/C permissions implied

### New runtime-relevant source protections
- Repeated `apply` on a verified **applied** batch now takes a read-only already-completed path after checking exact journal ID ownership, current live additions, no R7 baseline drift, unchanged imported fingerprints, Draft parent status and no order references. Pending/in-progress/corrupt batches are never silently retried.
- Guarded WP-CLI takes a per-batch lock **before** refreshing the live snapshot. Its error/kill handling still requires manual inspection when a lock or pending journal remains.
- Durable `pending_sku` marker is written and read back before each Woo product `save()`, followed by the saved product ID and marker clearance. A crash in the save/journal interval remains fail-closed for manual reconciliation; no automatic deletion of unjournaled rows.
- Variable-parent fingerprints are refreshed **after** creating child variations, because Woo may synchronize parent state as children save.
- Both Python planner and PHP guarded adapter reject old six-field snapshots without a protected-field digest before any real import.
- Isolated test suite: Python **31/31 PASS**, PHP pure guards **25/25 PASS**. No true Woo CRUD / rollback transaction has been exercised.

### Real Dev read-only protected snapshot and Preview (2026-10-29)
- Strengthened snapshot loaded by read-only WP-CLI evaluation from China Mac (no file deployed on Hostinger): **4** unchanged R7 Woo records, `uncategorized` only, no installed R8 global attributes, protected Coming Soon/noindex remain on.
- Protected snapshot persisted privately on China Mac at `~/AI/idollmate/r8-private/dev_snapshot_integrity_20261029.json`, never committed to public source.
- Python/PHP canonical snapshot SHA-256 parity: `0985949158ac561a212fe61ecfba6ad4bd6a120ed84c754832887c5972176be7`.
- QA CSV SHA-256: `e03ee78f41976d8c68692536bf0e3e13f1b7b70cdaccd0ee6840da3155bd85e2`. Four rows, **0** SKU collisions and CSV errors, **5** missing taxonomy/attribute blockers.
- Diagnostic blocked plan `6b729c6a8e2e47c1def8a140` has `ready_for_owner_review=false` and **must NEVER be approved or executed**. Gate T term creation will change the snapshot and force a fresh new plan ID.

### Exact next Owner approval boundary
- **Gate T only, not currently authorized:** `pass R8-C Gate T: Dev taxonomy mini-dolls full-body-dolls pa_material=tpe,silicone only`.
- **Gate C (different future approval):** after Gate T has succeeded, zero-blocker fresh Preview, no media, original QA fixture rights review, and exact batch plan+SHA: `pass R8-C Dev Canary <NEW_PLAN_ID> 4`.
- A second exact Rollback command and its independent Owner approval are required before any real deletion. Live PHP 137/503 interruption recovery is **NOT YET PASS** and must be contained/observed at Canary.

No Dev product, taxonomy, attribute, image, order, payment, DNS or Production changes were performed in this source-only continuation.

## Post-approval safe stop and exact-count hardening (2026-10-29)

Owner authorized exactly four QA Draft-only products on Dev under Plan
cfc3ead963a72e0aa44740d5. Adapter, CSV, plan and snapshot were staged to
a private Hostinger directory outside the web root. Authorized WP-CLI Apply
was blocked before dispatch by the execution channel's safety check (twice);
no actual import was executed and no alternate route was attempted.
Independent read-only Dev Woo checks returned no new SKU IDs, batch
journal, lock or tagged IDs, zero public products and unchanged R7 baseline.
Protected canonical snapshot SHA-256 remained
58eed76c86f14c307b3ee8e12df51ee3ef6e8b94894a72cc6d2dbd95f5c925fd.

A subsequent source-only review identified a gap: applied/replay batch
state could be accepted with fewer than the approved row count if both
created_count and tagged IDs agreed with each other but not with the Plan.
The adapter's commit transition and replay guard now require equality with
plan.total_rows, and reject invalid row-count bounds.

PHP isolated guard tests increased from 25 to 28. The approved CSV, policy
and protected snapshot are unchanged, so their checksum-derived Plan ID
remains the same only while a fresh live snapshot matches. Any future
allowed real execution must first verify that private Hostinger staging
contains the latest reviewed guarded adapter source.

Real Woo CRUD, variant price readback, batch idempotency and rollback remain
UNTESTED. Gate C BLOCKED / R8-C OPEN; no auto retry, no bypass of tool safety,
no Production, no merchant/payment work, no destructive rollback without
separate Owner pass.
