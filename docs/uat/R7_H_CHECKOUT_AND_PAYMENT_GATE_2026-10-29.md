# R7-H — Browser Checkout & Independent Payment TEST gate
Date: 2026-10-29
State: **BLOCKED FOR LIVE RETEST: Hostinger PHP resource instability; merchant authorization not available**

## 1. Verified live Dev evidence — do not conflate failures

The iDollmate Dev site uses WooCommerce 11.2.1 Checkout **Block** on WordPress page ID 10; actual content includes `woocommerce/checkout-terms-block`, `checkout-payment-block`, `checkout-actions-block` and billing/shipping blocks. The previously measured native TPE+Premium Head **$1,148**, Cart **$1,148** and Woo Checkout **$1,148** are PASS. A Dev-only offline BACS gateway and illustrative $200 US flat shipping were tested separately; the browser summary showed **$1,348** and the gateway listed correctly.

The full browser `Place Order` test has NOT passed. Two types of independent issues were observed:

1. **Browser submission did not reach an order confirmation**. Earlier attempts displayed the Checkout form but not a completed order. The earlier Store API event listener was incomplete/inconclusive, and no conclusive checkout POST request/response was saved. Do not claim a confirmed Woo Blocks defect or an unambiguous missing POST. Potential next checks: terms checkbox enabled/unchecked, shipping/billing validity, `wc/store/checkout` status and `wc/store/payment` status, `checkout_validation_before_processing` handlers, payment method registration, Store API nonce and the POST response.
2. **Hostinger account PHP pressure/503**. An isolated retest on 2026-10-29 caused PHP WP-CLI commands to be SIGKILL/exit 137; the Checkout page returned `HTTP 503 Service Unavailable: The server is temporarily busy`. Hostinger account process samples showed 71–75 user processes and approximately 3.8 GiB summed RSS, but the precise LVE/cgroup limit is NOT independently verified. A PHP outage/503 is a separate, confirmed hosting failure and blocks reliable payment testing.

Use the [Woo Checkout Block processing flow](https://developer.woocommerce.com/docs/block-development/extensible-blocks/cart-and-checkout-blocks/how-checkout-processes-an-order) as reference: client-side `before_processing` validates first, then payment listeners, then `POST /wc/store/v1/checkout`. The [Woo Store API docs](https://developer.woocommerce.com/docs/apis/store-api/nonce-tokens) require a valid nonce or Cart Token. **Never disable nonce verification** in Dev or Production as a shortcut.

## 2. Recovery incident / actual safety result

A failed WP-CLI rollback initially left tagged Dev-only test data in the database:
- `woocommerce_coming_soon=no`;
- Product #15 `publish` instead of Draft;
- `woocommerce_bacs_settings.enabled=yes` with `R7 TEST ONLY - DO NOT PAY`;
- Test US shipping zone ID 7, owner marker `IDM R7 BROWSER QA US ONLY DELETE`;
- Temporary mail-suppression mu-plugin.

Emergency recovery used the existing Hostinger MariaDB CLI **without launching WordPress/PHP**, with exact domain, product ID/slug, tagged zone ID/name, merchant method name, old settings and record presence checked before transactional updates. **No database backup and no private image materialization were performed**, consistent with Owner waiver for disposable Dev. The test-specific BACS option was removed because its original saved settings were an empty collection, test zone/method and option deleted, product #15 restored Draft, Coming Soon set to yes, mail-suppression mu-plugin and state file removed.

**Independent SQL readback PASS**:
- `siteurl=https://dev.idollmate.com`;
- `blog_public=0`;
- `woocommerce_coming_soon=yes`;
- product #15 Draft;
- BACS test settings absent;
- no remaining shipping zones;
- mail-suppression MU file absent; QA state file absent.

**Anonymous HTTP follow-up**: /shop/ and sample product URL returned 200 Coming Soon placeholder, including query-busted checks. This is the desired protected Dev state. No Production edits, no real charges, no test orders left.

The private recovery utility remains in the authorized China Mac / Hostinger user-private QA directory only; **do not run unless exact QA-tagged state is present and Owner-authorized disposable Dev is confirmed**. Do not use on Production. Avoid repeatedly starting/killing `lsphp` or restarting READY machines.

## 3. Next safe execution gates in order

**GATE 0 / Hostinger stability**:
- Inspect hPanel account CPU/RAM/entry processes and LVE/CloudLinux limit/fault data around 2026-10-29 ~03:55–04:05 UTC, and PHP worker logs; determine whether entry processes, PHP concurrency, memory allocation or upstream throttling is responsible.
- Without changing live infrastructure, establish that both PHP CLI and website remain healthy through repeated short checks before any new Test payment integrations.
- If Hostinger account continues hitting 137/503, stop and handle hosting limits/concurrency with Hostinger support or approved capacity change before browser/merchant payment attempts.

**GATE 1 / Browser Checkout Blocks**:
- Use [`scripts/uat/r7_checkout_block_diagnostic.cjs`](../../scripts/uat/r7_checkout_block_diagnostic.cjs) only in a controlled Dev UAT session with the tagged offline fixture and a host resource health PASS. It requires explicit env opt-in and an authorized local Chrome path. Test harness logs only route/status/errors/checkbox state; no real card details, credentials, Store API nonce or screenshot images.
- Check required fields, explicit Terms checkbox if present, checkout/payment data-store state, actual request/response and user-visible errors before/after Place Order.
- If no POST: investigate client validation events, payment state, button handlers, plugin conflicts. If POST 400/401: fields/nonce/auth; POST 409: amount mismatch; POST 5xx/503: server-resource/errors. If order created but no redirect: payment response and callback/redirect. **Do not mark this gate PASS without exactly one test order and matching confirmed order page**.
- Clean up BACS/zone/mail suppression, turn Coming Soon back on, return test product Draft and delete the synthetic order. Verify with independent readback, not only shell exit.

**GATE 2 / Independent merchant**:
- No iDollmate-specific TEST merchant is configured in Dev in current verified inventory. Never re-use BYL/SVAKOM accounts or credentials without separate explicit authorization and provider approval. The sales category is realistic adult dolls; payment processor category underwriting/acceptance is essential.
- Stripe currently lists sexually oriented products / lifelike sex toys as prohibited or restricted (jurisdiction-dependent); do NOT assume a Stripe test account implies acceptable production processing. See [Stripe official restricted businesses](https://stripe.com/legal/restricted-businesses) and [FAQ](https://support.stripe.com/questions/prohibited-and-restricted-business-list-faqs).
- **Candidate to evaluate, not selected or connected:** CCBill, which publishes an [official WooCommerce integration](https://ccbill.com/doc/ccbill-woocommerce-module), WordPress plugin with Checkout Blocks support, and a [FlexForms sandbox/test-user procedure](https://ccbill.com/doc/admin-portal-faq). The merchant must explicitly accept iDollmate business type, countries, refunds and shipping model, and provide a separate qualified test subaccount. CCBill's FlexForms mode supports Approval, Failure etc. webhooks; Advanced token-based flow has different webhook requirements. Do not install, connect or enable a gateway before an authorized iDollmate merchant is identified and site resource health stabilized.

**GATE 3 / Real provider sandbox QA** (only after prior passes):
- Process approved and declined test transactions with provider-sanctioned test data; verify order status/amount once, retry/idempotency (no duplicate capture).
- Verify provider-signed webhook/event, replay rejection, mismatched amount and non-verified sender rejection, successful update of correct order.
- Provider-backed partial/full refund TEST; ensure Woo financial state and processor state correspond; verify no real transfer, no emails, no shipment.
- Restore Dev hidden state and hand back a specific successful TEST merchant/account readback and webhook evidence, without disclosing secrets.
- Only then consider PR #3 ready for final review, with a separate explicit Owner approval for any production release.

## 4. Existing successful gates remain valid
- Native Woo variations and ThemeComplete options A–E cart subtotal checks 5/5 PASS.
- Product-local option metadata survived Woo Checkout **server-side** `create_order()` with identical totals.
- Woo internal partial refund $149 on $1,148 synthetic order PASS (not a provider refund).
- Real browser TPE+head Cart and Checkout price parity PASS; Home 0.2 and Storefront UI 0.1 active Dev.
- CI PHP isolated smoke passed; PR #3 still Draft and unmerged.

**Decision**: Do not continue payment charges or create merchant-specific credentials during an unstable host period. No production change.

## 5. Controlled reattempt / 2026-10-29 resource gate
- After the direct-database rollback, an independent resource sample briefly improved to **53 account processes / ~2,731 MiB summed RSS**; native Node syntax and GitHub Actions isolated CI checks passed.
- A fail-closed controlled browser diagnostic runner was prepared with a preflight threshold of **3,200 MiB summed RSS** (a conservative QA heuristic, **not a verified Hostinger LVE quota**). If the gate passed, it would use only the temporary Dev offline QA fixture and run a Chrome Checkout Block state/Store API trace; any PHP cleanup failure would invoke the strict Dev-only direct DB recovery. No billing provider was connected.
- **The runner did NOT execute a new checkout**: before any fixture setup it measured **66 processes / 3,744 MiB RSS** and exited **13** at the resource preflight. Therefore zero new customer order, webhook, refund or merchant connection resulted from this attempt. Subsequent read-only sample was 63 processes / 3,428 MiB.
- Server `/proc/self/cgroup` confirmed an **LVE memory cgroup path** (`/lvp2009124026/lve686797605`), but its memory limit/fault counter was not readable from the user's SSH namespace. So the **CloudLinux LVE usage context is confirmed, but the exact quota and fault metrics are not**. Obtain hPanel resource/fault metrics or Hostinger support evidence before attributing a precise root cause.
- No Hostinger PHP workers, Chrome windows or READY machines were restarted. The separate Dev SQL rollback was already independently verified. **Do not run another Checkout browser transaction until resource gate and provider gating are satisfied.**
