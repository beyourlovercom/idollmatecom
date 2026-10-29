# R8-D/E Dev taxonomy, material facets, SEO routes and QA product display

Date: 2026-10-29. Status: implementation and real-Dev UAT underway.

## Boundaries

This source-only catalog discovery layer is original, theme-neutral, and reuses native WooCommerce product_cat, pa_material, SKU/variation relationship and product prices. No new taxonomies, terms, products, images, public QA products, payment methods, shipping assumptions, order changes or rewrite flushing. Existing Dev is noindex and Coming Soon. No production Shopify changes.

Source files: wordpress/plugins/idollmate-discovery/idollmate-discovery.php and assets/discovery.css.
Isolated tests: tests/r8_discovery_smoke.php.

## R8-D scope

1. Public Woo shop and product-category archive (native routes /shop/ and /product-category/mini-dolls/, /product-category/full-body-dolls/).
2. Explicit allowlist of global material facet slugs: tpe, silicone. On Woo native main archive query, compose pa_material taxonomy constraint without replacing Woo visibility/status controls. Reject malformed/unknown values with an impossible taxonomy clause (never broaden a result).
3. Accessible Material select, category links, Clear filter, Woo pagination preserves approved facet value and sort where appropriate.
4. SEO: filtered archives noindex, canonical strips filter arguments to root shop/category URL. Nonfiltered paginated archives use own page URL. Do not override canonical providers if installed. Native Woo parent product permalink remains canonical; no child variation SEO route.
5. Dev global noindex and Coming Soon are invariant. Since all R8 QA products are Draft/Hidden, **public category archives correctly show no QA products**. Do not publish test data merely to populate public archive.

## R8-E scope

Authenticated Woo manager-only Admin > Products > iDollmate R8 QA lists the *exact approved batch* only if applied, count=4 and IDs [32,33,34,35]. Test category and material filters on the Draft/Hidden parent and simple products, prices USD599 / USD999 / USD1099 and approved variation mapping. The UI uses a local CSS-only empty image placeholder; no external images or media import. Preview buttons link to the authenticated native Woo product preview, preserving plugin and native variations; QA specifications are only shown to an authenticated Woo manager on that precise batch, never to public users.

R8-E is not payment testing, a stock promise, or permission to publish products.

## Dev UAT checklist

- Confirm installed plugin version 0.1.0 and active with WP/Woo version and current theme.
- Confirm WC archives 200, robots noindex/nofollow, canonical URLs correct, category links and filter present.
- Confirm filters for tpe/silicone and invalid values; empty public results while no products are published; no disclosure of hidden Draft QA products.
- Admin Woo manager preview: 2 parent/simple cards, filters categories 1/1 and materials 2/1, variation price range, valid preview links. Product detail native variation and responsive media placeholders; image count remains 0.
- Confirm frozen R7 product digests, all 4 R8 QA integrity hashes, applied batch journal, no duplicate SKUs.
- Ensure PRs remain Draft/unmerged and production unchanged. Uninstall/delete the discovery plugin only through separate sign-off if desired.

## Release status

UAT results must be appended after executing real Dev checks, rather than inferred from passing source tests.
