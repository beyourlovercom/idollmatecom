# R7-H Owner-approved 4 GB Hostinger UAT and offline Checkout gate — 2026-10-29

**Scope**: disposable Woo Dev at `dev.idollmate.com` only. iDollmate Shopify Production untouched. Adult competitor image data remains original URL references only, with no asset downloads/imports/backups.

## 1. Actual Hostinger hPanel evidence replaces earlier proxy thresholds

Owner supplied a screenshot from **the exact site `dev.idollmate.com`** Resources Usage page, 24 hours selected, on 2026-10-29:
- **Memory available / limit 4,096 MB**; average utilization **3,282 MB**; a tooltip at 10-29 11:40 displays **Memory 4,096 MB / 511 faults**. This screenshot does **not** establish that all 511 faults occurred exclusively at 11:40; it shows the visible tooltip's fault count, and the 24h graph's fault markers.
- **CPU available 100%**, 24h average **14%**; CPU usage curve remained substantially below its limit in the visible chart.
- PHP Workers / Max processes usage charts are below the screenshot viewport and therefore **not independently observed**.
- The owner explicitly accepts prior occasional Dev memory-limit events and authorizes continuing; **do not pause the entire R7 workflow on occasional 4 GB memory faults**.
- The old PSS 1,300 MiB and 65-process thresholds were **arbitrary QA heuristics** and have been removed from the China Mac disposable Dev orchestrator; per-process PSS and count remain observations. Actual PHP SIGKILL/137, HTTP 503, a broken checkout, or failed cleanup are still hard errors. **Never apply this relaxation to Production**.

## 2. Genuine one-click offline Woo Checkout — qualified PASS, with caveats

Actual Playwright Chromium-based buyer journey uses a **fresh browser/context each run**, original Woo product #15 TPE variation 16, native ThemeComplete Premium Head option $149, product subtotal $1,148, temporary US shipping $200, checkout **$1,348**. Temporarily enables *offline* `R7 TEST ONLY - DO NOT PAY` BACS, hidden QA product, Coming Soon=no and blocked emails. The harness verifies the actual Woo cart, variation/option, session persistence, browser mandatory fields, Cart Store amount, active BACS, enabled React checkout button and settled visible total. It allows exactly **one** `Place Order` click, no direct store dispatch and no synthetic payment gateway calls.

**Three independently completed, qualifying, first-click successes:**

| Order ID | Actual Chrome confirmation | Checkout POST | Result |
|---|---|---|---|
| #26 | `/checkout/order-received/26/` | exactly one; HTTP 200 | **PASS, deleted** |
| #27 | `/checkout/order-received/27/` | exactly one; HTTP 200 | **PASS, deleted** |
| #28 | `/checkout/order-received/28/` | exactly one; HTTP 200 | **PASS, deleted** |

Each confirmation presented correct **$1,348** total. All temporary test orders were deleted by the disposable Dev cleanup, after Woo's Store API order-processing path ran. The three successful attempts represent **3/3 qualified first-click submissions once Cart and Checkout were fully ready**, not 3/3 overall visitor journeys. The earlier browser order #25 also succeeded *on its second click*, and was deleted.

**Important independent setup failures preserved, not hidden:**
- A separate attempt returned **empty Woo Cart after Add to Cart**, with no WooCommerce session/item cookies, and did not reach Checkout. Test was aborted safely without creating an order.
- Another premature test script waited for a specific ThemeComplete final-total text that was not yet present and timed out **before Add to Cart**; this was a brittle test condition, not a verified customer defect.
- A subsequent controlled test showed Woo Store API **1 item**, actual `woocommerce_items_in_cart`, `woocommerce_cart_hash` and `wp_woocommerce_session_...` cookies present, and Cart persisted the variation/option. Initial Checkout button remained `disabled=true` during customer/shipping updates and eventually settled; an early script erroneously aborted while still disabled.
- The now corrected script waits for Woo product variation and checked option, verifies Store API cart item count and cookies **without logging cookie values**, waits for native checkout button to become enabled, stable $1,348 visible total and valid fields, then clicks once.

**Gate result:** the **single-click Woo Store API order-creation function is technically validated** on **three fully ready buyer journeys**. Still retain a separate **Cart readiness / occasional transient Add to Cart issue** before declaring flawless end-to-end customer reliability. Do not claim that the customer payment provider has been tested. Not a Production launch signoff.

## 3. Runtime and cleanup contract

Private China Mac QA orchestrator: `~/AI/idollmate/r7-release/r7-safe-checkout-orchestrator.py`. It uses try/finally to restore the disposable Dev fixture; if WP-CLI is unavailable because of host PHP memory faults, it has an **exact-record-guarded MariaDB-only fallback** for the isolated QA settings. On every completed test, independent DB probe confirmed:
- `blog_public=0`, Coming Soon=yes, product #15 Draft;
- synthetic BACS settings OFF/removed; 0 shipping zones; `woocommerce_calc_taxes=no`;
- QA state file / temporary email suppressor absent;
- test orders #26, #27 and #28 reported deleted by cleanup (schedule independent final WC order readback to reconfirm).
No real payments, legitimate customer emails, shipments, downloads, copies or Production writes.

## 4. iDollmate independent payment merchant screening

**Do not connect an unrelated Stripe test context**; current connected Stripe account is held in a different company name and is not verified for iDollmate adult dolls.

**CCBill**:
- Official plugin `CCBill Payment Gateway for WooCommerce` supports Checkout Blocks, FlexForms hosted payment pages and Advanced token mode; the account must enable Dynamic Pricing and provide a **one-time/tangible-goods-approved subaccount**. [Official plugin](https://wordpress.org/plugins/woocommerce-payment-gateway-ccbill/) and [CCBill setup](https://ccbill.com/doc/ccbill-woocommerce-module).
- CCBill explicitly lists adult toy ecommerce among merchant models that can apply. [Adult merchant page](https://ccbill.com/industries/adult-business).
- **CRITICAL:** CCBill's published **Tangible Goods Policies** require shipment within **three business days**, otherwise refund, and prohibit accepting orders not ship-ready within that interval; physical goods must use a separate subaccount and disclose tracking, support details and a return/refund policy. [CCBill terms, Exhibit B](https://ccbill.com/cs/client/policies/ccbill/terms_and_conditions_cc.html). iDollmate's first-year factory-direct fulfillment may involve longer made-to-order lead times. **Need written processor-specific approval or a contractually compliant fulfillment model, not assumptions.**
- Merchant registration and supported sandbox testing require approved account/subaccount plus registered **test user email and IP**. [Official CCBill test user](https://ccbill.com/doc/admin-portal-faq).
- FlexForms uses configured webhook URLs; Advanced token-based mode may use other confirmation mechanisms; do not invent HMAC support or claim webhook signatures without official selected-mode confirmation.

**Segpay**:
- Official WooCommerce integration requires an approved Segpay merchant account, a **dynamic-price ceiling** authorized by the account rep, plugin zip, access keys, and postback setup. [Segpay WooCommerce integration](https://gethelp.segpay.com/docs/Content/MPFeatures/WooCommerceIntegration.htm).
- Approved merchant maximum order total must cover at least **$1,348** for this test and expected upgrade-rich orders possibly higher; obtain explicit ceiling.
- Test refunds have contradictory descriptions in older Segpay API materials versus the current Woo guide; confirm supported sandbox refund and void scenarios with Segpay before promising end-to-end provider refund tests.

**Pending written merchant underwriting**: adult *realistic silicone/TPE dolls*, physical merchandise, custom configurations, direct-from-factory dropship, expected actual production/dispatch times, USD ~1000+ merchandise, shipping paid by buyer (~$200), approved legal merchant and jurisdictions, accurate return/refund and customer support page, maximum order limit, webhook/event authentication and replay prevention, partial/full provider refund test support.

## 5. R7 remaining gates

- **R7-H1:** Woo first-click-once checkout order creation **PASS for 3 qualified runs**; intermittent Cart initialization readiness still monitor and fix if independently reproduced.
- **R7-H2:** independent approved merchant sandbox **BLOCKED — no approved iDollmate-only account yet**.
- **R7-H3:** merchant-backed approved/declined, signed or provider-authenticated notifications and replay checks, partial/full refund **NOT STARTED**.
- **R7-J:** still **OPEN**, PR #3 Draft/unmerged; Production rollout separate Owner PASS.

Owner has explicitly authorized continuing ordinary Dev UAT despite occasional memory faults; **this is not authorization to send real payments, skip merchant approval, deploy Production or violate source-image copyright constraints**.
