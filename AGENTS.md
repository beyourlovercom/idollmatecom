# iDollmate Engineering Rules

## Project
- Public source repository: `beyourlovercom/idollmatecom`, default branch `main`.
- **China Windows / isolated Local WordPress** is the exclusive default development and browser-UAT environment. Never reassign or restart another READY device or Local site.
- **WordPress production target**: `https://dev.idollmate.com`, Hostinger Cloud Startup, WordPress + WooCommerce. The `dev` prefix is historical; this is not a staging hop to a separate WWW.
- The existing `https://idollmate.com` Shopify store is **independent** and explicitly excluded from every development/release operation, including DNS, data, payments and migration.
- Reuse Andy Core / Andy Commerce via their canonical repositories where applicable. Do not duplicate shared plugin code.
- WordPress core, private media, real orders, database dumps, tokens and credentials must never be committed.

## Approval gates
- Workflow: isolated Windows Local development → Local tests and actual desktop/mobile browser UAT → release manifest / rollback preparation → **separate Owner `pass`** → controlled deployment to the WordPress production target → independent production-target readback.
- No code, plugin, schema, Woo product, indexing, publication or payment changes on `dev.idollmate.com` before separate approval; R7 PR #3 and R8 PR #5 remain Draft/unmerged.
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
- Keep the production target **non-indexed (`blog_public=0`) and Coming Soon** until an independently approved launch/indexing decision; status as a production *target* is not a public launch. Preserve Draft/Hidden QA fixtures. No payment testing in current R8 scope.
- Verify WooCommerce activation, page routes, theme, responsive storefront, performance and security before release.
- Record deploy versions and rollback steps in the repository.

## Site Skill and operations
- Apply `skills/idollmate/SKILL.md` and `skills/idollmate/site.yaml` as this project's S0 overrides; reuse shared `rapid-webdev` for common WordPress/WooCommerce workflows.
- S1 authorized data research is read-only; S2-A/B occur only on isolated Windows Local; S2-C is controlled release to `dev.idollmate.com` after approval. S3–S6 daily operations target that WordPress site, while technical changes still return through Local and the release gate.
- Local Woo QA must resolve product records using stable SKU + approved batch marker; historical production-target product IDs are not portable.
