# R8-C Gate T actual Dev taxonomy creation and Gate C proposal
Date: 2026-10-29. Target: https://dev.idollmate.com WooCommerce **only**.

## Authorization and pre-write protection
Owner explicitly approved Gate T Dev terms ONLY, not real product creation, publication, media, payment or Production.
Verified siteurl/home, noindex=0, Woo Coming Soon=yes and USD.
Existing R7 #13/#15 parent products Draft, #16/#17 internal variations; existing categories only uncategorized; no R8 global attributes.
BACS disabled/unset; shipping zones 0.
Private baseline retained on China Mac at ~/AI/idollmate/r8-private/r8_gateT_before_20261029.json, never in Git.
Initial canonical snapshot SHA-256: 0985949158ac561a212fe61ecfba6ad4bd6a120ed84c754832887c5972176be7

## Only approved real Dev taxonomy writes
| Object | Label | Slug/taxonomy | Created ID |
| --- | --- | --- | ---: |
| Category | Mini Dolls | mini-dolls | 16 |
| Category | Full Body Dolls | full-body-dolls | 17 |
| Woo global attribute | Material | slug material / pa_material | 1 |
| Attribute term | TPE | pa_material / tpe | 18 |
| Attribute term | Silicone | pa_material / silicone | 19 |

Initial term-stage pre-write validation stopped because wc_get_attribute_taxonomies was keyed as "id:1", not index 0. NO attribute term was created by that failed validation. Read-only recheck confirmed the Woo attribute, then corrected the lookup via array_values and created only the two approved attribute terms.

## Post-write independently verified readback
Fresh protected snapshot saved privately at ~/AI/idollmate/r8-private/r8_gateT_after_20261029.json (chmod 600).
Existing four R7 product records AND per-product protected integrity hashes exactly unchanged.
Categories exactly: uncategorized, mini-dolls, full-body-dolls.
Attribute dictionary exactly: pa_material with silicone and tpe. Brands empty.
Dev noindex=0, Coming Soon=yes and site identity unchanged.
Final canonical snapshot SHA-256: 58eed76c86f14c307b3ee8e12df51ee3ef6e8b94894a72cc6d2dbd95f5c925fd
No Woo products, images, orders or public content created; Production and Shopify untouched. Gate T PASS.

## Fresh independent Gate C Preview (NO write)
Source file: docs/uat/R8_QA_ONLY_SAMPLE_IMPORT.csv
Source CSV SHA-256: e03ee78f41976d8c68692536bf0e3e13f1b7b70cdaccd0ee6840da3155bd85e2
Policy SHA-256: a3a3e4ef59c42ad6fd8174e884ea37ff61bbf9fcdcc80b855259b3792a5961c1
Protected snapshot SHA-256: 58eed76c86f14c307b3ee8e12df51ee3ef6e8b94894a72cc6d2dbd95f5c925fd
**Fresh proposed batch/plan ID: cfc3ead963a72e0aa44740d5**
Preview: 4 rows (1 simple, 1 variable parent, 2 variations), 0 duplicate SKUs, 0 CSV errors, 0 taxonomy blockers; ready_for_owner_review=true; dev_write_performed=false.

Exact four QA-only original text test fixtures:
| SKU | Row | Price (USD) | Term |
| --- | --- | ---: | --- |
| IDM-R8-EXAMPLE-SIMPLE | simple | 599.00 | mini-dolls / tpe |
| IDM-R8-EXAMPLE-PARENT | variable parent | - | full-body-dolls |
| IDM-R8-EXAMPLE-TPE | child | 999.00 | pa_material:tpe |
| IDM-R8-EXAMPLE-SIL | child | 1099.00 | pa_material:silicone |

All images blank, parent/simple Draft and hidden, factory made-to-order and no fabricated inventory. These are artificial QA fixtures, not verified licensed commercial supplier catalog products.
A changed Woo baseline, CSV or policy invalidates this Preview and requires a new explicit approval.

## Gate C still pending independent Owner approval
Proposed exact Owner authorization:
pass R8-C Dev Canary cfc3ead963a72e0aa44740d5 4 (QA-only fixtures; Draft/hidden; no images; Dev only)

Before creating a real Dev product: new live snapshot, verify all SHA bindings and zero blockers, Woo consistency and exact site. Source-only WP-CLI adapter requires R8_OWNER_DEV_WRITE_PASS=R8-OWNER-PASS-cfc3ead963a72e0aa44740d5 and R8 CONFIRM cfc3ead963a72e0aa44740d5 4.
Then only read back created IDs, variation pricing 999 and 1099, Draft/hidden parents, journal integrity, R7 preserved, no media, no public products and repeated Apply no duplicates.
Actual destructive batch rollback needs separate exact-batch Owner approval; phrase R8 ROLLBACK cfc3ead963a72e0aa44740d5. It must check batch ownership, journal IDs, fingerprints, order links, delete child-before-parent and show post-rollback baseline preservation.
PHP 137 / 503 / journal drift: STOP and manually reconcile; no blind rerun.
Real Woo CRUD, real rollback, and interruption recovery NOT YET PASS.

Issue #4 open; Draft PR #5 stacked on R7 Draft PR #3; neither merge nor Production, DNS, payment changes authorized.
