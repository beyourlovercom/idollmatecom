#!/usr/bin/env python3
"""R8-C: offline Preview -> Confirm -> batch rollback simulation.
No WordPress, network or Production access. Confirm modifies only a local JSON ledger.
"""
import argparse
import csv
import hashlib
import json
import os
import re
from pathlib import Path
import sys

from r8_catalog_preflight import preflight, POLICY_FILE

ORIGIN = "https://dev.idollmate.com"
MARKER = "R8-LOCAL-SIMULATOR-NO-WP-WRITES"
MAX_ROWS = 2000


def digest(v):
    return hashlib.sha256(json.dumps(v, sort_keys=True, ensure_ascii=False,
                                     separators=(",", ":")).encode()).hexdigest()


def snapshot(path):
    if not path.is_file() or path.stat().st_size > 8000000:
        raise ValueError("Missing or oversized inventory snapshot")
    s = json.loads(path.read_text(encoding="utf-8"))
    if s.get("site") != ORIGIN or str(s.get("blog_public")) != "0" or s.get("coming_soon") != "yes":
        raise ValueError("Snapshot must be protected dev.idollmate.com")
    if not isinstance(s.get("products"), list) or not isinstance(s.get("categories"), list):
        raise ValueError("Snapshot products/categories missing")
    if not isinstance(s.get("brands"), list) or not isinstance(s.get("attributes"), dict):
        raise ValueError("Snapshot brand/attribute dictionary missing")
    for item in s["products"]:
        if not isinstance(item.get("id"), int) or not isinstance(item.get("sku"), str):
            raise ValueError("Malformed snapshot product")
        if not isinstance(item.get("integrity_sha256"), str) or not re.fullmatch(
                r"[a-f0-9]{64}", item["integrity_sha256"]):
            raise ValueError("Legacy weak snapshot: protected product integrity digest required")
        if item.get("type") not in ("simple", "variable", "variation"):
            raise ValueError("Unsupported snapshot product type")
    skus = [x["sku"].casefold() for x in s["products"] if x["sku"]]
    if len(skus) != len(set(skus)):
        raise ValueError("Conflicting existing SKU inventory")
    if any(not isinstance(x, str) for x in s["categories"] + s["brands"]):
        raise ValueError("Malformed snapshot taxonomy")
    if any(not isinstance(v, list) or any(not isinstance(x, str) for x in v)
           for v in s["attributes"].values()):
        raise ValueError("Malformed snapshot attribute terms")
    return s


def rows(path):
    with path.open("r", encoding="utf-8-sig", newline="") as f:
        return [{k: v.strip() for k, v in row.items()}
                for row in csv.DictReader(f, strict=True)]


def preview(path, state):
    if any(not isinstance(item.get("integrity_sha256"), str) or
           not re.fullmatch(r"[a-f0-9]{64}", item["integrity_sha256"])
           for item in state.get("products", [])):
        raise ValueError("Legacy weak snapshot: protected product integrity digest required")
    sku_set = {x["sku"] for x in state["products"] if x["sku"]}
    check = preflight(path, existing_skus=sku_set, max_rows=MAX_ROWS)
    blockers = []
    actions = []
    existing_slugs = {x.get("slug") for x in state["products"]
                      if x.get("type") != "variation"}
    categories = set(state["categories"])
    brands = set(state["brands"])
    attr = {k: set(v) for k, v in state["attributes"].items()}
    if check["error_count"] or not check["can_import"]:
        blockers.append({"code": "CSV_INVALID", "count": check["error_count"]})
    else:
        for line, r in enumerate(rows(path), 2):
            sku, typ = r["sku"], r["row_type"]
            actions.append({"line": line, "sku": sku, "type": typ, "operation": "CREATE_DRAFT",
                            "parent_sku": r["parent_sku"]})
            if typ != "variation":
                if r["slug"] in existing_slugs:
                    blockers.append({"line": line, "sku": sku, "code": "SLUG_CONFLICT"})
                if r["category_slug"] not in categories:
                    blockers.append({"line": line, "sku": sku, "code": "CATEGORY_NOT_INSTALLED",
                                     "term": r["category_slug"]})
                if r["brand_slug"] and r["brand_slug"] not in brands:
                    blockers.append({"line": line, "sku": sku, "code": "BRAND_NOT_INSTALLED"})
            for col, tax in (("material", "pa_material"), ("size_code", "pa_size_code")):
                if r[col] and r[col] not in attr.get(tax, set()):
                    blockers.append({"line": line, "sku": sku, "code": "ATTRIBUTE_TERM_MISSING",
                                     "taxonomy": tax, "term": r[col]})
    policy_hash = hashlib.sha256(POLICY_FILE.read_bytes()).hexdigest()
    snapshot_hash = digest(state)
    plan_id = hashlib.sha256(("r8-c-v1:" + str(check["source_sha256"]) + ":" +
                              snapshot_hash + ":" + policy_hash).encode()).hexdigest()[:24]
    good = len(blockers) == 0 and check["can_import"]
    return {
        "schema": "r8-c-v1", "site": ORIGIN, "plan_id": plan_id,
        "source_sha256": check["source_sha256"], "snapshot_sha256": snapshot_hash,
        "policy_sha256": policy_hash, "total_rows": check["total_rows"],
        "row_types": check["type_counts"], "insert_draft": len(actions), "update_existing": 0,
        "publish": 0, "delete_existing": 0, "error_count": check["error_count"],
        "errors": check["errors"], "blocker_count": len(blockers),
        "blockers": blockers[:250], "blockers_truncated": len(blockers) > 250,
        "actions_preview": actions[:25], "ready_for_owner_review": good,
        "approval_phrase": "R8 CONFIRM " + plan_id + " " + str(check["total_rows"]),
        "simulation_only": True, "dev_write_performed": False,
        "requires_new_dev_write_pass": True
    }


def save(path, value):
    if path.is_symlink():
        raise ValueError("Refusing symlink path")
    path.parent.mkdir(parents=True, exist_ok=True)
    tmp = path.with_name(path.name + "." + str(os.getpid()) + ".tmp")
    try:
        with tmp.open("x", encoding="utf-8") as f:
            os.chmod(tmp, 0o600)
            json.dump(value, f, indent=2, sort_keys=True)
            f.write("\n")
            f.flush()
            os.fsync(f.fileno())
        os.replace(tmp, path)
    finally:
        if tmp.exists():
            tmp.unlink()


def plan_from(path):
    obj = json.loads(path.read_text(encoding="utf-8"))
    if obj.get("schema") != "r8-c-v1" or obj.get("site") != ORIGIN:
        raise ValueError("Non-Dev or invalid plan")
    return obj


def ledger_from(path):
    if path.is_symlink():
        raise ValueError("Refusing symlink ledger")
    if not path.exists():
        return {"marker": MARKER, "site": ORIGIN, "products": {}, "batches": {}}
    obj = json.loads(path.read_text(encoding="utf-8"))
    if obj.get("marker") != MARKER or obj.get("site") != ORIGIN:
        raise ValueError("Not a local R8 simulator ledger")
    if not isinstance(obj.get("products"), dict) or not isinstance(obj.get("batches"), dict):
        raise ValueError("Invalid ledger shape")
    return obj


def confirm(csv_file, state, proposal, approval, ledger_file):
    actual = preview(csv_file, state)
    if not actual["ready_for_owner_review"]:
        raise ValueError("Review is blocked by conflicts or missing taxonomy terms")
    for key in ("plan_id", "source_sha256", "snapshot_sha256", "policy_sha256", "total_rows"):
        if proposal.get(key) != actual[key]:
            raise ValueError("STALE_PLAN: CSV, snapshot or policy changed")
    if approval != actual["approval_phrase"]:
        raise ValueError("Approval phrase mismatch")
    ledger = ledger_from(ledger_file)
    batch = actual["plan_id"]
    if batch in ledger["batches"]:
        status = ledger["batches"][batch]["status"]
        if status == "applied":
            return {"status": "already_applied", "created": 0, "simulation_only": True}
        raise ValueError("Already rolled back. Create a new independent batch plan.")
    records = rows(csv_file)
    if len(records) != actual["total_rows"]:
        raise ValueError("CSV count changed before commit")
    if any(r["sku"].casefold() in ledger["products"] for r in records):
        raise ValueError("Existing simulation SKU cannot be overwritten")
    created = []
    for r in records:
        key = r["sku"].casefold()
        ledger["products"][key] = {
            "sku": r["sku"], "batch_id": batch, "status": "draft",
            "row_sha256": digest(r), "simulated": True
        }
        created.append(key)
    ledger["batches"][batch] = {
        "status": "applied", "created_skus": created,
        "source_sha256": actual["source_sha256"], "snapshot_sha256": actual["snapshot_sha256"]
    }
    save(ledger_file, ledger)
    return {"status": "applied", "created": len(created), "batch": batch,
            "simulation_only": True, "dev_write_performed": False}


def rollback(proposal, approval, ledger_file):
    batch = proposal["plan_id"]
    if approval != "R8 ROLLBACK " + batch:
        raise ValueError("Rollback approval phrase mismatch")
    ledger = ledger_from(ledger_file)
    data = ledger["batches"].get(batch)
    if data is None:
        raise ValueError("Unknown batch: no external records can be touched")
    if data["status"] == "rolled_back":
        return {"status": "already_rolled_back", "removed": 0,
                "simulation_only": True, "dev_write_performed": False}
    for sku in data["created_skus"]:
        p = ledger["products"].get(sku)
        if not p or p["batch_id"] != batch or p["status"] != "draft":
            raise ValueError("Product modified or missing; entire rollback denied")
    for sku in data["created_skus"]:
        del ledger["products"][sku]
    data["status"] = "rolled_back"
    save(ledger_file, ledger)
    return {"status": "rolled_back", "removed": len(data["created_skus"]),
            "simulation_only": True, "dev_write_performed": False}


def main(argv=None):
    ap = argparse.ArgumentParser(description="R8-C OFFLINE ONLY. No Woo write capability.")
    modes = ap.add_subparsers(dest="mode", required=True)
    p = modes.add_parser("preview")
    p.add_argument("--csv", required=True, type=Path)
    p.add_argument("--snapshot", required=True, type=Path)
    p.add_argument("--plan", required=True, type=Path)
    c = modes.add_parser("confirm")
    c.add_argument("--csv", required=True, type=Path)
    c.add_argument("--snapshot", required=True, type=Path)
    c.add_argument("--plan", required=True, type=Path)
    c.add_argument("--approval", required=True)
    c.add_argument("--sandbox-ledger", required=True, type=Path)
    b = modes.add_parser("rollback")
    b.add_argument("--plan", required=True, type=Path)
    b.add_argument("--approval", required=True)
    b.add_argument("--sandbox-ledger", required=True, type=Path)
    a = ap.parse_args(argv)
    try:
        if a.mode == "preview":
            if a.plan.exists():
                raise ValueError("Plan already exists, choose new path")
            result = preview(a.csv, snapshot(a.snapshot))
            save(a.plan, result)
        elif a.mode == "confirm":
            result = confirm(a.csv, snapshot(a.snapshot), plan_from(a.plan),
                             a.approval, a.sandbox_ledger)
        else:
            result = rollback(plan_from(a.plan), a.approval, a.sandbox_ledger)
    except (KeyError, ValueError, OSError, csv.Error, json.JSONDecodeError) as exc:
        print(json.dumps({"status": "blocked", "reason": str(exc),
                          "dev_write_performed": False}), file=sys.stderr)
        return 2
    print(json.dumps(result, indent=2, ensure_ascii=False))
    return 0 if result.get("ready_for_owner_review", True) else 1


if __name__ == "__main__":
    raise SystemExit(main())
