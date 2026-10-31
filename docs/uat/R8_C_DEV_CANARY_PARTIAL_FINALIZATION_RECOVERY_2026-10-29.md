# R8-C Dev Canary incident: true Woo products created; batch metadata repair pending

Date 2026-10-29. DEV ONLY: dev.idollmate.com. No Shopify, production, payments or destructive rollback.

Owner manually ran the approved four-row QA import (Plan cfc3ead963a72e0aa44740d5), which threw
R8-C final product readback/tag mismatch at original importer line 418.

READ-ONLY real Woo DB evidence:
- #32 IDM-R8-EXAMPLE-SIMPLE, simple, Draft/Hidden, USD599.00
- #33 IDM-R8-EXAMPLE-PARENT, variable parent, Draft/Hidden
- #34 IDM-R8-EXAMPLE-TPE, variation under #33, USD999.00, internal variation status publish
- #35 IDM-R8-EXAMPLE-SIL, variation under #33, USD1099.00, internal variation status publish
- Exactly 4 new QA products; 0 publicly published top-level products, 0 images, 0 order references.
- Frozen R7 products #13/#15/#16/#17: all protected integrity digests unchanged, exact original taxonomy and attributes unchanged. Coming Soon and noindex remain enabled.
- Batch journal: in_progress, IDs [32,33,34,35], created_count=4, no pending SKU, no active lock.
- All four new products have wrong _idm_r8_batch_id meta value "17", rather than the approved batch ID. No products have the correct batch meta yet.

Verified source root cause: original importer held its approved batch ID in $id and required the read-only snapshot at the same PHP scope. The snapshot iterates foreach($ids as $id), overwriting the importer's batch ID with last original R7 record ID 17. Thus the journal was written under the correct option key, but all new products were tagged "17", and final guard correctly failed.

Source remediation: read-only snapshot now executes through isolated function scope, with explicit batch ID nonmutation assertion. Fingerprint helper is shared by importer and recovery script, and PHP regression test proves including a snapshot that sets $id=17 cannot change the importer batch ID. Only source files and tests changed; no Dev repair has yet run.

Exact recovery-only script: scripts/catalog/r8_woo_dev_recover_batch.php.
- Read-only preview checks approved CSV, policy, frozen baseline, no new public products or order references, exact IDs/types/SKUs/prices/parents/taxonomy/material attributes, journal, and wrong legacy meta tag.
- Repair mode is separate and requires exact Owner pass and a typed recovery phrase.
- Permitted mutations only: correct _idm_r8_batch_id on the existing four IDs, refresh their fingerprint meta, then mark exact journal applied.
- Never creates, deletes, publishes or modifies SKU, prices, images or product types.
- Any drift: abort. No blind retry of original Apply; destructive rollback separately gated.

Read-only preview command from the existing Hostinger SSH:
B="$HOME/ai-idollmate-dev/r8-canary/cfc3ead963a72e0aa44740d5"
D="$HOME/domains/dev.idollmate.com/public_html"
R8_RECOVER_ACTION=preview wp --path="$D" eval-file "$B/r8_woo_dev_recover_batch.php"

Do not run repair unless the exact preview returns R8_RECOVERY_PREFLIGHT_PASS and user operates the approved exact-batch recovery command. After that, independently read back all four and check R7 immutable, no published products, journal applied, no lock, and idempotency.

R8-C remains OPEN / recovery pending. PR #3 and PR #5 Draft/unmerged.
