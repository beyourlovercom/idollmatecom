# YourDoll Reference Archive — R2–R6 Acceptance / 2026-10-28

**Outcome: bounded historical reference archive PASS; current-site full backup NOT PASS.**

This is a standalone evidence-based research acceptance for iDollmate. YourDoll's live homepage, robots.txt, and sitemap paths returned HTTP 403 on both China and US Mac; the live sitemap, complete current catalog, media files, database, cart and checkout remain unverified. No challenge bypass, live scraping or production/Dev modification occurred.

## R2 — Public historical URL inventory: PASS (bounded)
- Internet Archive CDX public index, historic captures principally 2023–2026; recovered and normalized **3,459** distinct historic page paths.
- **2,708** product routes; **432** probable category routes; **53** taxonomies; **130** pagination paths; other routes preserved with category hints.
- CDX collection executed in scoped, rate-limited batches without reported errors. Two product-specific query scopes exhausted; two broad scopes remained truncated by configured batch limits. Therefore **the complete historic or current URL count is UNKNOWN**.
- [Downloadable CSV in this public repository](yourdoll-historical-urls-v2-2026-10-28.csv). Each row has a historical capture timestamp, category hint, live verification state and actual private HTML sample flag.
- Source scripts: [R2 inventory](../../scripts/reference_r2_inventory_v2.py).

## R3 — Private historical HTML sampling: PASS (representative, not all pages)
- **93** distinct successful HTML snapshots, including category, product, editorial, policy and pagination views.
- **1** historical HTML retrieval failure was recorded and excluded from successful samples.
- Sample / discovered-URL ratio: **2.69%**. This is a sampling indicator, **not** measured coverage of the live site.
- Original HTML and manifest are in the private China Mac archive; the public GitHub repo contains metadata and scripts only.
- Archive sampler: [R3 private samples](../../scripts/reference_r3_private_samples.py).

## R4 — Referenced static resources: PASS (inventory only)
- Parsed the successful HTML examples without contacting original asset hosts.
- **22,008** distinct resource URL references: **21,349** images, **360** CSS, **234** JS, **11** fonts, **54** videos.
- Actual asset files downloaded in this phase: **0**. Resource URLs are historical references, may be missing/duplicated across versions, and are not a backup of media/assets.
- The detailed third-party resource URL CSV remains **private** on China Mac.

## R5 — Independent iDollmate architecture input: PASS (design only)
- [Original iDollmate information architecture and WooCommerce data model](../architecture/IDOLLMATE_IA_V1.md).
- Specifies original home, product archive, product detail, filters/variations, blog, policies and customer account/checkout planning with explicit source-evidence boundaries.
- The source shows historical WooCommerce and WoodMart signatures; neither competitor content nor proprietary theme assets are copied into the Dev store.

## R6 — Integrity / provenance / packaging: PASS (bounded archive only)
- Verified **93/93** successful HTML samples against their recorded SHA-256, HTML structure and document title. QA exceptions: **0**.
- Private archive path (China Mac): `~/AI/idollmate/reference-archive/yourdoll/2026-10-28/yourdoll-reference-r2-r6-20261028.tar.gz`.
- Archive size: **8,560,097 bytes**; SHA-256: `c176bdba32794e24bafe6be41d60c4f1280a33007f94f2d0e86eb196ef40aa22`.
- A separate tarfile read-back verified the archive could be opened and contained all **93** expected HTML files and the audit evidence.
- [Offline R4/R6 audit tool](../../scripts/reference_r4_r6_offline_audit.py).
- Metadata from internet archives can support historical research, **not** verification of a current source site restore.

## Acceptance decisions
| Boundary | Gate |
|---|---|
| R2 historic URL inventory | PASS (bounded discovery) |
| R3 representative private HTML | PASS (93 valid examples) |
| R4 static resource reference list | PASS (list only; no file copies) |
| R5 iDollmate original architecture input | PASS (documentation committed) |
| R6 private reference archive consistency | PASS (SHA-256 + tar readback) |
| **100% current YourDoll live-page backup** | **BLOCKED / NOT ACCEPTED — source 403 and no authorized export** |
| **All original images, assets, DB and functional website restore** | **NOT ACCEPTED — not collected or tested** |

**Handoff:** R7 may start as a distinct Dev-only implementation task from the original architecture input, subject to Owner approval. GitHub Issue #1 must remain open for source-authorization/full-site conditions.
