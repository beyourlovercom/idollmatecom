# R7-F front-end visual integration and R7-H guest checkout blocker — 2026-10-29

**Scope:** only `dev.idollmate.com`, Owner-approved disposable Dev (database backup waiver). Production `idollmate.com` / Shopify DNS untouched. Original YourDoll image URLs stored as text only; no downloaded image binaries or backups.

## Deliverables deployed to Dev

| Module | Version | Status |
|---|---|---|
| Catalog Fields | 0.2.0 | Active, unchanged |
| Original Home Layout | **0.2.0** | **Active, upgraded from 0.1.0** |
| Storefront UI | **0.1.0** | **Active, new CSS-only Woo layout layer** |
| WooCommerce | 11.2.1 | Active, core payments/pricing untouched |

Home V0.2 adds section jump links (Categories, Products, How to choose), original three-step guidance, accessible headings and responsive card layout. It retains original CSS-only art, uses actual WC catalog data and never inserts remote or local image assets. No products were imported. The original UAT preview page #14 remains the only preview, using the existing no-title page template; it was **not** assigned as the WordPress front page.

Storefront UI V0.1 adds a narrowly scoped `idm-storefront-ui` body class and a small CSS-only layer on actual Woo product/category/cart/checkout views, covering type hierarchy, product variation select, ThemeComplete options container, prices, add-to-cart and checkout block controls. No WC calculation, cart, order, gateway, shipping, tax or customer data hooks; no fonts/images/network requests.

Package SHA-256 from China Mac:
- `idollmate-home-0.2.0.zip`: `c2400bfdd01d89a21ac648831f8732e705d7c5747c4ea476bf2a15a812d8f396`
- `idollmate-storefront-0.1.0.zip`: `11b4ee893b8c4ca81a4039bbd1f20c1f38a188d935178c7d037d70c2d95504ec`

## Verified in actual headless Chrome

**Home, one-page test switched desktop 1440px to mobile 390px:**
- HTTP 200; `noindex,nofollow` in both viewports.
- H1 count **1**, 3 jump links correctly resolve to IDs, 3 original guide cards appear.
- Mobile and desktop widths equal document scroll width (no horizontal overflow).
- No uncaught page JS errors, `idollmate-home/assets/home.css` loaded, **0** `.idm-home img` elements.

**Product → Cart → Checkout in isolated Dev test:**
- A controlled runner temporarily set hidden QA product #15 to publish and Woo `woocommerce_coming_soon=no` only during the browser test; it restored **Draft + coming_soon=yes** on exit.
- Product view loaded new Storefront CSS/body class. Actual Add to Cart button computed background `rgb(164,82,105)`; Woo variation and 3 ThemeComplete options visible.
- Actual Chrome TPE $999 + head $149 showed **$1,148 Final Total**. Add to Cart → Woo Cart Block **$1,148** with exact material and paid option; Checkout block also displayed corresponding option amount. No checkout form was submitted in this visual test.
- Desktop and 390px phone CSS: no horizontal overflow; zero uncaught JS errors.

**No human signed-off aesthetic choice:** Code/UI technical regressions PASS; final editorial brand design remains subject to Owner's visual acceptance.

## Additional R7-H browser-initiated guest checkout exploration

Temporarily created a **test-only offline BACS** method (`R7 TEST ONLY - DO NOT PAY`), US-only flat shipping **$200** and a WordPress `pre_wp_mail` suppression mu-plugin; temporary product #15 was hidden from catalog but published during tester browsing, Coming Soon disabled only for the short UAT run.

Woo checkout block loaded and displayed:
- TPE with head: **$1,148**
- Synthetic QA shipping: **$200**
- Expected checkout total: **$1,348**
- BACS offline test method displayed and selected.
- React shipping address fields correctly mapped to `shipping-first_name`, `shipping-last_name`, `shipping-address_1`, `shipping-city`, `shipping-state`, `shipping-postcode`.

**Final browser order placement: NOT PASS.** Repeatedly clicking `Place Order` while form values and synthetic BACS method were present remained on `/checkout/`; no order confirmation and **0 guest QA orders created**. The tool's limited Store API listener did not capture a checkout-response event; therefore *the root cause is undetermined* — do not assert API request was never sent. Earlier attempts exposed test-runner issues (checkbox activation timing, wrong billing-vs-shipping field IDs and a page navigation race), corrected before this final attempt. Those runner issues should not be labeled confirmed Woo/ThemeComplete bugs. No genuine gateway interaction or webhook took place.

**Cleanup verified read-only after all attempts:** `blog_public=0`, `woocommerce_coming_soon=yes`, test product #15 Draft, **0** shipping zones, taxes `no`, BACS `no`, **0** QA guest orders, temporary mail suppression MU-plugin **removed**, QA state file **removed**. WordPress attachment count remains **1**, unchanged. Production unaffected.

**R7-H follow-up:** Use an explicitly authorized, category-compatible iDollmate TEST payment account if available. Independently diagnose Checkout Block UI submission by instrumenting the Store API network traffic + Gutenberg/WC logs, or use an isolated Woo classic-checkout control test (no real sending), then test success/decline, webhook signature, paid order, refund. Do not borrow unrelated BeYourLover/SVAKOM merchant credentials or mark an external refund PASS on the strength of Woo internal bookkeeping. Retain Coming Soon and Draft while not actively testing.

## CI and Git state
- `tests/idollmate_home_smoke.php`: PASS.
- `tests/idollmate_storefront_smoke.php`: PASS; CSS only on customer-facing WC screens, no images/font URLs.
- PHP syntax for both plugin files: PASS.
- `.github/workflows/r7-smoke.yml` now also includes the Storefront module and new test.
- Draft PR #3 remains **unmerged**, Production release requires separate pass.
