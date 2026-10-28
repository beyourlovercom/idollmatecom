# iDollmate IA / WooCommerce Architecture Input V1
Date: 2026-10-28
Status: **Research input, NOT deployed**
Source: [YourDoll historical-reference R2–R6](../reference/yourdoll-r2-r6-acceptance-2026-10-28.md)

## Evidence boundary
The preceding archive contains only **historical** Internet Archive URL metadata and private historical HTML examples. The current YourDoll site, sitemap and robots.txt returned HTTP 403 on independent China/US machine probes. No attempt was made to bypass Cloudflare and no direct live WooCommerce functional test of YourDoll was performed. Product media, original copy, scripts and proprietary design assets are **not** licensed for reuse. All iDollmate code and content must be original or separately licensed.

Observed in the sampled historical HTML:
- **Homepage:** multiple catalog category blocks, promotional/featured product areas, reassurance/trust areas, manufacturer/brand groupings, some fast-ship / stock messaging.
- **Category/listing:** category landings, sorting forms, storefront product listings, category/tag pages and paginated routes.
- **Product detail:** product gallery, product measurements and specification sections, product/cart forms; some snapshots show variation components and reviews.
- **Editorial and policy:** blog/article archives, guides, about, affiliate, payment, shipping and returns pages.
- **Platform signatures:** historical snapshots contain WordPress, WooCommerce and WoodMart CSS/class clues. This is observational evidence, **not** a directive to copy their theme.
- **NOT verified:** current catalog totals, live prices, product stock, customer accounts, payment gateway, checkout, shipping rules, functional behavior, current SEO indexes.

## Proposed iDollmate route and template map
| Priority | Proposed route | Original template/function | Woo/WordPress implementation |
|---|---|---|---|
| P0 | `/` | Home hero, discovery grid, featured items, trust/fulfillment notes | Original homepage |
| P0 | `/shop/` | Main catalog, sorting, pagination, filter controls | WooCommerce shop archive |
| P0 | `/product/{slug}/` | Gallery, verified specifications, variant selection, cart action, delivery notes, reviews | Woo product single |
| P0 | `/product-category/{term}/` | Human-readable material, dimensions, body, availability groupings | Woo product_cat |
| P0 | `/cart/` | Cart editing, totals and shipping prompt | Woo cart |
| P0 | `/checkout/` | Validated checkout, currency, tax, shipping, privacy, test payments | Woo checkout |
| P0 | `/my-account/` | Login, orders and account support | Woo account |
| P1 | `/blog/` and individual posts | Original buying guides, comparisons, education, manufacturer updates | WordPress posts |
| P1 | `/about/`, `/contact/` | Original business and support content | WP pages |
| P1 | shipping / return / privacy / FAQ | Explicit customer-facing policies | WP pages and linked FAQs |
| P2 | `/brands/` and optional manufacturer hubs | Manufacturer taxonomy when legally appropriate | Custom Woo taxonomy |
| P2 | affiliate/help landing pages | Optional independent marketing modules | Andy Core / Andy Campaign as approved |

**No direct 1:1 mapping** is permitted from competitor URLs/text/images into iDollmate. The route list is an implementation proposal, not a migration plan for YourDoll or a claim about live URL structure.

## Data model for original WooCommerce catalog
- **Core fields:** SKU, original product title, published/sale status, price/currency, stock, dimensions, shipping weight, images with valid usage rights, category, product warranty.
- **Filter taxonomy candidates:** height, weight, material, body style, head style, skin tone, hair, manufacturer/brand, dispatch warehouse, stock/lead-time.
- **Variation vs configurable add-ons:** use Woo variations for true sellable SKU combinations. Avoid combinatorial SKU explosion; consider approved add-on pricing logic for optional heads or accessories only after explicit product and checkout tests.
- **Availability:** factory made-to-order vs local stock must be clearly distinct; never infer live inventory from historical snapshots.
- **Content:** original descriptions and photography; documented provenance / licensing for all imports.
- **SEO:** unique original titles, structured data, breadcrumb consistency, canonical route policy, paginated category rules, XML sitemap, no-index Dev.
- **Operations:** backend/admin is desktop-only per Andy governance; **customer storefront must be responsive**, including mobile checkout and performance UAT.

## Implementation readiness / Owner gate
1. Finalize and sign off product taxonomy, WooCommerce permalink scheme and licensed theme choice (WoodMart license vs custom theme; existing reference theme signatures imply no licensing rights).
2. Produce low-risk Dev-only original homepage / category / product template with placeholder original or licensed assets.
3. Validate product filtering, real variant changes, cart, test payments, currency, taxes, shipping, emails, returns and performance across desktop/mobile before release approval.
4. Keep `idollmate.com` production/Shopify DNS untouched until separately authorized by Owner `pass`; never publish the reference archive or imported competitor images/copy.

## R7 handoff
This file is the **architecture input** from R2–R6, ready for the R7 development proposal. No site code, template or production database deployment was authorized or made during R2–R6.
