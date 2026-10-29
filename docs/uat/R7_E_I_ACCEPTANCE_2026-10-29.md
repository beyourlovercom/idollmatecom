# R7 E–I acceptance update — 2026-10-29

**Scope:** `dev.idollmate.com` only. Database backup intentionally waived for this disposable Dev by the Owner. Nothing was changed on Production / Shopify DNS. No competitor image file downloaded or mirrored: YourDoll image data remains original-URL-only metadata.

## R7-E — Real customer browser interaction: PASS for core flow

Original first-party automated Chrome browser inspection showed WooCommerce products/Shop/Cart displaying “Great things are on the horizon.” The cause was conclusively **WooCommerce Coming Soon** (`woocommerce_coming_soon=yes`), *not* a Hostinger AI theme template defect. During isolated browser UAT, this option was changed temporarily to `no` and sample product **#15** published with catalog visibility **hidden**; a shell EXIT cleanup restored **Coming Soon=yes** and product status **draft**. `blog_public=0` throughout. Public checkout payment was never submitted.

Playwright Core launched separate **headless Chrome**, not existing operator browser windows, with image network requests aborted. Actual Woo EPO product page confirmed:
- Native Woo variation dropdown **TPE / Silicone**.
- **Three** product-local ThemeComplete checkboxes, regular +$149, +$59, +$35, with no image files.
- Selecting TPE and Premium Head produced visible dynamic **Final Total $1,148**, base $999 plus +$149; **Add to cart** active.
- Clicking Add to Cart and opening the **WooCommerce Cart block** showed TPE material, Premium Head +$149, line amount and cart estimated total **$1,148**.
- **WooCommerce Checkout block** rendered contact and billing fields and contained matching **$1,148** amount. No guest payment was attempted because **no payment gateway is enabled**.
- Headless Chrome desktop width 1440 and mobile responsive width 390: no horizontal overflow in product layout; zero uncaught page JS errors. Mobile full checkout form-completion UAT is not claimed.

Initial public browsing showed the storefront coming-soon placeholder because of WC Coming Soon; this was not a broken Shop/Cart theme. Its option is **restored to yes** for normal Dev.

## R7-F — Minimal Dev visual fix: PASS (not full design completion)
- UAT home page **#14** had 2 H1 tags due default theme title plus hero. The existing native `no-title` block template was assigned **only** to UAT page #14, with LiteSpeed cache purge; public readback returned **1 H1**.
- No theme file was edited, no public homepage replaced, no new images copied. Original CSS-only hero and actual Woo catalog renderer remain as before.
- Full branding/layout polish and human visual signoff remain open.

## R7-G — Real shipping + tax + checkout accounting: PASS for isolated fixture

Temporarily created a WooCommerce US shipping zone with flat-rate **$200** and a **synthetic test-only 5%** tax rate for US/CA, enabled Dev taxes, and ran a real WC cart and server Checkout order with product #15. These amounts do **not** assert a market rate or legal tax obligation.

| Item | Actual |
|---|---:|
| TPE base with Premium Head | $1,148.00 |
| Hypothetical product tax (5%) | $57.40 |
| Test flat shipping | $200.00 |
| Cart total | **$1,405.40** |
| Checkout-created order total | **$1,405.40** |

Temporary test order **#21** deleted. Finally **taxes restored OFF**, **0 shipping zones**, tax rate deleted, shipping method settings removed, cart empty, sample product Draft.

## R7-H — Internal refund bookkeeping PASS; external gateway NOT TESTED

Created a no-gateway Woo Checkout order for **$1,148**, then a **$149 partial Woo native refund**, reading **$149 refunded / $999 remaining**. No card charge, webhook, or service-provider refund took place. The synthetic order and refund were both deleted; test email sending suppressed.

**Blocking gate:** No Stripe/PayPal or other approved iDollmate **TEST** payment gateway is installed or configured. BACS, Cheque and COD were disabled at last read. Do not connect an unrelated BeYourLover or SVAKOM merchant account to iDollmate. Need an appropriately authorized iDollmate test merchant and integration before gateway capture, webhook, failure, paid order and external refund can pass. Separate Production signoff remains mandatory.

## R7-I — Regression & isolation: PASS with caveats

- Installed Dev plugins: `idollmate-catalog 0.2.0` and `idollmate-home 0.1.0` active, WooCommerce 11.2.1.
- Re-ran PHP lint for both source plugins and isolated catalog/home smoke suites: PASS.
- `scripts/r7_dev_http_smoke.py`: **9/9 PASS**, including `/`, `/idollmate-home-uat/`, `/shop/`, `/cart/`, `/checkout/`, `/my-account/` (HTTP 200 with noindex), draft product URLs #13/#15 (HTTP 404), and home CSS (HTTP 200).
- Final site readback: `blog_public=0`, `woocommerce_coming_soon=yes`, product #15 **draft**, 3 temporary TM choices on this draft only, **0** published global TM forms, **0** shipping zones, `woocommerce_calc_taxes=no`. WordPress media attachment count **1** (unchanged by R7 image-reference work).
- Temporary Checkout orders #19 and #21 independently confirmed deleted. Synthetic partial-refund order/refund also deleted in the same test.
- No `wp-content/debug.log` present during read-only inspection; **absence of a log is not evidence of no errors**. Previous intermittent Hostinger PHP CLI SIGKILL/137 had recovered but root cause remains uncertain.
- GitHub PR #3 has no reported checks; manually executed lint and smoke tests must not be portrayed as GitHub CI.

## Remaining R7-J release conditions

1. Select and authorize an **iDollmate-specific eligible TEST payment merchant**; finish payment acceptance, failed/3DS/cancel handling, webhook signature verification, gateway refund and rollback. No simulated refund can substitute.
2. Complete branding / original storefront visual review and mobile real checkout confirmation. Do not auto-publish image URLs that return 403 or lack image reproduction rights.
3. Add CI workflow/checks as applicable before merging source PR #3. Keep it **Draft / unmerged** until acceptance.
4. Any Production deployment, migration off Shopify or DNS change requires separate explicit Owner **pass**.

**Source:** [R7 Cart / Checkout UAT matrix](R7_TM_OPTIONS_CHECKOUT_MATRIX.md), [R7 Dev release checklist](R7_DEV_RELEASE_CHECKLIST.md), [PR #3](https://github.com/beyourlovercom/idollmatecom/pull/3).
