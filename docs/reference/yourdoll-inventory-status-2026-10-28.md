# YourDoll historical URL inventory - 2026-10-28

## Scope and limitations
- 992 distinct, normalized URL paths recoverable from Internet Archive public CDX metadata (2025-2026).
- Not evidence that each path exists live today: direct www.yourdoll.com root, robots.txt, and sitemap probes were rejected with HTTP 403 from China and US devices.
- URL inventory only: actual HTML, product images, themes, original CSS/JS and checkout are NOT backed up by the CSV.
- Some template hints are probabilistic; a WooCommerce slug can also be an article.
- Historical index was truncated to bounded batches and still reported additional records. **This is not a complete all-page inventory**.
- 25 historical HTML samples were retrieved and their SHA-256 hashes verified on the China Mac; files remain private, are NOT a complete asset-inclusive backup, and are not committed to this public repository.

## Template hints
- editorial: 1
- homepage: 1
- information: 12
- pagination: 58
- probable_taxonomy: 68
- product: 617
- unclassified: 235

## Files
- [CSV URL manifest](yourdoll-historical-urls-2026-10-28.csv)
- [Metadata collection utility](../../scripts/wayback_index_inventory.py)

## Next gate
Obtain authorized access or a source export to enumerate current pages and preserve allowed original files privately. Do not bypass Cloudflare security controls.
