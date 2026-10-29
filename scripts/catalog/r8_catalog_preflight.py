#!/usr/bin/env python3
"""R8 Catalog OS: offline, read-only CSV preflight for ORIGINAL iDollmate products.

No WordPress import, no HTTP, no image fetch, no connection to any shop.
Produces a bounded JSON diagnostic and exits nonzero for invalid input.
"""
from __future__ import annotations

import argparse
import csv
from decimal import Decimal, InvalidOperation
import hashlib
import json
from pathlib import Path
import re
import sys
from urllib.parse import urlsplit

POLICY_FILE = Path(__file__).with_name("r8_catalog_policy.json")
SKU_RE = re.compile(r"^[A-Za-z0-9][A-Za-z0-9._-]{2,63}$")
SLUG_RE = re.compile(r"^[a-z0-9]+(?:-[a-z0-9]+)*$")
CODE_RE = re.compile(r"^[A-Za-z0-9][A-Za-z0-9._-]{0,31}$")
SOURCE_RE = re.compile(r"^[A-Za-z0-9][A-Za-z0-9._:-]{2,95}$")
PRICE_RE = re.compile(r"^(?:0|[1-9]\d*)(?:\.\d{1,2})?$")
DEC_RE = re.compile(r"^\d+(?:\.\d{1,2})?$")
IMAGE_PATH_RE = re.compile(r"^/wp-content/uploads/[A-Za-z0-9/_-]+(?:\.[A-Za-z0-9_-]+)*\.(?:jpe?g|png|webp|gif|avif)$", re.IGNORECASE)
MAX_BYTES = 20 * 1024 * 1024
MAX_NOTES = 250


def valid_reference_image(url: str) -> bool:
    """Only a non-rendered original URL reference, not rights to use the image."""
    if not url:
        return True
    if len(url) > 2048 or any(ord(x) < 33 for x in url):
        return False
    try:
        p = urlsplit(url)
    except ValueError:
        return False
    return (p.scheme == "https"
            and p.hostname in ("yourdoll.com", "www.yourdoll.com")
            and p.port is None and not p.username and not p.password
            and not p.query and not p.fragment
            and bool(IMAGE_PATH_RE.fullmatch(p.path))
            and ".." not in p.path and "\\" not in p.path)


def safe_cell(value: str, *, limit: int = 200) -> bool:
    if len(value) > limit or "\x00" in value or "\r" in value or "\n" in value:
        return False
    # Guard cells that are commonly re-exported to Excel; no formula execution.
    if value.lstrip().startswith(("=", "+", "@")):
        return False
    return True


def positive_metric(value: str, maximum: str) -> bool:
    if not value:
        return True
    if not DEC_RE.fullmatch(value):
        return False
    try:
        return Decimal("0") < Decimal(value) <= Decimal(maximum)
    except InvalidOperation:
        return False


def money(value: str) -> bool:
    if not PRICE_RE.fullmatch(value):
        return False
    try:
        return Decimal("0") < Decimal(value) <= Decimal("999999.99")
    except InvalidOperation:
        return False


def image_warning(image: str) -> str | None:
    if image:
        return "Reference URL is private metadata only, not an image license or Woo gallery asset."
    return None


def preflight(path: Path, *, existing_skus: set[str] | None = None,
              max_rows: int = 10000) -> dict:
    policy = json.loads(POLICY_FILE.read_text(encoding="utf-8"))
    columns = policy["columns"]
    report = {
        "schema": policy["schema_version"], "source_sha256": None,
        "total_rows": 0, "type_counts": {"simple": 0, "variable": 0, "variation": 0},
        "error_count": 0, "warning_count": 0, "errors": [], "warnings": [],
        "preview": [], "readonly": True, "can_import": False,
    }

    def note(kind: str, line: int, sku: str, message: str) -> None:
        report[kind + "_count"] += 1
        notes = report[kind + "s"]
        if len(notes) < MAX_NOTES:
            notes.append({"row": line, "sku": sku[:64], "message": message})

    if not path.is_file():
        note("error", 0, "", "CSV file does not exist")
        return report
    if path.stat().st_size > MAX_BYTES:
        note("error", 0, "", "CSV exceeds 20 MiB source preflight size limit")
        return report
    report["source_sha256"] = hashlib.sha256(path.read_bytes()).hexdigest()
    sku_lines: dict[str, int] = {}
    parent_slugs: set[str] = set()
    product_rows: dict[str, dict] = {}
    variations: list[tuple[int, dict]] = []
    used_combos: set[tuple[str, str, str]] = set()
    existing_skus = existing_skus or set()

    csv.field_size_limit(100000)
    try:
        with path.open("r", encoding="utf-8-sig", newline="") as f:
            reader = csv.DictReader(f, strict=True)
            headers = reader.fieldnames or []
            if len(headers) != len(set(headers)):
                note("error", 1, "", "CSV contains duplicate headers")
                return report
            missing = sorted(set(columns) - set(headers))
            extra = sorted(set(headers) - set(columns))
            if missing or extra:
                note("error", 1, "", "CSV schema mismatch: missing=" + ",".join(missing)
                     + "; extra=" + ",".join(extra))
                return report
            for line, raw in enumerate(reader, 2):
                report["total_rows"] += 1
                if report["total_rows"] > max_rows:
                    note("error", line, "", "Batch exceeds max_rows=" + str(max_rows))
                    break
                if None in raw or any(v is None for v in raw.values()):
                    note("error", line, "", "Malformed CSV row / unexpected columns")
                    continue
                row = {k: v.strip() for k, v in raw.items()}
                sku = row["sku"]
                rtype = row["row_type"]
                if len(report["preview"]) < 12:
                    report["preview"].append({
                        "sku": sku[:64], "type": rtype[:16],
                        "category": row["category_slug"][:64],
                    })
                for k, v in row.items():
                    if not safe_cell(v, limit=2048 if k == "reference_image_url" else 200):
                        note("error", line, sku, "Unsafe or overlong field: " + k)
                if not SKU_RE.fullmatch(sku):
                    note("error", line, sku, "SKU must be 3-64 ASCII letters/numbers/._-")
                if sku in sku_lines:
                    note("error", line, sku, "Duplicate SKU; first row=" + str(sku_lines[sku]))
                elif sku:
                    sku_lines[sku] = line
                if sku in existing_skus:
                    note("error", line, sku, "SKU conflicts with existing Woo inventory snapshot")
                if rtype not in policy["row_types"]:
                    note("error", line, sku, "Unsupported row_type")
                    continue
                report["type_counts"][rtype] += 1
                if row["product_status"] != "draft":
                    note("error", line, sku, "All R8 products must remain draft")
                if row["content_origin"] not in ("original", "licensed"):
                    note("error", line, sku, "content_origin must be original or licensed")
                if not SOURCE_RE.fullmatch(row["source_ref"]):
                    note("error", line, sku, "Missing/invalid original supplier or license evidence ID")
                if row["material"] and row["material"] not in policy["materials"]:
                    note("error", line, sku, "Unknown material; use canonical Woo term")
                if row["size_code"] and not CODE_RE.fullmatch(row["size_code"]):
                    note("error", line, sku, "Invalid size_code")
                if not positive_metric(row["height_cm"], "260"):
                    note("error", line, sku, "height_cm must be numeric >0 and <=260")
                if not positive_metric(row["weight_kg"], "250"):
                    note("error", line, sku, "weight_kg must be numeric >0 and <=250")
                image = row["reference_image_url"]
                if not valid_reference_image(image):
                    note("error", line, sku, "Only original YourDoll HTTPS image URL references allowed")
                elif image:
                    note("warning", line, sku, image_warning(image) or "")
                if rtype in ("simple", "variable"):
                    if row["parent_sku"]:
                        note("error", line, sku, "Parent/simple must have blank parent_sku")
                    if len(row["title"]) < 3 or not safe_cell(row["title"], limit=130):
                        note("error", line, sku, "Original product title required (3-130 chars)")
                    if not SLUG_RE.fullmatch(row["slug"]) or len(row["slug"]) > 90:
                        note("error", line, sku, "Product slug must be lowercase hyphenated, <=90 chars")
                    if row["slug"] in parent_slugs and row["slug"]:
                        note("error", line, sku, "Duplicate canonical product slug")
                    if row["slug"]:
                        parent_slugs.add(row["slug"])
                    if row["category_slug"] not in policy["categories"]:
                        note("error", line, sku, "Category not in provisional approved dictionary")
                    if row["brand_slug"] and (not SLUG_RE.fullmatch(row["brand_slug"]) or len(row["brand_slug"]) > 70):
                        note("error", line, sku, "Invalid native Woo brand_slug")
                    if row["fulfillment_mode"] not in policy["fulfillment_modes"]:
                        note("error", line, sku, "Invalid fulfillment_mode")
                    if row["dispatch_region"] not in policy["dispatch_regions"]:
                        note("error", line, sku, "Invalid dispatch_region")
                    days = row["lead_days"]
                    if days and (not days.isascii() or not days.isdecimal() or int(days) > 180):
                        note("error", line, sku, "lead_days must be an integer 0-180")
                    if rtype == "variable":
                        if row["price_usd"] or row["stock_quantity"]:
                            note("error", line, sku, "Variable parent has no price or stock in V1; variations own them")
                    elif not money(row["price_usd"]):
                        note("error", line, sku, "Simple product requires positive USD price <=999999.99, 2 decimals")
                    if rtype == "simple":
                        qty = row["stock_quantity"]
                        if row["fulfillment_mode"] == "local_stock":
                            if not qty.isascii() or not qty.isdecimal() or int(qty) > 1000000:
                                note("error", line, sku, "Local-stock simple requires integer stock_quantity")
                        elif qty:
                            note("error", line, sku, "Factory MTO quantity must be blank; do not invent inventory")
                    product_rows[sku] = row
                else:
                    variations.append((line, row))
                    if not SKU_RE.fullmatch(row["parent_sku"]):
                        note("error", line, sku, "Variation requires valid parent_sku")
                    if row["title"] or row["slug"] or row["category_slug"] or row["brand_slug"]:
                        note("error", line, sku, "Variation must inherit parent title/slug/category/brand")
                    if not (row["material"] or row["size_code"]):
                        note("error", line, sku, "Variation must have material or size_code")
                    for k in ("fulfillment_mode", "dispatch_region", "lead_days", "height_cm", "weight_kg"):
                        if row[k]:
                            note("error", line, sku, "Variation must inherit parent field: " + k)
                    if not money(row["price_usd"]):
                        note("error", line, sku, "Variation requires positive USD price")
                    combo = (row["parent_sku"], row["material"], row["size_code"])
                    if combo in used_combos:
                        note("error", line, sku, "Duplicate parent variation material/size combination")
                    used_combos.add(combo)
    except (UnicodeError, csv.Error) as exc:
        note("error", 0, "", "Unreadable UTF-8 CSV / malformed quoting: " + type(exc).__name__)
        return report

    parents_with_children: set[str] = set()
    for line, row in variations:
        sku, psku = row["sku"], row["parent_sku"]
        parent = product_rows.get(psku)
        if not parent or parent["row_type"] != "variable":
            note("error", line, sku, "Variation parent missing or not row_type=variable in same batch")
            continue
        parents_with_children.add(psku)
        qty = row["stock_quantity"]
        if parent["fulfillment_mode"] == "local_stock":
            if not qty.isascii() or not qty.isdecimal() or int(qty) > 1000000:
                note("error", line, sku, "Local-stock variation requires integer stock_quantity")
        elif qty:
            note("error", line, sku, "Factory MTO variation must not invent stock")
    for sku, row in product_rows.items():
        if row["row_type"] == "variable" and sku not in parents_with_children:
            note("error", sku_lines.get(sku, 0), sku, "Variable parent has no variations")
    report["can_import"] = report["error_count"] == 0 and report["total_rows"] > 0
    return report


def load_existing(path: Path) -> set[str]:
    if not path.is_file() or path.stat().st_size > 10_000_000:
        raise ValueError("Existing SKU snapshot missing/oversized")
    with path.open("r", encoding="utf-8-sig", newline="") as f:
        values = [line.strip() for line in f]
    if values and values[0].lower() == "sku":
        values = values[1:]
    result = set()
    for item in values:
        if item and not SKU_RE.fullmatch(item):
            raise ValueError("Invalid SKU in existing SKU snapshot")
        if item:
            result.add(item)
    return result


def main(argv=None) -> int:
    parser = argparse.ArgumentParser(description="R8 Catalog V1 read-only CSV preflight, never imports products")
    parser.add_argument("csv", type=Path, help="Original/authorized product rows CSV")
    parser.add_argument("--existing-skus", type=Path, default=None,
                        help="Optional newline-separated read-only Woo SKU inventory snapshot")
    parser.add_argument("--report", type=Path, default=None, help="Optional local JSON report path")
    parser.add_argument("--max-rows", type=int, default=10000)
    args = parser.parse_args(argv)
    if args.max_rows < 1 or args.max_rows > 100000:
        parser.error("--max-rows must be between 1 and 100000")
    try:
        known = load_existing(args.existing_skus) if args.existing_skus else set()
        result = preflight(args.csv, existing_skus=known, max_rows=args.max_rows)
    except (OSError, ValueError, json.JSONDecodeError) as exc:
        print(json.dumps({"can_import": False, "error": str(exc)}, indent=2), file=sys.stderr)
        return 2
    report = json.dumps(result, ensure_ascii=False, indent=2) + "\n"
    if args.report:
        try:
            args.report.parent.mkdir(parents=True, exist_ok=True)
            args.report.write_text(report, encoding="utf-8")
        except OSError as exc:
            print("Cannot write report: " + str(exc), file=sys.stderr)
            return 2
    print(report)
    return 0 if result["can_import"] else 1


if __name__ == "__main__":
    raise SystemExit(main())
