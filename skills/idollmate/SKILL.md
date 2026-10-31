---
name: idollmate-local-production
description: iDollmate WordPress/WooCommerce project-specific workflow; China Windows Local development and gated release to the official WordPress target dev.idollmate.com. Does not touch independent idollmate.com Shopify.
---

# iDollmate — site-specific SKILL (S0 extension)

**Precedence**: project `AGENTS.md` > this site Skill > shared `beyourlovercom/skills-webdev/skills/rapid-webdev/SKILL.md`. This file is source guidance, not an installed MCP, deployment permission or ChatGPT-native Skill.

## Site identity and environments
- Business: original iDollmate WordPress/WooCommerce catalog; USD-native SKU, variable material (TPE/Silicone) and manager-only QA; legal/original/licensed images and copy.
- Source: `beyourlovercom/idollmatecom`, R7 PR #3 Draft; R8 PR #5 Draft stacked on R7; Issue #4. No merges or force pushes without explicit Owner authorization.
- Development and authoritative pre-release runtime: **China Windows / separate Local WordPress site, dedicated path/ports/database**, never reuse another Local site.
- Sole WordPress production **target**: **https://dev.idollmate.com**. Its `dev` prefix is historical; it is *not* an intermediate staging hop, and no further WWW migration exists.
- **https://idollmate.com is unrelated Shopify**. Never alter its DNS, store, payments, data, theme or deployment from this project.

## Execution: S0 → S1 → S2-A → S2-B → S2-C → S3–S6
- S0: verify domain-to-environment map, technology, objectives, governance, permissions, installed tools, current Git SHA, previous UAT evidence; emit remaining delta and evidence log. Never silently apply another site's topology.
- S1: research via authorized public or connected analytics/search/ad data; read-only unless separate permission; label unverified data.
- S2-A Local builder: use current R8 Draft source plus R7 dependency; isolate DB and synthetic Draft/Hidden SKU fixtures. Woo owns prices, SKU, variation, taxonomy; paid upgrades stay in approved EPO ownership. Resolve records via SKU plus batch marker, **never assume Dev numeric IDs locally**.
- S2-B local acceptance: PHP/static tests, native Woo runtime, desktop + 390px mobile actual-browser screenshot, authentic draft preview/variation pricing, filters, placeholder, access protections. Record versions, commands, expected/actual, screenshot locations, failure count and rollback. Previously verified Dev 36/36 discovery and seven admin filter checks are historical evidence, not local browser PASS.
- S2-C **controlled production release**: once Local PASS and exact file/data diff, version, backup/rollback, and protection baseline are reviewed, STOP for separate Owner `pass`. Only then deploy to `dev.idollmate.com` and read back. No direct remote edits, imports, plugin update, taxonomy/data changes or publish before approval.
- S3: live analytics events/attribution; S4: live SEO; S5: approved content migration; S6: approved content operations on `dev.idollmate.com`. Code/plugin/theme/schema/global SEO changes always return through S2-A/B/C gates.
- Until a separate **launch / indexing** approval, keep production-target `blog_public=0` and Woo Coming Soon enabled, all QA products Draft/Hidden, no test customer/order/payment data; never confuse **production target identity** with **public store launch**.

## Current R8-G/H source-first continuation; do not reopen closed R8-E
1. **R8-E released/closed on 2026-10-31:** Owner manually installed exact Discovery 0.1.2 on final Woo WordPress target; installed PHP/CSS SHA matched, actual site contract 40/40, manager QA 11/11, anonymous 4/4, real Draft preview 2/2; logged limitation: remote PHP error log inaccessible. 98/98 matching Hostinger theme Local browser checks, including multi-page pagination, were already PASS. **Do not retest/refactor these without a new evidence-driven regression.**
2. **Latest protected target baseline:** 8 historical QA-only SKU records across R7/R8; categories mini-dolls/full-body-dolls + uncategorized; global material tpe/silicone; no Woo Brands. Read-only snapshot private Mac/isolated Windows, 2026-10-31; no real 2,000-SKU supplier source or granted catalog license evidenced.
3. **R8-G/H stage:** prepare lawful supplier-owned/authorized source CSV and private rights evidence manifest via docs/templates. Run canonical `r8_catalog_preflight.py`, current-snapshot `r8_catalog_batch.py preview` and `r8_source_rights_preflight.py`. Reject existing R7/R8 QA reimports, invented/synthetic `IDM-R8-SYNTH-*` source, taxonomy/slug collisions, missing approved rights evidence. **Metadata self-attestation is not independently proven legal rights or Owner permission**; manually inspect evidence and its SHA.
4. 2,000-row synthetic Local preview tested structural capacity only (0.0627s on actual eight-SKU snapshot); it does NOT authorize any 2,000-row Woo import. The empty real supplier file, rights/license verification, final category/attribute dictionary, and small approved new Draft Canary are OPEN.
5. Never re-Apply or rollback the historical applied QA batch `cfc3ead963a72e0aa44740d5` (target IDs 32–35) without a **new specific Owner approval**; no public products, no competitor images and no payment tests. All future live term creation, product writes and PR merges remain separate gates.

## Evidence/approval contract
Every result declares `LOCAL PASS/FAIL/OPEN`, source HEAD and file hashes, exact host, UAT commands and screenshots, original-vs-synthetic fixtures, remaining blockers, GitHub PR state, and **whether Production target changed (default NO)**. Preserve authorized local and production recovery evidence without committing secrets. For the already-closed Discovery 0.1.2 manual release only, Owner explicitly waived new backups; do not infer a blanket backup waiver for future product data changes. Never claim browser PASS for isolated PHP tests.
