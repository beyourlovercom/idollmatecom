# iDollmate Engineering Rules

## Project
- Public source repository: `beyourlovercom/idollmatecom`, default branch `main`.
- Dev environment: `https://dev.idollmate.com`, Hostinger Cloud Startup, WordPress + WooCommerce.
- The existing `idollmate.com` Shopify DNS and storefront are out of scope. Never modify them as part of Dev work.
- Reuse Andy Core / Andy Commerce via their canonical repositories where applicable. Do not duplicate shared plugin code.
- WordPress core, private media, real orders, database dumps, tokens and credentials must never be committed.

## Approval gates
- Use Dev-first deployment and explicit UAT. Production writes and merges/release affecting Production require Owner `pass`.
- Before any destructive operation, verify exact domain, current files and data, backup and rollback feasibility.
- No new hosting charges, hosting-plan upgrades, mass migration, or restart of READY devices.
- Backoffice/admin interfaces of Andy-series plugins are Desktop-only; customer-facing storefront remains mobile-responsive.

## Reference and content policy
- **YourDoll source ownership (Owner statement 2026-10-31):** the Owner explicitly confirms `yourdoll.com` is their own website and authorizes a **private, high-fidelity local backup/reconstruction** under `duplicate-site-skills`. This replaces the older assumption that the site is an unrelated competitor. Preserve the source identity as source evidence; keep original backup media/HTML/DB in private storage, never in this public GitHub repository.
- Public search indexing, public reuse of YourDoll brand/content on the separate iDollmate WordPress domain, production replacement, and use of source-side admin/DB data each remain separate actions; do not infer them from local backup permission.
- A public URL inventory is permitted; only archive content accessible through ordinary published endpoints when access and rights permit.
- Respect robots and terms, crawl conservatively; stop on access-denied, login, CAPTCHA, Cloudflare challenge or rate limits.
- Raw third-party HTML, original photos, CSS/JS, brand assets and copy must **not** be published in this public repository or pushed onto iDollmate by default.
- Derive original theme components, content structure and UX specifications from permitted reference observations.

## Release requirements
- Keep Dev non-indexed and use TLS. Configure test-only payments and shipping before transactional UAT.
- Verify WooCommerce activation, page routes, theme, responsive storefront, performance and security before release.
- Record deploy versions and rollback steps in the repository.

## Temporary image-source rule — Owner approved 2026-10-28
- **Scope: iDollmate Dev / R7 and reference research only.** For any illustrative image reference used during current development, retain the exact **original `https://www.yourdoll.com/...` or `https://yourdoll.com/...` image URL**. Do not generate local duplicates, backup copies, image-download mirrors, resized/re-encoded copies, WordPress Media Library imports, proxy/caching endpoints, or commit original images to GitHub. Store image URLs as **metadata only**.
- Third-party image URLs may return 403, expire or block hotlinking. **Never bypass origin restrictions** and do not replace them with unauthorized mirrors; use image-free/neutral empty states when a picture cannot load.
- URL-linking alone does **not** grant rights to reproduce or commercially display third-party images. Do not publish competitors' image content to an externally visible storefront without permission. Mark research references as private/admin-only until licensing/permission and an accessible origin are verified. Production image policy requires independent Owner approval and applicable rights.
- All new image features must be tested for: no binary downloads to project/server; no WordPress attachment creation; no permanent local cache; no substitution by unrelated domains; failed images do not break layout or checkout.
