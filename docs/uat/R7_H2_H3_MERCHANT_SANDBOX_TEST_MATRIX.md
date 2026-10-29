# R7-H2/H3 — Independent iDollmate payment merchant integration gate

**Status: account/underwriting BLOCKED; technical test plan READY.**
Prepared 2026-10-29. Do NOT connect an unrelated Stripe, BYL or SVAKOM account. No merchant credentials, real card data or live customer records belong in GitHub.

## Provider option: CCBill, under evaluation ONLY

Official sources:
- [CCBill WooCommerce module setup](https://ccbill.com/doc/ccbill-woocommerce-module)
- [Official CCBill WooCommerce WP plugin](https://wordpress.org/plugins/woocommerce-payment-gateway-ccbill/)
- [CCBill test transaction user](https://ccbill.com/doc/admin-portal-faq)
- [CCBill FlexForms Sandbox](https://ccbill.com/doc/managing-flexforms-payment-flows)
- [Refund DataLink API](https://ccbill.com/doc/ccbill-api-guide)

As of 2026-10-29, WordPress.org lists the official CCBill plugin **3.1.1**. Plugin 2.0 introduced Woo Blocks Checkout support; 3.1 added an **IPN Security Token** option for FlexForms, improved post-purchase redirects and other security changes. 3.1.1 has a renewal fix. WordPress.org explicitly notes 3.0+ breaking settings changes, so validate from a clean disabled install in Dev and don't auto-upgrade/install in Production.

**No live CCBill merchant subaccount is verified for this project.** Provider's ability to underwrite this exact business cannot be inferred from a generic "adult" marketing claim.

### Merchant approval request must explicitly include

| Area | Information requiring merchant approval |
|---|---|
| Trading name/domain | Independent **iDollmate** brand; `dev.idollmate.com` is a non-public QA site; Production `idollmate.com` still Shopify |
| Physical goods | Realistic humanlike adult dolls / sexual merchandise; no digital subscriptions by default |
| Pricing | Variable configuration, about US$1,000 merchandise plus customer-paid shipping ~US$200; typical test case $999+$149+$200 = $1,348 |
| Fulfillment | Physical one-piece factory drop-ship to end customer, international destinations, carrier tracking, realistic delivery times |
| Countries/currencies | Explicit list of merchant-approved buyer countries and supported USD settlement; no assumed geographic coverage |
| Refunds/chargebacks | Cancellation before dispatch, partial/full provider-backed refund eligibility, carrier damage, chargeback policy |
| Credentials | Independent legally approved merchant entity, client account + dedicated TEST subaccount; safely stored secret, never committed |
| Checkout mode | **FlexForms hosted** vs **Advanced token-based**: choose based on authorization, Woo Blocks support, and security requirements |

### Provider specifics not to conflate

- FlexForms hosted mode normally redirects to the processor's payment form and needs subaccount settings, approval/denial return URLs, Dynamic Pricing and selected payment notifications. Sandbox forms generally require an active Admin Portal session and a configured test-user email/IP.
- Advanced mode uses tokenized on-site checkout, requires merchant support credentials/features; its callback requirements differ. Do **not** configure FlexForms webhook URLs blindly for Advanced mode.
- CCBill's FlexForms plugin 3.1 includes an IPN Security Token setting, but **test its actual validation behavior** for selected webhook events; do not claim HMAC signing without confirming actual format/algorithm and compatible CCBill settings.
- CCBill DataLink `refundTransaction` offers partial/full operations subject to merchant account privileges; Woo internal refund accounting by itself is not a verified provider refund. Some transaction types may not support partial refunds—obtain an approved test transaction type before acceptance.

### R7-H3 exact QA matrix

| ID | Actual TEST case | PASS criteria |
|---|---|---|
| P0 | Merchant subaccount authenticity and adult-category permission | Provider identifies iDollmate merchant and explicit approval for realistic adult dolls; TEST mode unmistakable |
| P1 | Shopper original Woo variable product + TM add-on | End-to-end cart and shipping amount exactly matches processor payment amount (e.g. $1,348) |
| P2 | Approved test payment | Exactly one provider transaction; one Woo order, correct status/currency/total, no duplicate capture |
| P3 | Declined/failed/abandoned payment | Clear customer message; Woo not marked paid; no shipping/fulfillment |
| P4 | Provider callback/return | Correct order ID, amount, currency, merchant/subaccount; only authorized callback updates paid state |
| P5 | Replayed/forged callback | Same event idempotent; unrecognized token/mismatched amount/order cannot confirm payment |
| P6 | Network timeout/retry | Confirm provider transaction status before retry; never double-charge |
| P7 | Provider-backed partial refund (e.g. $149) | Provider TEST refund record and Woo refund state both match; confirmation read back from processor |
| P8 | Provider-backed full refund | Merchant TEST refund record and Woo order state match; no duplication/negative balances |
| P9 | Isolation/rollback | Test emails suppressed; temporary orders removed; Coming Soon=yes, product #15 Draft, no provider secrets in logs/GitHub, provider integration disabled when not needed |
| P10 | PR / Production release | PR #3 remains Draft until Owner explicitly approves release; Production Shopify/DNS untouched |

**Non-acceptance:** Mock webhook posts, a Woo `wc_create_refund()` record, offline BACS orders, or an account not underwritten for iDollmate are not substitutes for real provider TEST success/refund events.

### Engineering gates

1. H1 checkout browser ready-first-click **PASS 3/3 consecutive** (#29, #30, #31); see [evidence](R7_H_FIRST_CLICK_3PASS_2026-10-29.md). Intermittent Cart session/cache readiness failures remain separate caveats.
2. H2 merchant must be **explicitly approved and separately owned for iDollmate**; no plugin activation or credential configuration until a suitable provider/TEST subaccount is identified.
3. H3 provider-backed QA run with provider's sanctioned test transactions and verified return/webhook/refund records.
4. H4 merge, sign, and production publication **requires separate Owner pass**.

