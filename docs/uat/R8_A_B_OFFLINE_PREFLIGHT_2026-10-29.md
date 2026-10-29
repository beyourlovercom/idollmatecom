# R8-B offline catalog preflight: tested source-first

**State**: offline validator implemented and tested; **NO WooCommerce import**. First run: 2026-10-29.

## Artifacts

- `scripts/catalog/r8_catalog_policy.json`: versioned provisional allowlists and exact CSV columns, including `draft`-only status.
- `scripts/catalog/r8_catalog_preflight.py`: Python 3.11 stdlib-only, no imports, no network calls. Detects duplicate SKU (case insensitive), optional existing Woo SKU manifest conflicts, duplicate canonical slug, orphan/duplicate variations, price/inventory invalid, invalid category/material/lead-time, unverified content or unsafe spreadsheet values, and unsafe third-party media links. Original YourDoll image URLs are text-only references; **no image download or display**.
- `docs/uat/R8_QA_ONLY_SAMPLE_IMPORT.csv`: artificial QA-only original text, 4 rows. **Do not upload to Dev as real products**.
- `tests/test_r8_catalog_preflight.py`: zero WordPress connections; test normal/invalid paths and a 2,000-row batch.
- `.github/workflows/r8-catalog-preflight.yml`: CI lint/unit only, never touches Dev or Production.

## Verified execution on China Mac

```bash
python3 -m unittest discover -s tests -p 'test_r8_catalog_preflight.py' -v
# 16 tests ... OK (0.051s)

python3 scripts/catalog/r8_catalog_preflight.py \
  docs/uat/R8_QA_ONLY_SAMPLE_IMPORT.csv
# total_rows: 4, simple:1, variable:1, variation:2,
# error_count:0, warning_count:0,
# can_import:true, import_executed:false, requires_owner_approval:true
```

The 2,000-row performance test is part of the 16 tests and completed within the observed 0.051s total unittest run on one local machine; this is an **offline structural validation performance observation**, not a claim about bulk Woo DB import runtime or server performance.

`can_import=true` means **structurally eligible for later human review**, not permission or execution. No Dev WP table, Woo order, tax/shipping, payment gateway, or Production Shopify state was modified. Do not import these synthetic QA rows.

## R8-C next controlled scope (not yet implemented)

1. Read-only existing SKU and taxonomy snapshot from Woo Dev (do not export customer data).
2. Preview upload: exact source hash, line diagnostics, SKU collision map, parent/variation mapping and natural Woo attributes; display proposed insert/update counts **before any write**.
3. Explicit admin confirmation of a single preview hash and bounded batch; default **Draft only**, no existing SKU overwrites, rate-limited Woo CRUD.
4. Append-only batch journal with created product IDs, original preimage for any explicitly permitted update (no updates in V1), rollback **only by immutable R8 batch-tagged IDs** and idempotent retry.
5. Separate Owner approval before first Dev DB write; Production/Shopify outside this entire workstream.

## Known constraints

- Taxonomy category labels are proposed; require editorial approval before actual catalog import/SEO publication.
- Provenance fields are user/source declarations. Validator cannot establish intellectual property rights from an ID alone.
- `reference_image_url` remains **private metadata only**. Third-party image URLs/403 responses confer no license; R8 tooling never fetches or renders them.
- Physical stock and prices need supplier-original evidence, not invented numbers.
- Parent SKU grouping is required within the same batch for deterministic import; external Woo parent lookup is not implemented.
- Entire R8 branch depends on unmerged R7 Draft PR #3; target the R7 branch as the stacked PR base. No merge until explicit Owner **pass**.

R7 code remains frozen; H1 payment merchant tests are not R8 dependencies.
