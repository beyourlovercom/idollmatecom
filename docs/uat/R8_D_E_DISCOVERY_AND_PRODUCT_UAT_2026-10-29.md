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

- Confirm installed plugin version 0.1.1 and active with WP/Woo version and current theme.
- Confirm WC archives 200, robots noindex/nofollow, canonical URLs correct, category links and filter present.
- Confirm filters for tpe/silicone and invalid values; empty public results while no products are published; no disclosure of hidden Draft QA products.
- Admin Woo manager preview: 2 parent/simple cards, filters categories 1/1 and materials 2/1, variation price range, valid preview links. Product detail native variation and responsive media placeholders; image count remains 0.
- Confirm frozen R7 product digests, all 4 R8 QA integrity hashes, applied batch journal, no duplicate SKUs.
- Ensure PRs remain Draft/unmerged and production unchanged. Uninstall/delete the discovery plugin only through separate sign-off if desired.

## Release status

UAT results must be appended after executing real Dev checks, rather than inferred from passing source tests.

## Genuine block-theme and Coming Soon compatibility correction

Real anonymous HTTP on /shop/ and category URLs returned Woo's global Coming Soon block instead of
the archive; no merchant filters can be visible to anonymous guests while that pre-existing gate is ON.
This is expected and the gate MUST remain ON. Actual archive block template, inspected through
get_block_template(), uses the WooCommerce Product Collection block, not classic Woo hooks.
The 0.1.1 revision adds scoped query_loop_block_query_vars and render_block_woocommerce/product-collection
integration for the inherited main archive block; it does not affect custom collections or other pages.
This is supported by the official WordPress Query Loop hook and WooCommerce Product Collection
implementation. Test with authenticated Woo administrator render, never by publishing QA products.

## Executed real Dev UAT evidence, 2026-10-29

Real runtime: WordPress 7.1.4, WooCommerce 11.2.1, Hostinger AI block
theme, Woo Product Collection archive, Active idollmate-discovery v0.1.1,
native Extra Product Options, USD, blog_public=0, Coming Soon=yes.

R8-D Dev functional UAT: PASS.
- Existing /shop/, /product-category/mini-dolls/ and
  /product-category/full-body-dolls/ return HTTP 200.
- All three canonical routes correct; filtered canonical removes material.
  Dev robots remain noindex, nofollow.
- R8 discovery isolated 36/36 PASS, including malformed filter input and
  nonarchive protection.
- Real WordPress active block template introspected: inherited Woo Product
  Collection block. Real runtime render_block() shop-context test:
  R8_ACTUAL_WOO_BLOCK_RENDER_PASS, exactly one filter form,
  both category links, zero Draft QA SKU leakage.
- Real WP-context tests: tpe/silicone filters append to Woo query constraints,
  unknown values fail closed; prior Woo visibility constraints preserved.
- Anonymous HTTP renders Woo global Coming Soon, not live shop catalog;
  this is EXPECTED and must not be disabled for UAT. Anonymous QA links
  return 404 and admin QA route redirects to login.

R8-E real QA data and admin render functional UAT: PASS.
- In actual Woo admin-role context, restricted QA page has 7/7 expected
  filter results: all=2, mini=1, full-body=1, tpe=2, silicone=1,
  mini+silicone=0, invalid=0, all with matching SKU lists.
- Valid native Woo draft product preview links for #32 and #33, and
  administrator-only QA specifications in the single-product hook.
- #32 simple Draft/Hidden USD599; #33 variable parent Draft/Hidden;
  #34 TPE child USD999 and #35 Silicone child USD1099, both under #33.
  Woo readback returned 2 available variations, no QA images/galleries.
  Native Extra Product Options remains active and unchanged.
- Fresh R8 protected Woo snapshot matches original 4 R7 product digests,
  4 retained R8 QA IDs, unchanged taxonomy, Coming Soon and noindex.
- R7 HTTP regression 9/9 PASS. R8 guard PHP 31/31 PASS;
  R8 discovery PHP 36/36 PASS; R8 catalog Python 31/31 PASS.
- No import, publishing, rollback, order, image or payment writes.

Still outstanding and NOT falsely claimed complete:
1. Authenticated actual browser screenshots + mobile/desktop visual signoff
   for Woo Draft product detail, variations, admin QA cards and filter UI.
   In-process Woo HTML rendering is a functional test, NOT equivalent to
   browser UX screenshots.
2. Multi-page public catalog pagination cannot be validated with only
   four hidden Draft QA products; requires later lawful original products.
3. R8-E does not authorize add-to-cart/payment, reference image ingestion
   or public QA publication.
4. PR #3 and #5 remain Draft/unmerged, R8 Issue #4 open, Production Shopify
   and DNS unchanged.

Current status: R8-D Dev functional UAT PASS; R8-E admin QA data/display
functional UAT PASS; final browser visual and scale UAT remain OPEN.
