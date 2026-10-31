#!/usr/bin/env python3
"""R8-G: read-only catalog source + rights-evidence metadata preflight.

This is NOT proof of legal rights or Owner approval. The actual private evidence
must be independently examined; this CLI never imports, fetches or publishes.
"""
from __future__ import annotations

import argparse
import csv
from datetime import date
import json
from pathlib import Path
import re
import sys

from r8_catalog_batch import preview, snapshot
from r8_catalog_preflight import POLICY_FILE

MANIFEST_FIELDS = (
    "source_ref", "content_origin", "rights_basis", "evidence_record_id",
    "evidence_sha256", "reviewed_by", "reviewed_date", "review_status"
)
BASIS = {"original": {"original_owned"}, "licensed": {"supplier_authorized", "licensed"}}
BLOCKED_PREFIXES = ("QA-", "QA_", "TEST-", "TEST_", "SYNTHETIC-", "SYNTHETIC_")
BLOCKED_SKU_MARKERS = ("IDM-R8-EXAMPLE-", "IDM-R8-SYNTH-", "IDM-R7-QA-", "IDM-R7-VARIANT-QA-")


def records(path: Path, max_bytes: int = 15_000_000) -> tuple[list[str], list[dict[str, str]]]:
    if not path.is_file() or path.is_symlink() or path.stat().st_size > max_bytes:
        raise ValueError("FILE_UNAVAILABLE_OR_UNSAFE")
    with path.open(encoding="utf-8-sig", newline="") as fh:
        rd = csv.DictReader(fh, strict=True)
        fields = rd.fieldnames or []
        if len(fields) != len(set(fields)):
            raise ValueError("DUPLICATE_COLUMN")
        rows = []
        for item in rd:
            if len(rows) >= 10_000 or None in item or any(v is None for v in item.values()):
                raise ValueError("MALFORMED_OR_OVERSIZE_ROWS")
            rows.append({k: v.strip() for k, v in item.items()})
    return fields, rows


def validate(catalog: Path, rights: Path, live_snapshot: Path) -> dict:
    issues: list[dict] = []
    issue_counts: dict[str, int] = {}
    issue_total = 0

    def fail(code: str, line: int = 0):
        nonlocal issue_total
        issue_total += 1
        issue_counts[code] = issue_counts.get(code, 0) + 1
        if len(issues) < 30:
            issues.append({"code": code, "line": line})

    catalog_fields, items = records(catalog)
    required = json.loads(POLICY_FILE.read_text(encoding="utf-8"))["columns"]
    if catalog_fields != required:
        fail("NONCANONICAL_CATALOG_HEADER")
    if not items:
        fail("EMPTY_CATALOG")
    src_refs: dict[str, set[str]] = {}
    for i, item in enumerate(items, 2):
        sku = item.get("sku", "").upper()
        ref = item.get("source_ref", "").upper()
        if sku.startswith(BLOCKED_SKU_MARKERS) or ref.startswith(BLOCKED_PREFIXES):
            fail("QA_OR_SYNTHETIC_RECORD_FORBIDDEN", i)
        if not ref:
            fail("SOURCE_REF_MISSING", i)
        src_refs.setdefault(item.get("source_ref", ""), set()).add(item.get("content_origin", ""))
    try:
        mf_fields, entries = records(rights)
    except ValueError:
        mf_fields, entries = [], []
        fail("RIGHTS_MANIFEST_MISSING_OR_UNSAFE")
    if tuple(mf_fields) != MANIFEST_FIELDS:
        fail("RIGHTS_MANIFEST_SCHEMA_INVALID")
    found: set[str] = set()
    for i, entry in enumerate(entries, 2):
        ref = entry.get("source_ref", "")
        if not ref or ref in found:
            fail("DUPLICATE_OR_EMPTY_SOURCE_REF", i)
        found.add(ref)
        origin = entry.get("content_origin", "")
        if ref not in src_refs:
            fail("UNREFERENCED_EVIDENCE_RECORD", i)
        if ref in src_refs and src_refs[ref] != {origin}:
            fail("CATALOG_RIGHTS_ORIGIN_MISMATCH", i)
        if entry.get("rights_basis", "") not in BASIS.get(origin, set()):
            fail("RIGHTS_BASIS_UNSUPPORTED", i)
        evidence = entry.get("evidence_record_id", "")
        digest = entry.get("evidence_sha256", "")
        if not re.fullmatch(r"[A-Z0-9][A-Z0-9_-]{5,119}", evidence) or evidence.startswith(BLOCKED_PREFIXES):
            fail("PRIVATE_EVIDENCE_POINTER_MISSING", i)
        if not re.fullmatch(r"[a-f0-9]{64}", digest):
            fail("EVIDENCE_DIGEST_MISSING", i)
        if (not entry.get("reviewed_by") or entry["reviewed_by"].upper().startswith(BLOCKED_PREFIXES)
                or entry.get("review_status") != "approved"):
            fail("RIGHTS_REVIEW_NOT_APPROVED", i)
        try:
            reviewed = date.fromisoformat(entry.get("reviewed_date", ""))
            if reviewed > date.today():
                fail("REVIEW_DATE_IN_FUTURE", i)
        except ValueError:
            fail("REVIEW_DATE_INVALID", i)
    for ref in src_refs:
        if ref and ref not in found:
            fail("MISSING_PRIVATE_EVIDENCE_FOR_SOURCE")
    live = snapshot(live_snapshot)
    p = preview(catalog, live)
    if not p["ready_for_owner_review"]:
        fail("WOO_SKU_TAXONOMY_OR_CSV_PREVIEW_BLOCKED")
    report = {
        "schema": "r8-g-source-rights-v1",
        "site": live["site"],
        "existing_woo_records": len(live["products"]),
        "candidate_rows": len(items),
        "source_references": len(src_refs),
        "evidence_entries": len(entries),
        "csv_sha256": p["source_sha256"],
        "snapshot_sha256": p["snapshot_sha256"],
        "policy_sha256": p["policy_sha256"],
        "woo_preview_error_count": p["error_count"],
        "woo_preview_blocker_count": p["blocker_count"],
        "rights_metadata_issue_count": issue_total,
        "issue_code_counts": dict(sorted(issue_counts.items())),
        "issues": issues,
        "issues_truncated": issue_total > len(issues),
        "ready_for_independent_owner_evidence_review": issue_total == 0,
        "legal_rights_independently_proven": False,
        "owner_write_pass_received": False,
        "production_import_approved": False,
        "simulation_only": True,
        "dev_write_performed": False,
    }
    return report


def main() -> int:
    ap = argparse.ArgumentParser(description=__doc__)
    ap.add_argument("--catalog", type=Path, required=True)
    ap.add_argument("--rights-manifest", type=Path, required=True)
    ap.add_argument("--snapshot", type=Path, required=True)
    ap.add_argument("--report", type=Path)
    args = ap.parse_args()
    try:
        out = validate(args.catalog, args.rights_manifest, args.snapshot)
        data = json.dumps(out, indent=2, ensure_ascii=False) + "\n"
        if args.report:
            if args.report.exists() or args.report.is_symlink():
                raise ValueError("REPORT_EXISTS_NO_OVERWRITE")
            args.report.parent.mkdir(parents=True, exist_ok=True)
            args.report.write_text(data, encoding="utf-8")
        print(data)
        return 0 if out["ready_for_independent_owner_evidence_review"] else 1
    except (OSError, ValueError, csv.Error, json.JSONDecodeError) as e:
        print(json.dumps({"status": "blocked", "error_code": str(e),
                          "dev_write_performed": False}), file=sys.stderr)
        return 2


if __name__ == "__main__":
    raise SystemExit(main())
