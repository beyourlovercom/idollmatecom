#!/usr/bin/env python3
"""Historical URL inventory v2. Metadata only; respects live-site access blocks.

Uses the Internet Archive's public CDX index. Does NOT request the live site,
bypass challenges, or download third-party HTML, media or assets.
"""
import argparse
import csv
import json
import re
import subprocess
import time
from collections import Counter
from datetime import datetime, timezone
from pathlib import Path
from urllib.parse import urlencode, urlsplit, urlunsplit

CDX = "https://web.archive.org/cdx/search/cdx"
EXT = (".css", ".js", ".jpg", ".jpeg", ".png", ".webp", ".gif", ".svg",
       ".ico", ".woff", ".woff2", ".ttf", ".eot", ".otf", ".pdf", ".json",
       ".xml", ".zip", ".mp4", ".webm", ".mp3", ".txt", ".map", ".gz")
BLOCKED = ("/cdn-cgi/", "/wp-content/", "/wp-includes/", "/wp-admin/",
           "/wp-json/", "/wp-login", "/feed/", "/xmlrpc", "/wp-cron")
PRIVATES = ("/my-account/", "/checkout/", "/cart/", "/order-received/")


def page_type(path):
    p = path.lower()
    if p == "/": return "home"
    if re.search(r"/page/\d+/?$", p): return "pagination"
    if p.startswith("/product/"): return "product"
    if p.startswith(("/category/", "/product-category/", "/tag/", "/product-tag/")): return "taxonomy"
    if p.startswith(("/blog/", "/author/", "/news/")): return "editorial"
    if any(x in p for x in ("shipping", "return", "refund", "privacy", "about-us",
                            "contact", "payment", "terms", "warranty", "faq",
                            "affiliate", "track-your-order")): return "policy_or_help"
    if any(x in p for x in ("-doll", "-dolls", "-kg/", "in-stock", "silicone",
                            "manga", "brand", "tpe-", "cm-", "height")):
        return "probable_catalog_category"
    return "page_or_editorial"


def canonical(url):
    p = urlsplit(url)
    if (p.hostname or "").lower() not in ("yourdoll.com", "www.yourdoll.com"):
        return None
    path = re.sub(r"/{2,}", "/", p.path or "/")
    low = path.lower()
    if (low.startswith(BLOCKED) or low.startswith(PRIVATES) or
            low.endswith(EXT) or low.endswith(("/embed/", "/feed/")) or
            len(path) > 260 or "?" in path):
        return None
    if not path.endswith("/") and "." not in path.rsplit("/", 1)[-1]:
        path += "/"
    return urlunsplit(("https", "www.yourdoll.com", path, "", ""))


def parse_cdx(raw):
    obj = json.loads(raw)
    if not isinstance(obj, list) or not obj or obj[0][:3] != ["original", "timestamp", "statuscode"]:
        raise ValueError("Unexpected CDX response")
    resume = None
    if len(obj) >= 3 and obj[-2] == [] and len(obj[-1]) == 1:
        resume = obj[-1][0]
        obj = obj[:-2]
    return obj[1:], resume


def query(params, timeout=75):
    url = CDX + "?" + urlencode(params)
    p = subprocess.run(["curl", "-fsSL", "--retry", "1", "--max-time", str(timeout), url],
                       capture_output=True, timeout=timeout+10)
    if p.returncode:
        raise RuntimeError(p.stderr.decode(errors="replace")[:300] or f"curl code {p.returncode}")
    return parse_cdx(p.stdout)


def load_seed(p, found):
    with p.open(encoding="utf-8") as fh:
        for r in csv.DictReader(fh):
            url = canonical(r.get("source_url", ""))
            if not url: continue
            stamp = (r.get("historical_capture_utc", "").replace("-", "").replace(":", "")
                     .replace("T", "").replace("Z", "")) or r.get("archive_timestamp", "")
            if len(stamp) != 14 or not stamp.isdigit(): continue
            old = found.get(url, {})
            if stamp > old.get("timestamp", ""):
                found[url] = {"url":url, "timestamp":stamp, "source":"seed_2026_10_28"}


def main(args):
    out = Path(args.output).expanduser()
    out.mkdir(parents=True, exist_ok=True)
    found = {}
    if args.seed:
        load_seed(Path(args.seed).expanduser(), found)
    attempts, errors = [], []
    scope = [("site_2023_2024", "yourdoll.com/*", "2023", "2024"),
             ("site_2025_2026", "yourdoll.com/*", "2025", "2026"),
             ("products_2023_2024", "yourdoll.com/product/*", "2023", "2024"),
             ("products_2025_2026", "yourdoll.com/product/*", "2025", "2026")]
    for name, pattern, start, end in scope:
        resume, seen_resumes, scope_new = None, set(), 0
        exhausted = False
        for page in range(1, args.max_batches+1):
            params = {"url":pattern, "output":"json", "fl":"original,timestamp,statuscode",
                      "filter":"statuscode:200", "from":start, "to":end,
                      "collapse":"urlkey", "showResumeKey":"true", "limit":args.batch_size}
            if resume: params["resumeKey"] = resume
            try:
                rows, follow = query(params)
            except Exception as e:
                errors.append({"scope":name, "page":page, "error":str(e)})
                print("ERROR", name, page, str(e)[:150], flush=True)
                break
            new = 0
            for row in rows:
                if len(row) < 3 or row[2] != "200": continue
                url = canonical(row[0])
                if not url: continue
                stamp = row[1]
                old = found.get(url)
                if old is None:
                    new += 1
                    scope_new += 1
                if old is None or stamp > old["timestamp"]:
                    found[url] = {"url":url, "timestamp":stamp, "source":name}
            attempts.append({"scope":name,"page":page,"records":len(rows),
                             "new_urls":new,"continuation":bool(follow)})
            print(name, "batch", page,"records",len(rows),"new",new,
                  "total",len(found),"has_more",bool(follow),flush=True)
            if not follow:
                exhausted=True
                break
            if follow in seen_resumes:
                errors.append({"scope":name, "page":page, "error":"repeated resume key"})
                break
            seen_resumes.add(follow)
            resume=follow
            time.sleep(args.delay)
        attempts.append({"scope":name, "new_url_total":scope_new, "exhausted":exhausted})
    dest=out/"historical_urls_v2.csv"
    with dest.open("w", newline="", encoding="utf-8") as f:
        w=csv.writer(f)
        w.writerow(["source_url","template_hint","historical_capture_utc",
                    "historical_http_status","live_http_status","actual_html_backed_up",
                    "metadata_source"])
        for url, val in sorted(found.items()):
            t=val["timestamp"]
            date=f"{t[:4]}-{t[4:6]}-{t[6:8]}T{t[8:10]}:{t[10:12]}:{t[12:14]}Z"
            w.writerow([url,page_type(urlsplit(url).path),date,"200","not_verified","no",val["source"]])
    stats={"generated_at_utc":datetime.now(timezone.utc).isoformat(),
           "source":"Internet Archive CDX historical records",
           "live_current_url_coverage":"UNKNOWN (live site 403; no current sitemap)",
           "full_site_backup":False,"seed_count":bool(args.seed),
           "distinct_historical_urls":len(found),
           "page_types":dict(Counter(page_type(urlsplit(u).path) for u in found)),
           "attempts":attempts,"errors":errors}
    (out/"r2_collection_evidence.json").write_text(json.dumps(stats,indent=2)+"\n",encoding="utf-8")
    print("R2_RESULT",json.dumps({"count":len(found),"errors":len(errors),
                              "types":stats["page_types"]}),flush=True)

if __name__=="__main__":
    ap=argparse.ArgumentParser()
    ap.add_argument("--seed",help="previous historical CSV")
    ap.add_argument("--output",required=True)
    ap.add_argument("--batch-size",type=int,default=1000)
    ap.add_argument("--max-batches",type=int,default=6)
    ap.add_argument("--delay",type=float,default=2.5)
    args=ap.parse_args()
    if not (1<=args.batch_size<=3000 and 1<=args.max_batches<=30 and args.delay>=2):
        ap.error("batch 1-3000, max batches 1-30, delay >= 2 seconds")
    main(args)
