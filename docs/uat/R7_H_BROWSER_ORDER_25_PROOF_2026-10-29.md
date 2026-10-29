# R7-H controlled browser Checkout — first-click issue isolated
Date: 2026-10-29 (Hostinger UTC ~05:46)
Source: Actual Dev Playwright browser trace, WooCommerce Store API, and server debug log.

## Real successful browser order: PASS (offline QA only)
- Customer selected Woo variable product `IDM-R7-VARIANT-QA-20261028`, TPE $999.00 plus product-local ThemeComplete premium head +$149.00.
- Woo checkout Block cart data: `total_items=114800`, `total_shipping=20000`, `total_price=134800` cents USD. Tax 0.
- US/CA synthetic shipping address, valid required inputs; BACS explicitly marked `R7 TEST ONLY - DO NOT PAY`; no real processor.
- First normal browser Playwright click on `Place Order` had no Checkout status change, no validation errors, and no `POST /wc/store/v1/checkout`. It also had `clickCount=0` in a document-level capture listener — suggests the first click may have been lost during active form state/rerender or initialization. **This is not enough to assign a definitive Woo Blocks bug**.
- A *second* normal browser click (only after confirming there was no checkout POST and status remained idle) completed normally:
  - `POST /wp-json/wc/store/v1/checkout` returned **HTTP 200**
  - Browser redirected to `/checkout/order-received/25/`.
  - Actual WooCommerce debug log `place-order-debug-7d5d8b65-2026-10-29-...` records Store API steps: cart validated, addresses/customer updated, draft order #25 created, additional fields stored, payment method set, order validated, stock reserved and order processed. Total 1348.00.
- Woo test order **#25 was deleted** by the Dev-only fixture cleanup. Independent read-back `wc_get_order(25)` returned **DELETED**. Checkout had **no real card charge, webhook or provider refund**. All synthetic customer contact data used `example.invalid`.

## Repeat single-click test — not yet determinative
- Follow-up test changed the browser script to explicitly blur the final shipping-phone field before submit to avoid losing first click during Woo Blocks async address/rerender.
- The attempt did **not** reach a ready BACS Checkout screen (gateway marker wait timed out), so it did not submit. It does not prove the blur resolves or fails to resolve first-click UX. The limited trace must be extended to record checkout HTTP status/body summary on gateway-init failure; avoid guessing the root cause.
- WooCommerce community has seen cases of a first-click no-op on Blocks Checkout; official support suggests checking cache, plugin compatibility and logs, not declaring a general WooCommerce defect. Source: https://wordpress.org/support/topic/woocommerce-block-checkout-place-order-button-not-responding-initially/.

## Hostinger / fail-safe
- Account-level PHP CLI intermittent SIGKILL/137 and front-end HTTP 503 previously confirmed. Sum RSS exaggerates shared-memory usage; PSS is the more useful preliminary user-process metric, but official Hostinger limits/faults still needed.
- New Python orchestrator with try/finally and exact-match Dev-only DB rollback fallback prevents accidental lingering public QA products/payment config.
- After successful browser order #25 and after the inconclusive later test, cleanup verified: `blog_public=0`, Coming Soon=yes, product #15 Draft, BACS test option removed/disabled, synthetic US shipping zones=0, taxes off, temporary mail MU/state removed; Production Shopify DNS untouched.
- Original image policy remains URL-only; no image binary imports, downloads, copies, thumbnails, photos or backups.

## Strict acceptance status
| Gate | Result |
|---|---|
| Woo variation + ThemeComplete add-on values | PASS |
| Browser product -> Cart -> Checkout totals | PASS |
| Browser Store API created QA order, reached confirmation (second click) | **PASS** |
| Native Woo server Checkout order total and meta | PASS |
| First click succeeds reliably from active address field | **OPEN (first click no-op observed once)** |
| Real independent test merchant success/decline/webhook/provider refund | **BLOCKED: no adult-category-approved iDollmate test merchant configured** |
| Dev cleanup/readback | PASS |
| PR #3 final merge/release | OPEN, Draft |

**Next**: Instrument Checkout Block client event/initialization and first-click reaction; test only when Hostinger metrics permit. Inspect Hostinger resource faults and current Woo logs. Do not rewrite Woo internals, disable nonce checks, enable real BACS for customers or merge PR #3. Once first-click stable, obtain owner-authorized iDollmate-specific eligible payment test merchant, then execute provider sandbox payment success, decline, signed webhook verification, replay rejection, partial/full refund and cleanup.