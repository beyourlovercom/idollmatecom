# R8-A — Catalog OS V1 schema and WooCommerce ownership contract

**Status**: R8 source-first proposal; **not a product import or live category launch**. Based on frozen R7 field ownership and original iDollmate IA.

## Ownership / critical constraints

- Existing **WooCommerce** owns master SKU, variable/variation product relationship, unique WP slug, native US-dollar price, inventory status/quantities, `product_cat`, `product_brand`, global attribute taxonomies and permalink endpoints. Do **not** reimplement Woo models.
- Existing ThemeComplete Extra Product Options owns **paid upgrades** such as premium head, skeleton, accessories. Only material/size combinations that are *real sellable SKUs with distinct stock/price* become native Woo variations. Avoid `N material × N skin × N hair × N head × N skeleton` SKU explosions.
- Existing `idollmate-catalog` R7 fields own `_idm_fulfillment_mode` (`factory_mto` or `local_stock`), `_idm_dispatch_region` (CN/US/EU/OTHER) and `_idm_estimated_dispatch_days` (0–180). These **do not** set shipping prices or promises.
- Dev stays **noindex / Coming Soon / Draft**; importer must not publish, change orders, customers, stock allocations or real payment settings. Production remains **Shopify** and its data model is not implicitly changed by Woo Dev.

## Provisional product hierarchy (requires category editorial approval)

Main category **one per parent/simple product**; secondary shopper facets use Woo global attributes, not duplicate categories. Categories below are proposals, not a claim about the competitor or live catalog:

| `product_cat` slug proposal | Customer-facing concept |
|---|---|
| `full-body-dolls` | Full-body dolls |
| `torso-dolls` | Torso dolls |
| `mini-dolls` | Compact / mini dolls |
| `anime-style-dolls` | Anime-style dolls |
| `custom-dolls` | Customizable models |

Material is **not** a root category (use Woo `pa_material`). Height/weight are numeric specs and filter bands, not one Woo category per centimeter or kilogram. Each parent product must have a unique descriptive slug; Woo variations are not SEO landing pages and inherit their parent.

Proposed global attributes:
- `pa_material`: `tpe`, `silicone`, `hybrid` (only if verified for product).
- `pa_height_cm`: standardized numeric centimeters used for discovery/filter bands; raw measurement should also remain in original specs, not be confused with an EPO price option.
- `pa_skin_tone`, `pa_body_style`, `pa_head_style`: only values grounded in supplier/product data; exact term dictionary is editorially reviewed before import.
- `pa_manufacturer` is **not** created; use the already available Woo `product_brand`.
- `pa_size_code`: optional verified model-specific sellable size identifier, not a centimeter string by default.

## Batch CSV V1: one row per distinct SKU

**Canonical columns (all included, even when a cell is blank):**

| Column | Meaning | Rules |
|---|---|---|
| `sku` | Unique stable item identifier | ASCII 3–64 chars `A-Z a-z 0-9 . _ -`; duplicates and existing SKUs rejected |
| `row_type` | `simple`, `variable`, `variation` | `variable` parent has >=1 child variation in same batch |
| `parent_sku` | Parent SKU only for `variation` | Must exist in batch and be `variable` |
| `title` | Original/authorized product title | Mandatory on `simple` and `variable`; variation inherits parent title |
| `slug` | Parent-facing route suffix | Mandatory on `simple`/`variable`, unique, lowercase hyphenated; blank for `variation` |
| `category_slug` | Root product category | Required for `simple`/`variable`, blank for `variation`, in approved provisional list |
| `brand_slug` | Woo native Brand term slug | Optional, no new brand taxonomy; importing will require a pre-mapped term |
| `material` | `tpe`, `silicone`, `hybrid` | Optional simple/parent; variation requires material or `size_code` |
| `size_code` | Verified model size variant | Short stable alphanumeric code; variations may use material + size |
| `height_cm`, `weight_kg` | Positive metric specs | Optional for parent/simple; no field-level conversion guess |
| `fulfillment_mode` | `factory_mto` / `local_stock` | Required parent/simple; variation inherits |
| `dispatch_region` | CN/US/EU/OTHER | Required parent/simple; variation inherits |
| `lead_days` | Estimated dispatch time | Optional integer 0–180 for parent/simple; not a customer promise |
| `price_usd` | Woo base regular price | Required for simple/variation, decimal with max 2 places, no tax/shipping; blank variable parent |
| `stock_quantity` | Woo physical stock quantity | Required nonnegative integer on `local_stock` simple/variation; blank for factory_mto |
| `content_origin` | Provenance declaration | `original` or `licensed`; no competitor copied content |
| `source_ref` | Internal lawful original/supplier source evidence ID | Non-empty marker; provenance self-declaration is **not** legal proof |
| `reference_image_url` | R7 private reference **URL only** | Optional, strict original `https://(www.)yourdoll.com/wp-content/uploads/....(jpg/png/webp/gif/avif)`, no query/userinfo/fragment; no HTTP request, rendering or media import |
| `product_status` | Woo status constraint | **Must be `draft`** for R8 V1; no CSV-controlled publish/pending/private |

Do not include product descriptions, unauthorized images, supplier secrets, billing data or third-party page content in preflight sample files. After editorial signoff, a separate authorized-content pipeline can populate original product descriptions.

**Import path later** (not implemented by validator): authenticated admin → uploaded original CSV → **preflight Preview** → explicit Owner-confirmed scoped batch → Woo CRUD create Draft only → transaction/audit identifiers + rollback by batch. Never blindly call native Woo Importer for 2,000 rows before dry run and catalog ownership mapping.

## Performance and safety gates

- Dry-run should verify **2,000 rows** without mutating Woo/WordPress/database/network. Use incremental CSV read and O(n) dictionary sets; invalid records return useful row-specific errors, rather than silent partial imports.
- Reject duplicate SKU, conflict with an optional existing-SKU inventory manifest, duplicate parent slug, orphan variation, identical material/size under one parent, non-canonical taxonomy values, malformed monetary units, missing source evidence or unsafe spreadsheet formulas.
- JSON diagnostic report records count, counts by type, **SHA-256 source CSV**, row errors/warnings and a bounded preview (no credentials or contents). `--report` writes only local file, never uploaded data.
- These validations cannot prove legal ownership of content, supplier stock, shipping estimates or physical SKU accuracy: those require original source evidence and human UAT.
- All development and QA remain **source-only** pending explicit Dev rollout approval; never merge R7 PR #3 or R8 without independent Owner `pass`.

**Depends on:** [R7 closeout](../uat/R7_ENGINEERING_SCOPE_CLOSURE_AND_R8_HANDOFF_2026-10-29.md) and [R7 source ownership](R7_CATALOG_CONTRACT_V1.md).
