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
- `yourdoll.com` is a third-party reference, not the iDollmate source code.
- A public URL inventory is permitted; only archive content accessible through ordinary published endpoints when access and rights permit.
- Respect robots and terms, crawl conservatively; stop on access-denied, login, CAPTCHA, Cloudflare challenge or rate limits.
- Raw third-party HTML, original photos, CSS/JS, brand assets and copy must **not** be published in this public repository or pushed onto iDollmate by default.
- Derive original theme components, content structure and UX specifications from permitted reference observations.

## Release requirements
- Keep Dev non-indexed and use TLS. Configure test-only payments and shipping before transactional UAT.
- Verify WooCommerce activation, page routes, theme, responsive storefront, performance and security before release.
- Record deploy versions and rollback steps in the repository.
