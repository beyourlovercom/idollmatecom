# iDollmate R7 — Engineering-scope closure & R8 handoff

**Date:** 2026-10-29  
**Owner decision:** CLOSE the R7 **engineering development scope** and proceed directly to **R8 Catalog OS V1**. Do **not** perform further R7 payment-gateway tests. This is a scope closeout, **not a merge, production release, full e-commerce launch acceptance, or permission to enable payments.**

## Frozen R7 engineering deliverables

| Component | Source | Dev readback / acceptance |
|---|---|---|
| iDollmate Catalog Fields | `wordpress/plugins/idollmate-catalog/` **0.2.0** | Active Dev; product fulfillment, dispatch region/days and reference URL metadata only |
| Original Home | `wordpress/plugins/idollmate-home/` **0.2.0** | Active Dev; original responsive HTML/CSS, category/featured/guide with empty states; UAT preview page #14 `no-title` |
| Storefront UI | `wordpress/plugins/idollmate-storefront/` **0.1.0** | Active Dev; customer-side Woo styling only; does not change Woo pricing/payment/cart calculations |
| Woo native variable product + ThemeComplete product-local add-ons | QA product #15 with TPE/Silicone variations, optional head/skeleton/accessory | Actual cart A–E 5/5, coupon/quantity, order-line item metadata, tax/shipping and internal refund all tested at distinct gates |
| Guest browser first-click | `scripts/uat/r7_checkout_block_diagnostic.cjs` | Three **consecutive qualifying** independent Chrome runs **#29, #30, #31**: exactly one Place Order click, one Store API POST HTTP 200, native order confirmation, total $1,348 with synthetic $200 shipping; all orders deleted |
| CI and isolation | `.github/workflows/r7-smoke.yml`, `tests/idollmate_*_smoke.php` | PHP syntax/isolated smoke CI PASS; Dev noindex/Coming Soon/Draft restored and documented |

### Last Dev readback before R7 scope close
- `dev.idollmate.com` remains WordPress/WooCommerce Dev; active product sample **Draft**, `blog_public=0`, Woo Coming Soon **yes**, temporary shipping zones **0**.
- Synthetic BACS was disabled and QA orders **#29/#30/#31** deleted, prior independent readback verified. Zero real payment or provider refunds.
- Hostinger hPanel for the **correct** Dev domain showed Memory limit **4,096 MB**, 24h mean **3,282 MB**, memory-limit faults observed; Owner accepted intermittent Dev issues. The previous custom 1,300 MiB PSS limit was retired; actual web/CLI failures still require stop and rollback.
- Product references from **YourDoll** remain direct original-site **URL metadata ONLY**, not image files. Do not download, proxy, mirror, cache, attach to Woo media, regenerate previews, or display commercially without rights. Original content only.

## Explicit exclusions and retained risks

- R7 did **not** establish a provider-approved iDollmate payment account, card approval/denial, 3DS, provider-authenticated callback/webhook, live refund, settlement or chargeback test. These are **deferred to a separately approved payments/go-live workstream**, not conditions for starting R8.
- Occasional Dev PHP SIGKILL/137 and HTTP 503, plus non-qualifying cart session/caching tests, remain **known staging risks**. Three qualifying first-click passes do not claim zero possible future cart faults.
- No bulk real catalog was imported, no authorized production product photography acquired, no 2,000 SKU live inventory verified. No claim of SEO traffic or readiness for Google indexing.
- The main ecommerce Production domain `idollmate.com` **remains Shopify**. Woo Dev features are **not** deployed to Shopify and are not proof of its compatibility.
- Do not deploy any source to production, alter DNS, activate payments, publish customer products, merge PR #3 or promote R8 without a new explicit Owner **`pass`**.

## GitHub governance and frozen handoff

- R7 implementation branch: `feat/r7-original-catalog-v1`.
- **PR #3 MUST stay OPEN + DRAFT + UNMERGED**, preserving the engineering snapshot. It is not to be marked ready for review or merged automatically. Existing Dev installations can remain active but hidden.
- R7 engineering issue **#2 is CLOSED as completed within the Owner-approved reduced engineering scope**. Payment readiness and final launch acceptance are **not** falsely marked PASS.
- R8 development must start on a **separate feature branch** based on the frozen R7 head; use a **stacked Draft PR targeting the R7 branch**, never main, until the Owner authorizes the dependency merge chain.
- R8 is source-first / read-only Dev baseline; any installation or bulk data write to Dev must have a separate acceptance gate and evidence.

## R8 Catalog OS V1 immediate scope (22–40 engineering hours, excluding all content production)

1. **R8-A**: freeze canonical Woo taxonomy/attribute dictionary, SKU uniqueness/variant model, draft/visibility, ownership of price/stock, source and image licensing fields.
2. **R8-B**: safe import contract and CSV **preflight-only** batch validator (duplicates, invalid categories, material/size units, dates, prices, image rights, 2,000 SKU scale). Never auto-import from historical competitor snapshots.
3. **R8-C**: controlled idempotent Draft-only import with preview → explicit confirm and audit/rollback, no changes to R7 Woo test products and never publish automatically.
4. **R8-D**: scalable category/filters/SEO slug structure and buyer facets using Woo global attributes rather than duplicated taxonomies.
5. **R8-E**: original/lawfully licensed sample data browser UAT, archive evidence, optional staging rollout after approval.

**Reference:** [R7 qualifying 3/3 first-click report](R7_H_FIRST_CLICK_3PASS_2026-10-29.md), [R7 Dev acceptance E–I](R7_E_I_ACCEPTANCE_2026-10-29.md), [IA](../architecture/IDOLLMATE_IA_V1.md), [existing catalog ownership contract](../architecture/R7_CATALOG_CONTRACT_V1.md). 
