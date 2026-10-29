# R7-H — First-click Checkout Block acceptance: 3/3 consecutive PASS

**Test date:** 2026-10-29  
**Scope:** `dev.idollmate.com` only; WooCommerce Checkout Block, ThemeComplete product-local add-ons, temporary offline BACS and synthetic US-only $200 shipping. This does **not** test a real merchant or gateway payment.

## Hostinger limit and test policy

The Owner provided a screenshot of the Hostinger hPanel **Resources Usage** view for **dev.idollmate.com**, confirming:
- Host plan Memory **Available / limit: 4,096 MB**.
- Memory 24h average **3,282 MB**.
- Chart point at 10-29 11:40 reached **4,096 MB** and displayed **511 faults**.
- CPU average **14%**, available **100%**.
- HPanel memory *faults are real*, but **the precise fault classification and PHP Worker / NPROC ceilings were not shown**. Do not infer those from the screenshot.
- Owner explicitly accepted intermittent **Dev** memory-limit risks and authorized continuation, not removal of crash detection, rollback, checkout validation, or Production safeguards.

The China Mac Python Dev-only QA orchestrator was revised to stop treating the previous **PSS 1,300 MiB / 65-process** heuristic as a real Hostinger quota. It continues to sample resource usage, fail on actual HTTP/PHP/browser errors and perform unconditional `try/finally` cleanup. If WP cleanup cannot run, it attempts exact-match MariaDB recovery and independent readback. This private server-access orchestration remains outside the repo and is **not** a Production workflow.

## Three consecutive qualifying independent Chrome checkout runs

All three started from a fresh Chrome browser context, loaded temporary hidden QA product #15, selected TPE + Premium head and completed an independent real shopper checkout with exactly one normal `Place Order` click.

| QA order | Product & upgrades | Shipping | Cart | First click | Store API | Confirmation | Cleanup |
|---|---:|---:|---:|---|---|---|---|
| #29 | $1,148 | $200 | $1,348 | PASS | 1 POST, HTTP 200 | `/checkout/order-received/29/` | order deleted |
| #30 | $1,148 | $200 | $1,348 | PASS | 1 POST, HTTP 200 | `/checkout/order-received/30/` | order deleted |
| #31 | $1,148 | $200 | $1,348 | PASS | 1 POST, HTTP 200 | `/checkout/order-received/31/` | order deleted |

For each qualifying run:
- Woo Store API `GET /wc/store/v1/cart` returned HTTP 200 and **1** item after Add to Cart; native Woo session cookies were created; independent Cart page showed exact product, **Premium head** metadata and subtotal **$1,148**.
- Checkout Cart Store `total_items=114800`, `total_shipping=20000`, `total_price=134800` (USD cents); address required fields valid, checkout validation errors empty, `bacs` active.
- Woo Checkout Block `Place Order` button was enabled and wired to a React `onClick` handler when clicked. The script waited for pending shipping/address UI changes to settle; it never programmatically dispatched checkout or clicked a second time.
- **Exactly one** `POST /wp-json/wc/store/v1/checkout` returned **HTTP 200**; browser navigated to native Woo order-received confirmation.
- After each test, temporary BACS enabled/zone/product publish/mail-suppression configuration was reverted and the test order deleted. Independent database readback confirmed: `blog_public=0`, Coming Soon=yes, product #15 Draft, zero shipping zones, no QA BACS option enabled, temporary MU/state files absent.

## Earlier failures and limitations

Earlier runs were blocked for **distinct reasons**: PHP CLI exit 137 / occasional HTTP 503 from Hostinger resource pressure, conservative test-only PSS gate, a too-strict price DOM selector, and a browser run in which a native Add to Cart submission left the Cart empty. These do not negate the three qualifying successful runs but **do not prove permanent cart-session reliability under production traffic**. Cart persistence was added as an explicit gate and passed in all 3 recent qualifying sessions.

Observed transient Woo Checkout state during shipping address changes: button temporarily disabled, focus on BODY, then enabled. Repeated shopper testing now waits for stable Cart Store amount, visible Checkout amount, React handler and button state before the **one** real click. No Woo core/theme/product/payment calculation code changed. The exact root cause of *premature customer clicks* has not been proven. We do **not** claim every click made while disabled has been fixed. Appropriate readiness guidance and visual customer UAT remain relevant.

### Gate determination

**R7-H1 browser single-click on a ready Checkout Block: PASS (3/3 consecutive).**  
**R7-H2 iDollmate-specific, category-approved merchant and sandbox: NOT AVAILABLE / NOT CONNECTED.**  
**R7-H3 provider-backed approved/declined, authenticated callback/webhook, idempotency/replay, partial/full refunds: NOT TESTED.**

BACS is an *offline test fixture*, not an actual card processing merchant; no charge, payout, refund, provider callback or production payment occurred. No images were downloaded/mirrored. **PR #3 remains Draft / unmerged** and Production Shopify site remains untouched.

## Next steps (blocked on external merchant authorization)

1. Obtain iDollmate-specific, explicitly underwritten processor (e.g. CCBill candidate) approved for realistic adult dolls / physical goods, USD variable-price one-time orders around $1,000 plus customer-paid ~$200 international shipping, allowed countries and refund model.
2. Provider sandbox/subaccount credentials must be entered through secure merchant/Dev settings, **not** chat or GitHub, and provider setup must distinguish CCBill hosted FlexForms webhooks from Advanced token integration.
3. Only after account identity/category authorization verified, install approved provider plugin to Dev **disabled first**, configure isolated sandbox and verify success, decline, callbacks and authenticity/replay, provider-origin partial/full refund. Do not claim H3 PASS on the prior Woo internal partial refund.
4. Separate Owner `pass` required for PR merge or Production release.

## Evidence links

- [Browser test instrumentation](../../scripts/uat/r7_checkout_block_diagnostic.cjs)
- [Original real browser order #25 and first-click investigation](R7_H_BROWSER_ORDER_25_PROOF_2026-10-29.md)
- [Hostinger & adult-category merchant approval gates](R7_H_RESOURCE_AND_MERCHANT_APPROVAL_GATES_2026-10-29.md)
- [CCBill official WooCommerce integration](https://ccbill.com/doc/ccbill-woocommerce-module)
- [CCBill sandbox transactions](https://ccbill.com/doc/admin-portal-faq)
