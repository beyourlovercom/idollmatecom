# R7 · TM Extra Product Options / WooCommerce checkout acceptance
Date: 2026-10-28
Status: **PENDING ON-SITE UAT — NOT A PASS**

## Environment
- Only `https://dev.idollmate.com`. Production `idollmate.com`/Shopify is untouched.
- WordPress 7.1.3, WooCommerce 11.2.1, Hostinger AI Theme 2.1.15, ThemeComplete Extra Product Options 7.6.2, Catalog Fields 0.2.0, Home 0.1.0.
- Dev `blog_public=0`; both sample products remain **Draft**, inaccessible to public visitors; current payment gateways are disabled.
- **Host block:** multiple WP-CLI and bare `php -r` processes returned SIGKILL/exit 137. On Hostinger 2026-10-28, observed account 75 processes and summed RSS ~4,061 MiB; this suggests but does not prove account-level resource pressure. The shared host has substantial global RAM; do not confuse it with account quota. Do not restart READY desktops, kill unknown PHP workers, bypass Cloudflare, switch themes, or buy hosting.
- Official field/pricing docs: https://themecomplete.com/documentation/woocommerce-tc-extra-product-options/
- Need a normal Hostinger PHP process health check before **any** new write/configuration change.

## Test fixtures (NOT actual iDollmate selling prices)
Existing Woo variable QA product **#15**, SKU `IDM-R7-VARIANT-QA-20261028`:
- TPE variant: $999.00
- Silicone variant: $1,099.00
- Product parent, variants and reference image fields are Dev-only; no competitor images.
Proposed **temporary product-local** TM fields, using Builder mode (do not create site-wide Global Forms or real catalog prices):
- Optional upgraded head: fixed **+$149.00**
- Optional skeleton: fixed **+$59.00**
- Optional accessory: fixed **+$35.00**
- All are test-only and should be removed or disabled after signoff. No image replacement, image upload or media import.
- Prefer simple fixed-price checkboxes over advanced formula in this first UAT; do not set option prices to a second Woo product variation.

## Expected tax-exclusive, shipping-exclusive arithmetic
| Test | Woo base | TM selected upgrades | Qty | Expected product/cart subtotal |
|---|---:|---|---:|---:|
| A | TPE $999 | none | 1 | **$999** |
| B | TPE $999 | head $149 | 1 | **$1,148** |
| C | TPE $999 | head $149 + skeleton $59 + accessory $35 | 1 | **$1,242** |
| D | Silicone $1,099 | head $149 + skeleton $59 + accessory $35 | 1 | **$1,342** |
| E | TPE $999 | head $149 | 2 | **$2,296** |

**Important:** These are not live plugin measurements, merely the expected acceptance values. Tax and shipping must be verified separately in WooCommerce with a configured test location; the plugin's 'Set to Fee' price mode must not be confused with a normal per-item fixed add-on. Test cart reload, variation switching, quantities and coupon behavior.

## Test sequence / acceptance criteria
1. Recheck PHP CLI can execute a minimal `php -r` without SIGKILL; no extra writes until this recovers.
2. In the Woo product editor for draft test #15, inspect whether the **ThemeComplete Extra Product Options** data panel is available for classic editor; create only 3 temporary product-specific fixed fee-free checkboxes after health recovers.
3. For an end-to-end cart test, ensure access is restricted to a permitted tester. Only briefly enable a *hidden/catalog-invisible* sample with deliberate UAT rollback after testing. Do not make it publicly discoverable or enable a real payment method.
4. In a controlled test browser, verify all A–E values, with no double counting between Woo variations and TM. Verify UI total, request, server cart, and checkout review are equal for identical selections.
5. Confirm no cross-session leakage; repeated refresh does not duplicate options; removing an item clears options and price.
6. Verify shipping class/zone rate, taxes, validation errors, cart coupons and discount interaction in test configuration. Do not imply live shipping or tax values.
7. Gateway test requires a separately verified TEST account and approved checkout test configuration. As of this checkpoint gateways BACS/Cheque/COD are disabled. **Do not create real charges, emails, fulfillments or refunds.**
8. Dev noindex on all public UAT pages and no local image backups/WordPress media attachments throughout.
9. Document outcomes, test order IDs and exact plugin config; clean up or return test product to Draft, disable test add-on forms, and keep production untouched.

## Runtime safety & evidence
- `python3 scripts/r7_dev_http_smoke.py` performs limited, read-only public HTML/header checks; does not download images or submit checkout.
- If any PHP CLI is SIGKILL 137, stop writing and record as BLOCKED until account-level resource health is restored.
- WP native variations (2 SKUs) persistence was previously proven in Dev; this does **not** substitute for TM checkout acceptance.
- Keep PR #3 Draft/open until tests pass. The user waived DB backup requirement for disposable **Dev only**; no waiver for Production.
