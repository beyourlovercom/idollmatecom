# YourDoll reference inventory / archival policy

Target: https://yourdoll.com (third-party public website)
Purpose: Internal architecture research for iDollmate, not republishing another site's content.

## Current access gate (2026-10-27)
Public pages and candidate sitemaps returned a Cloudflare challenge / HTTP 403 from automated requests. Complete page acquisition is NOT verified or complete. Do not represent this as a full backup.

Attempted:
- `https://yourdoll.com/`
- `https://www.yourdoll.com/robots.txt`
- `https://www.yourdoll.com/sitemap_index.xml`
- `https://www.yourdoll.com/sitemap.xml`
- `https://www.yourdoll.com/wp-sitemap.xml`

## Procedure when authorized access is available
1. Verify permission to retain reference materials and the site's terms.
2. Inspect permitted robots and sitemap endpoints; inventory all discoverable URL classes.
3. Capture only accessible, authorized pages at low request rate. Stop on 403, 429, CAPTCHA or authentication barriers.
4. Keep captured content under ignored local/private `reference-archive/`, with source URL and capture date.
5. Compare templates: homepage, categories, products, filters, cart, checkout, accounts, information, blog.
6. Build **original** iDollmate WordPress templates, WooCommerce product attributes, navigation and content.

Do not copy live competitor text/images into iDollmate or the public GitHub repository without permission.
