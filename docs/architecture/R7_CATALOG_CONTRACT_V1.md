# R7 — iDollmate Catalog Contract V1 (2026-10-28)

**Status:** Implementation proposal on branch `feat/r7-original-catalog-v1`; no deployment. The production domain and existing WordPress Dev theme were not changed.

## Evidence checked
- YourDoll archived 2026-05 Sitemap: **6,773 historical distinct URLs**, partitioned into 7 product sitemap parts, product category, tag, post and page indexes. These are **historical** records, not current live inventory or complete product data. Source: [historical URL manifest](../reference/yourdoll-wayback-sitemap-202605.csv).
- The earlier CDX archive contained 3,459 historical URL paths; union with sitemap is 7,677 URL paths. Do not import competitor copy or media.
- Read-only `dev.idollmate.com` checked 2026-10-28: WordPress **7.1.3**, WooCommerce **11.2.1**, active theme **hostinger-ai-theme 2.1.15**, and **woocommerce-tm-extra-product-options 7.6.2** active; Woo `product_brand` taxonomy exists; **0 published products**; `blog_public=1` (this is a **new discrepancy** against previously reported noindex; recheck in Dev UI before release). NOTE: this readback is not authorization to alter the site.

## Do not duplicate WooCommerce
| Requirement | Owning layer | Proposed data |
|---|---|---|
| SKU, real price, sale price, stock qty / status | WooCommerce core | `_sku`, `_price`, stock fields; do not duplicate |
| Manufacturer / brand | WooCommerce Brands | Built-in `product_brand` exists on the verified Dev environment; no new `idm_brand` taxonomy |
| Material / height / weight / skin tone / head / body style | Woo global attributes | `pa_material`, `pa_height_cm`, `pa_weight_kg`, `pa_skin_tone`, `pa_head_style`, `pa_body_style`; exact ranges require catalog data |
| Product variation / sellable SKUs | WooCommerce variations | Use real SKU variations only when physical variant drives stock or distinct sale SKU |
| Optional paid configurations | Existing Extra Product Options plugin | First audit plugin license, provenance, compatibility and price arithmetic; do not implement competing option engine |
| Fulfillment model | iDollmate Catalog Fields (R7 candidate) | `_idm_fulfillment_mode`: factory made-to-order or stock |
| Dispatch region | iDollmate Catalog Fields | `_idm_dispatch_region`: CN / US / EU / OTHER |
| Estimate to dispatch | iDollmate Catalog Fields | `_idm_estimated_dispatch_days`: integer 0–180; backend only until verified |
| Carrier/actual shipping rates, checkout/tax | Woo shipping / payment tools | Never infer from private metadata; only test-mode validation on Dev |

## Original storefront information architecture
- **Home:** product discovery, catalog categories, original imagery, verified shipping/payment reassurance, editorials.
- **Shop and categories:** /shop/ and Woo product-category slugs; sort, facet filters, pagination, breadcrumbs and canonical SEO.
- **Product:** original image gallery with usage rights, real variation selection, Woo total reconciliation, dispatch notice only after verification.
- **Cart/Checkout/Account:** Woo native endpoints stay canonical. Preserve refund/account privacy controls; theme styling must not replace gateway functions.
- **Blog/FAQ/Policies:** independently written pages with owned content. No 1:1 source URL cloning.

## Theme rule
The existing Hostinger AI theme is active. A partner previously proposed WoodMart, but there is no verified theme license or approval to install it. **Keep catalog rules theme-neutral** and defer storefront theme choice to independent Owner sign-off. WooCommerce Extra Product Options is active; never migrate or rewrite its existing option model without a read-only compatibility audit.

## Plugin release gates
- `wordpress/plugins/idollmate-catalog/idollmate-catalog.php` is source-only, not active.
- Plugin only augments backend Woo shipping product fields, validates allowed values, and saves optional fields in Woo's authenticated product-save lifecycle. It does not register new brands, create products, modify Woo prices/stock, enqueue assets, send emails, call APIs, add storefront messaging, or change database schema by itself.
- **Before Dev deployment:** Owner pass, confirm PHP syntax check, Woo theme/editor compatibility, recheck Dev indexing and rollback/activation status. For block-editor products, the classic field hook may not display: mark UAT not-applicable until verified.
- **Before Production:** separate pass and completed payment/shipping/option price UAT; no production changes as part of R7.

## Development sequencing
1. Source checks and documented baseline (this PR).
2. Owner review of Woo attributes and theme choice, Dev deployment approval.
3. Backoffice UAT: product field persistence, brand/attribute mapping and no price/stock side effects.
4. Original storefront templates, responsive customer UX, with test content only.
5. Checkout including add-on price arithmetic, tax, shipping, refunds and rollbacks.

## R7.1 image reference rule (Owner pass, 2026-10-28)
- **Temporary Dev only**: all third-party research image references, if needed, use the original `https://www.yourdoll.com/wp-content/uploads/...` URL **as a URL string**, not a file. This is not a license to display competitor imagery commercially.
- Never download, resize/re-encode, sideload to Woo product gallery, create WordPress media attachments, store image binaries, create proxy/CDN mirrors, or publish original photo/HTML to this public GitHub repository.
- Catalog Fields V0.2 adds the backend-only field `_idm_reference_image_url` with HTTPS, exact host and supported raster extension validation. It does **not** render that image to customers, invoke HTTP GET/HEAD at save time, or alter Woo featured/gallery images.
- **Operational verification**: sampled direct origin image URLs returned HTTP 403 even with first-party Referer. Consequently the homepage retains a CSS-only, image-free placeholder; do not force broken third-party image tags into the public-facing Dev UI. This can be revisited only when the image origin allows normal loading *and* usage rights are established.
- No image backup is required or desired in the current iDollmate development phase; the private historical inventory remains metadata-only for image URLs.
