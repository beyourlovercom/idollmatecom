# R8-C Dev four-row QA Canary — post-repair real Woo acceptance

Date: 2026-10-29.
Environment: protected WooCommerce DEV only (https://dev.idollmate.com).
Original Owner-approved Plan: cfc3ead963a72e0aa44740d5
Original four-row CSV SHA256: e03ee78f41976d8c68692536bf0e3e13f1b7b70cdaccd0ee6840da3155bd85e2
Frozen R7 pre-import snapshot SHA256: 58eed76c86f14c307b3ee8e12df51ee3ef6e8b94894a72cc6d2dbd95f5c925fd

## Proven incident recovery

Owner's manual exact-batch recovery returned:
R8_RECOVERY_COMPLETE batch=cfc3ead963a72e0aa44740d5 repaired=4 created=0 deleted=0 published=0

This was a metadata/journal repair of already-created records, **not** a second Apply or a rollback. All four Woo products were previously created by the original authorized Apply, which had failed during final readback because of the snapshot include's variable-scope collision ($id overwritten with 17). The source fix is in commit 4beb30e8e49a5d23cbd6488f5594875da5c59683.

## Independent real Woo read-only verification

| Woo ID | Approved QA SKU | Type | Parent | Regular price | Status/visibility |
| --- | --- | --- | --- | --- | --- |
| 32 | IDM-R8-EXAMPLE-SIMPLE | simple | 0 | USD 599.00 | draft / hidden |
| 33 | IDM-R8-EXAMPLE-PARENT | variable | 0 | parent has no direct price | draft / hidden |
| 34 | IDM-R8-EXAMPLE-TPE | variation | 33 | USD 999.00 | Woo internal variation publish; parent draft/hidden |
| 35 | IDM-R8-EXAMPLE-SIL | variation | 33 | USD 1099.00 | Woo internal variation publish; parent draft/hidden |

Readback proved:
- Each _idm_r8_batch_id metadata is exactly cfc3ead963a72e0aa44740d5; **no** remaining rows tagged the bad value 17.
- Batch option state **applied**, created_count **4**, exact IDs **[32,33,34,35]**, CSV digest unchanged, no active batch lock, no pending SKU.
- Exact four original R7 records **#13,#15,#16,#17** preserved including protected integrity digest; frozen taxonomy and attribute baseline unchanged.
- All four imported product fingerprints were recomputed by the canonical source helper and **4/4 matched** persisted fingerprints.
- The shared exact-batch read-only replay guard passed for four expected IDs and all new additions; this establishes source guard idempotency and no duplicate IDs, but **a second actual Apply was intentionally NOT executed**.
- Dev protection remained USD currency, blog_public=0 (noindex), Woo Coming Soon=yes; publicly published parent products **0**.
- Each QA product had **0 images**, no gallery and **0 linked orders** at the separate post-import audit.
- Category assignments: simple -> mini-dolls; variable parent -> full-body-dolls; TPE / Silicone variation material attributes correct. No legacy data deletion or product overwrite.
- Read-only guard output:
  R8_READ_ONLY_GATE_C_GUARDS_PASS
  added_ids=[32,33,34,35], tagged_ids=[32,33,34,35],
  r7_baseline_count=4, total_live_count=8, journal_state=applied,
  created_count=4, fingerprints=4, idempotency_guard=PASS_READ_ONLY,
  public_products=0, data_write=false.

## Sign-off, scope and remaining gates

**R8-C first controlled Dev Canary creation and recovery: PASS.**
**R8-C exact-batch read-only rollback safeguards: source-tested, not destructively executed.**
A full end-to-end destructive rollback/UAT remains untested and needs separate, explicit Owner approval. Do not issue a rollback merely for sign-off; retaining four Draft-only QA entries enables subsequent R8-D/R8-E Dev UI tests.

No new product writes after Owner's recovery, no re-Apply, no payments, Shopify Production, DNS changes, image ingestion or PR merges.

GitHub PR #5 and parent PR #3 remain Draft/unmerged; Issue #4 remains open for remaining R8 work.

Related incident and recovery source:
docs/uat/R8_C_DEV_CANARY_PARTIAL_FINALIZATION_RECOVERY_2026-10-29.md
