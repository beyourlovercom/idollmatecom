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

## R8-E delta; do not repeat closed R7/R8 tasks
1. Verify Local is genuinely isolated before any write; check theme, WordPress/Woo/PHP versions and plugin dependencies.
2. Test admin-only R8 QA SKU-based lookup; native draft/simple/variable preview; TPE USD999, Silicone USD1099, simple USD599 where synthetic fixtures are authorized.
3. Test real browser widths desktop/390px, variation selection/price, inaccessible public Draft, category and material UI, placeholder without external images, keyboard and focus.
4. Pagination remains **OPEN** until an authorized multi-page fixture demonstrates it. No dummy PASS by using hidden Draft products.
5. Handoff lists the former Dev batch as `cfc3ead963a72e0aa44740d5`; Dev IDs 32–35 are only history. Do not re-Apply four Dev products or perform destructive rollback.

## Evidence/approval contract
Every result declares `LOCAL PASS/FAIL/OPEN`, source HEAD and file hashes, exact host, UAT commands and screenshots, original-vs-synthetic fixtures, remaining blockers, GitHub PR state, and **whether Production target changed (default NO)**. Preserve local and production backups without committing secrets. Never claim browser PASS for isolated PHP tests.
