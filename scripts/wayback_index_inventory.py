#!/usr/bin/env python3
"""Export publicly indexed historical YourDoll URLs, never bypass the live site."""
import argparse
from collections import Counter
import csv
import json
from pathlib import Path
import subprocess
import time
from urllib.parse import urlencode, urlsplit, urlunsplit

CDX = "https://web.archive.org/cdx/search/cdx"
ALLOWED = {"yourdoll.com", "www.yourdoll.com"}

def classify(url):
    path = urlsplit(url).path.lower()
    if path in ("", "/"):
        return "homepage"
    if "/page/" in path:
        return "pagination"
    if path.startswith(("/product/", "/products/")):
        return "product"
    if path.startswith(("/product-category/", "/product-tag/", "/category/", "/collections/")):
        return "taxonomy"
    if path.startswith(("/blog/", "/news/", "/blogs/")):
        return "editorial"
    if any(x in path for x in ("shipping", "return", "refund", "privacy", "about", "contact",
                                "payment", "terms", "warranty", "faq", "affiliate")):
        return "information"
    if path.endswith(("-sex-doll/", "-sex-dolls/", "-dolls/")):
        return "probable_taxonomy"
    return "unclassified"

def canonical(url):
    p = urlsplit(url)
    host = (p.hostname or "").lower()
    if host not in ALLOWED:
        return None
    # No URL parameters or fragments: inventory canonical page routes only
    path = p.path or "/"
    if path.lower().endswith((".jpg", ".jpeg", ".webp", ".png", ".gif", ".svg", ".pdf",
                              ".css", ".js", ".xml", ".json", ".zip", ".mp4")):
        return None
    return urlunsplit(("https", "www.yourdoll.com", path, "", ""))

def cdx_query(params, timeout=90):
    url = CDX + "?" + urlencode(params)
    p = subprocess.run(["curl", "-fLsS", "--retry", "1", "--max-time", str(timeout), url],
                       stdout=subprocess.PIPE, stderr=subprocess.PIPE, timeout=timeout + 12)
    if p.returncode != 0:
        raise RuntimeError(f"CDX query failed ({p.returncode}): {p.stderr.decode(errors='replace')[:220]}")
    try:
        data = json.loads(p.stdout)
    except ValueError as e:
        raise RuntimeError("Archive returned invalid JSON") from e
    if not isinstance(data, list) or not data or data[0][:2] != ["original", "timestamp"]:
        raise RuntimeError("Unexpected archive metadata fields")
    return data

def run(args):
    output = Path(args.output).expanduser()
    output.mkdir(parents=True, exist_ok=True)
    url_rows, seen_keys, resume = {}, set(), None
    stats = {"batches_completed": 0, "source": CDX, "access": "public historical metadata",
             "live_site": "not crawled; live YourDoll endpoints returned HTTP 403",
             "claim": "Historical indexed URL inventory, NOT a full site backup"}
    for batch in range(args.max_batches):
        params = {"url": "yourdoll.com/*", "output": "json",
                  "fl": "original,timestamp,statuscode", "filter": "statuscode:200",
                  "from": args.from_year, "to": args.to_year,
                  "collapse": "urlkey", "showResumeKey": "true", "limit": args.batch_size}
        if resume:
            params["resumeKey"] = resume
        data = cdx_query(params)
        following = None
        if len(data) >= 3 and data[-2] == [] and len(data[-1]) == 1:
            following = data[-1][0]
            data = data[:-2]
        for row in data[1:]:
            if len(row) < 3 or row[2] != "200":
                continue
            url = canonical(row[0])
            if not url:
                continue
            previous = url_rows.get(url)
            if previous is None or row[1] > previous["archive_timestamp"]:
                url_rows[url] = {"source_url": url, "page_type": classify(url),
                                 "archive_timestamp": row[1], "archive_status": row[2],
                                 "live_status": "not verified", "html_saved": "no"}
        stats["batches_completed"] += 1
        print(f"batch={batch + 1} unique_urls={len(url_rows)} more={bool(following)}", flush=True)
        if not following or following in seen_keys:
            resume = None
            break
        seen_keys.add(following)
        resume = following
        if batch + 1 < args.max_batches:
            time.sleep(args.delay)
    fields = ["source_url", "page_type", "archive_timestamp", "archive_status",
              "live_status", "html_saved"]
    with (output / "historical_urls.csv").open("w", newline="", encoding="utf-8") as fh:
        w = csv.DictWriter(fh, fieldnames=fields)
        w.writeheader()
        w.writerows(sorted(url_rows.values(), key=lambda x: x["source_url"]))
    stats.update({"unique_urls": len(url_rows), "type_counts": dict(Counter(
        x["page_type"] for x in url_rows.values())), "has_more": bool(resume),
        "date_range": f"{args.from_year}-{args.to_year}"})
    (output / "inventory_summary.json").write_text(json.dumps(stats, indent=2) + "\n")
    if resume:
        (output / "RESUME_KEY_PRIVATE.txt").write_text(resume + "\n")
    print("OUTPUT", output, json.dumps(stats, sort_keys=True), flush=True)

if __name__ == "__main__":
    ap = argparse.ArgumentParser()
    ap.add_argument("--from-year", default="2025")
    ap.add_argument("--to-year", default="2026")
    ap.add_argument("--batch-size", type=int, default=500)
    ap.add_argument("--max-batches", type=int, default=4)
    ap.add_argument("--delay", type=float, default=3.0)
    ap.add_argument("--output", default="reference-archive/yourdoll")
    a = ap.parse_args()
    if not 1 <= a.batch_size <= 3000 or not 1 <= a.max_batches <= 20 or a.delay < 2:
        ap.error("Use batch-size 1-3000, max-batches 1-20, delay >= 2s")
    run(a)
