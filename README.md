# iDollmate

WordPress + WooCommerce storefront development for **iDollmate**.

## Environments and ownership

- **Dev URL:** https://dev.idollmate.com
- **Host:** existing Hostinger Cloud Startup, server908 (USA, NC).
- **WordPress:** 7.1.3, **WooCommerce:** 11.2.1, **PHP:** 8.3.
- **Production domain:** `idollmate.com` currently points to Shopify. This repository does not authorize changing the existing production site.
- **GitHub:** public `beyourlovercom/idollmatecom` (main). No WordPress core, private production data or third-party reference captures in Git.

## Current environment baseline — 2026-10-27

- Dev WordPress/WooCommerce installed and activated using Hostinger SSH + WP-CLI.
- HTTPS is reachable and routes `/`, `/shop/`, `/cart/`, `/checkout/`, `/my-account/`, `/wp-json/` return HTTP 200.
- Dev search indexing disabled in WordPress settings (`blog_public=0`).
- Cloudflare subdomain DNS: `dev` A record to Hostinger `193.46.197.115`, **DNS only**.
- Administrator user: `idollmate_owner` (access via Hostinger WP Admin or password reset to the admin mailbox).
- Theme is WordPress Twenty Twenty-Five by default; iDollmate original theme customization is pending.

## Repository layout

- `AGENTS.md`: binding development, security and release instructions.
- `docs/REFERENCE_ARCHIVE.md`: YourDoll external reference audit and access restrictions.
- `scripts/reference_inventory.py`: robots-aware URL inventory and optional **private** HTML capture script.
- `.gitignore`: prevents third-party captures, media, backups and secrets from being committed.

## YourDoll reference

`yourdoll.com` is protected by Cloudflare challenge / 403 on automated access; a complete backup has **not** been made. The inventory utility must stop on blocked access, and source pages/assets must not be republished to this public repository or Dev site without rights.

## Next development steps

1. Review the WooCommerce setup and admin access, configure test payment/shipping only.
2. Design and implement original iDollmate WooCommerce templates, category taxonomy and product attributes.
3. Create a permitted reference URL inventory when authorized public/export data is available.
4. Dev QA for performance, storefront desktop/mobile, security, order flows, and Owner UAT before any production release.

See `AGENTS.md` before making changes.
