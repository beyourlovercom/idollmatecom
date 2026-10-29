# R7-H authoritative resource and independent payment provider gates (2026-10-29)

**Scope**: iDollmate disposable Woo Dev on `https://dev.idollmate.com` ONLY. Production `idollmate.com` Shopify must remain untouched. Never publish or copy third-party product images. User waived disposable-Dev DB backup only, not Production or payments safeguards.

## Gate H0 — Hostinger actual account resource limits: BLOCKED ON hPanel MATCH

Previous tests encountered intermittent PHP CLI exit 137 / HTTP 503. CLI inspection established an LVE cgroup path, but **did not expose the account's real Memory, PHP Workers, Max processes, CPU limits or LVE fault counters**. Earlier summed-RSS 3.2 GiB / 65 processes and later PSS 1.3 GiB are *engineering test stop rules*, NOT official Hostinger plan limits or proof of the actual cause.

Official Hostinger Help: [Resource Usage](https://www.hostinger.com/support/2436138-how-to-check-resource-usage-in-hostinger/) and [Hosting plan parameters](https://www.hostinger.com/support/6976044-parameters-and-limits-of-hosting-plans-in-hostinger/).

The account-holder must open **Websites → Dashboard for a site in the SAME hosting account as `dev.idollmate.com` → Hosting plan → Resources Usage** and inspect the actual hosting plan ID and **6h and 24h** graphs at UTC 2026-10-29 approx 02:00–07:00 (especially PHP SIGKILL/503 observations). Capture or read *actual* red limit lines and usage peaks for CPU, Memory, PHP Workers, Max processes, and any host-plan limit/fault warnings. Hostinger reports aggregate usage across all sites in the hosting plan, not per site; graph timestamps are UTC, updates about every 10–15 minutes. Do not map another Hosting Plan to iDollmate by shared browser/login or guessed account name.

**Available UI read-back**: a Hostinger `Websites` tab exists on the authorized `007windows` machine. It displays a `Cloud Startup Hosting` group, **but its visible listed website is not iDollmate**. Therefore **this is not yet evidence of iDollmate's Hosting Plan**. No logged-in hPanel tab for the correct iDollmate site was verified. Do not apply generic public Cloud Startup limits to `u686797605` without proving account match.

Once the right plan metrics are available, classify primary fault (PMEM, process count, PHP worker saturation, CPU or IO), gather peak usage times, test one HTTP/CLI operation at a time, then tune plan-specific safe QA threshold. Do not kill live workers, restart READY devices, force an upgrade, or activate a payment provider to diagnose hosting.

## Gate H1 — first-Click Checkout Block: OPEN

Past verified Dev achievements:
- Woo variation/ThemeComplete EPO real Cart A–E **5/5 PASS**, US synthetic tax/shipping tests pass; native Woo server Checkout creation pass.
- Real customer headless Chrome test (order #25, synthetic offline BACS): **second click** submitted `POST /wp-json/wc/store/v1/checkout` HTTP **200**, redirected `/checkout/order-received/25/`; Woo debug log confirmed correct order amount **$1,348.00** and Woo workflow, order #25 permanently deleted.
- **First click** previously no-op; `wc/store/checkout` idle, `wc/store/validation` no reported errors; Cart Store amount correct. Later browser run showed **Place Order temporarily disabled, focus lost to BODY, visible total temporarily absent** during asynchronous shipping/customer updates.
- `scripts/uat/r7_checkout_block_diagnostic.cjs` now explicitly verifies cart persistence; waits for button React handler + enabled state, valid fields, selected BACS, current Store Cart total + visible total stable; permits **one normal first click** only and requires exactly one `POST /wp-json/wc/store/v1/checkout` with HTTP 200 plus order confirmation URL. No second auto-click, nonce bypass or forced store dispatch.
- Last source CI and syntax checks passed, but **the new first-click acceptance remains UNVERIFIED** because conservative resource preflights often stop Dev QA. This is not a shipped site fix or a PASS.

Acceptance: run 3 independent Dev sandbox shopper attempts with fresh sessions and genuine on-page interactions only *after* H0 is verified, each must succeed with exactly one Place Order click, no failed network response or duplicate order. If first click still no-ops, diagnose Woo Blocks button focus/disabled transition with official Woo Validation Store, then reproduce with the original Storefront UI CSS module temporarily disabled to isolate CSS vs Woo core/TM/host, and restore afterwards. Do not rework production.

## Gate H2 — verified, independent adult-category merchant: BLOCKED

Current evidence: Dev only has BACS/Cheque/COD (disabled outside temporary QA). No independent adult-category-approved iDollmate payment TEST account was found in the connected mailbox search, and the currently connected Stripe test session identifies a different company, not verified iDollmate category approval; **do not reuse it or call it eligible**.

Official CCBill may be evaluated as a candidate but is **not connected or approved**:
- [CCBill official WooCommerce plugin](https://wordpress.org/plugins/woocommerce-payment-gateway-ccbill/) supports Checkout Blocks; current version 3.x may have upgrade/compatibility changes, so preflight against Woo 11.2.1.
- [CCBill WooCommerce setup](https://ccbill.com/doc/ccbill-woocommerce-module) requires an **active merchant account** and explicitly distinguishes **FlexForms hosted integration**, with **approval/denial callbacks and per-subaccount webhooks**, from **Advanced token-based integration**, which does **not require the same webhooks**.
- [CCBill test user](https://ccbill.com/doc/admin-portal-faq) requires a configured merchant subaccount/test-user email + IP and supported test data. Sandbox payment forms are login-scoped, not public customer forms.
- [CCBill Data Link refund API](https://ccbill.com/doc/ccbill-api-guide) supports partial and full refunds subject to account permission; do not assume Woo's own refund button makes a provider-backed refund. Verify whether selected plugin supports refund propagation or needs an approved API/manual merchant portal test.
- **Webhooks security requirement:** determine CCBill's supported authenticity mechanism and replay/idempotency controls in the chosen integration, rather than claiming all CCBill webhooks have HMAC signatures. Require verified source, account/subaccount, order mapping, amount/currency, unique transaction ID, duplicate rejection, and safe response for bad data. If provider does not offer cryptographic signed delivery, document the accepted supported alternative and explicit security signoff rather than falsely reporting “signature verified.”

Merchant prerequisites: written confirmation that `iDollmate` may sell **realistic adult dolls / tangible sexual merchandise**, supported legal entity/countries, one-time variable-price USD goods around US$1,000+, customer-paid freight ~US$200, refund policy, approved website category, and TEST account/subaccount access. No live data or funds, no brand cross-merchant connection.

## Gate H3 — provider-backed TEST acceptance (not run)

For a verified approved iDollmate merchant: 1) test payment approved → correct Woo order status, idempotent capture; 2) failed/declined/3DS or provider-specific denial flow → no success/duplicate charge; 3) verified provider callback/webhook with authenticity & replay/duplicate rejection; 4) provider-backed partial and full refunds matching Woo financial status; 5) all test orders and settings cleaned; restore Dev `woocommerce_coming_soon=yes`, sample product #15 Draft, `blog_public=0`, no unexpected image files, refund & gateway state read back.

PR #3 must stay **Draft/unmerged** until H0, H1, H2, H3 and final Owner signoff. Any Production write requires separate explicit Owner `pass`.
