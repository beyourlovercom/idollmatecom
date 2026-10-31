# iDollmate R8-F1 → F3 — Read-only preflight and exact release approval sheet

**Execution:** 2026-10-31 (Asia/Taipei). **Outcome:** **F1 PASS (40/40), F2 OMITTED by explicit Owner request, F3 READY FOR OWNER DECISION.** Not a deployment authorization and not a PR-merge authorization.

## 0. Override of earlier generic plan
Owner's explicit scope for this execution: **"优先完成 R8-F1 → F2 → F3，不需要备份。直接执行。都是测试站点"**. Accordingly, **no new full WordPress file backup, database dump/snapshot, or plugin backup was created for F2**. F2 must be marked **SKIPPED — OWNER WAIVED**, **not PASS**. This changes the backup requirement **only for this requested R8-F1–F3 approval preparation**, not the rule that F4 deployment and PR merges require separate Owner approval. This project continues to treat `https://dev.idollmate.com` as the final dedicated WordPress target, notwithstanding its historical "dev" prefix. `https://idollmate.com` is unrelated Shopify and is excluded.

## F1 — actual target read-only WP-CLI validation: PASS (40/40)
Execution was by the existing authorized China Mac SSH alias `jutdo-hostinger` using **verified WP home and directory identity** `$HOME/domains/dev.idollmate.com/public_html`, then a read-only PHP probe supplied via **WP-CLI eval-file STDIN**. The probe was stored **only on China Mac**, not written into the WordPress host. Its first invocation hit a PHP **nested ternary syntax error** in the test itself and completed with no site changes; after correcting only the local probe expression, the second invocation returned **40 PASS / 0 FAIL** and exit code 0.

Checked exact site/runtime/config:
- WP `7.1.4`, PHP `8.3.x` (previous exact target PHP `8.3.36`), Woo `11.2.1`, theme `hostinger-ai-theme` `2.1.15`, currency USD.
- `blog_public=0`; `woocommerce_coming_soon=yes`.
- `idollmate-discovery` active `0.1.1`.
- **Live 0.1.1 PHP SHA256** `88d95e55412db67ebafdfe8b99653c03b8f0f929ba69d6f4d3e00673b5dd318a`.
- **Live CSS SHA256** `efb9b680c95b6d0c7652ef959904880e1c506fa985b5faf48581127766efbdce`.
- Approved batch id `cfc3ead963a72e0aa44740d5`; journal `applied`, `created_count=4`, exact original IDs `[32,33,34,35]`.
- Four unique SKUs: `IDM-R8-EXAMPLE-SIMPLE`→32; `IDM-R8-EXAMPLE-PARENT`→33; `IDM-R8-EXAMPLE-TPE`→34; `IDM-R8-EXAMPLE-SIL`→35. All four `_idm_r8_batch_id` values match the approved batch.
- Parent/simple 32/33 correctly `draft` / catalog `hidden`, no Woo image/gallery media; children 34/35 correctly `variation`, associated with parent 33, their internal `publish` status does **not** expose unpublished parent products.
- Prices USD **599 / 999 / 1099** exactly; no published top-level products; **zero Woo order-item references** to these four IDs; original R7 entities 13, 15, 16, 17 remain present.
- Output ending: `F1_REAL_DEV_READONLY_PASS` and `check_failure_count=0`.

**F1 verdict:** **PASS, no target WP/DB write performed.** This was a metadata and integrity audit, not a full PHP 8.3 product purchase or merchant/payments test. The R7 original entities were checked for existence, not re-fingerprinted; prior protected R7 state remains a separate change boundary.

## F2 — backup expressly excluded
- **SKIPPED / OWNER WAIVED.** No new backup made. No backup freshness or database restore capability is asserted.
- Existing prior-turn read-only copy of the live 0.1.1 plugin remains privately stored on China Mac from earlier on 2026-10-31, as pre-existing evidence, not produced by F2. Its previously logged archive SHA256 is `c5bd614b93de68b275eb5464f9d2aed7e7df1136ef31d262a8bf82b85ddba977`; live 0.1.1 PHP and CSS byte hashes are verified again under F1 above. **No additional archive created.**
- **Risk acknowledged:** without a new full WP/database backup, a hypothetical unrelated production data corruption cannot be restored to a fresh pre-release snapshot. For the bounded plugin-only change with no migrations, rollback is limited to restoring the *pre-existing old plugin code* (after checking archive and live old-version identity) and repeating protected-data readbacks. Explicit Owner acceptance of this no-backup risk must remain in the F4 release decision.

## F3 — candidate and governance gates: PASS, frozen for Owner review
Only candidate and **no automatic deploy**:
- **Target** `https://dev.idollmate.com` WordPress only.
- **Artifact** `D:\ai\idollmate-isolated\_qa\idollmate-discovery-0.1.2-VERIFIED-LF-PREAPPROVAL.zip` on isolated Windows Local.
- **ZIP bytes** `6247`, SHA256 `6053a965238180c70f941b848a27466c840edecbe643123001f429ba32146489`.
- **Exactly two included files:** `idollmate-discovery/idollmate-discovery.php` new raw Git SHA256 `0b8a126785a29d99351f196eb19bc58b5fc7dc89d9935aabdc7c3391f192905d`; `idollmate-discovery/assets/discovery.css` SHA256 `efb9b680c95b6d0c7652ef959904880e1c506fa985b5faf48581127766efbdce`, **byte-identical to live**.
- **Deprecated / forbidden** old Windows CRLF archive SHA256 `95d1556d8e130730ea7099882c17fd58103f8e5fb8431619bda1f6fbe2e6bc90`.
- `r8_discovery_scope_verify.py` returned `R8_DISCOVERY_012_SCOPE_PASS`: only admin QA SKU/journal resolution and plugin header version change compared with 0.1.1; frontend theme, product customer templates, prices, checkout, CSS, data models and database migrations **unmodified**.
- `r8_discovery_preapproval_verify.py` returned `PREAPPROVAL_PACKAGE_INTEGRITY_PASS`: zip SHA, exact membership, byte-for-byte raw Git blob equality, 0.1.2 header.
- The candidate plugin PHP was syntax-checked by *actual target PHP8.3* via SSH STDIN and returned **no syntax errors** (previous same-day evidence).
- Windows checkout at preparation had no uncommitted changes and matched remote Draft branch head **`67176fdf32fd7d1a62c1aa7fa30ae18b35ec8d7a`**. New documentation commits may advance the Draft head afterward **without changing this sealed plugin artifact**. Never treat an updated docs commit as permission to rebuild arbitrary PHP.
- Exact Hostinger 2.1.15 theme copied read-only to Windows Local and real Chrome/Woo suite **98/98 PASS**; see `docs/uat/R8_E_HOSTINGER_AI_THEME_LOCAL_PARITY_2026-10-31.md`.
- GitHub PR #3 and PR #5 were independently read back as **open / Draft / unmerged**.

## Future F4–F6 — strictly withheld pending a NEW Owner command
**Not executed in this request.** Separate Owner approval must identify:
1. Release *only* Discovery **0.1.1 → 0.1.2** to `https://dev.idollmate.com` with ZIP hash `6053a965...`, no R7/R8 stacked PR merges.
2. Confirm explicit no-new-backup release risk acceptance, if that remains the Owner's preference.
3. No theme, media, SKU, product publication, category import, order, payment, customer, DNS, Shopify, indexing, Coming Soon or unrelated plugin modifications.
4. Exact runtime script gate before write: `home`, 0.1.1 active, PHP/CSS pre-hashes, approved 4-SKU batch/journal, zero QA order refs, noindex/Coming Soon, target plugin folder; **abort on drift**, and verify ZIP raw file bytes.
5. After authorized update: read back Discovery 0.1.2 and installed PHP/CSS SHA256; manager QA filter set, Draft preview and variation price reads, anonymous Draft 404, Woo frontend HTTP200, noindex/Coming Soon and error logs. **No purchase, payment or merchant tests**.
6. Rollback on failure: restore exactly previous 0.1.1 plugin code using verified previously archived copy, re-read protection gates. **No database rollback** is available under the no-backup waiver; never call such rollback successful until code and data are verified.

**Final state: F1 PASSED, F2 SKIPPED BY OWNER, F3 COMPLETED; RELEASE NOT EXECUTED, OWNER APPROVAL PENDING.**
