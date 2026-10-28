#!/usr/bin/env python3
"""Bounded private samples from publicly archived HTML; never contacts live source.

Stores representative historical pages privately for UX/template auditing.
Not an all-page or asset-inclusive backup. Respect rights and only run for permitted use.
"""
import argparse
import csv
import gzip
import hashlib
import json
import re
import subprocess
import time
from collections import Counter
from pathlib import Path
from urllib.parse import urlsplit

QUOTAS = {"product": 22, "probable_catalog_category": 14, "taxonomy": 9,
          "editorial": 8, "policy_or_help": 6, "pagination": 8,
          "page_or_editorial": 10}
def capture(url, stamp, timeout=30):
    archive = "https://web.archive.org/web/" + stamp + "id_/" + url
    p = subprocess.run(["curl", "-fLsS", "--compressed", "--max-time",
                        str(timeout), archive], capture_output=True,
                       timeout=timeout + 5)
    data = p.stdout
    if data[:2] == b"\x1f\x8b":
        try: data = gzip.decompress(data)
        except OSError: pass
    head = data[:15000].lower()
    title = re.search(rb"<title[^>]*>(.{0,250}?)</title\s*>", data, re.I|re.S)
    valid = (p.returncode == 0 and len(data) < 15_000_000 and
             (b"<html" in head or b"<!doctype html" in head) and
             title is not None and
             b"just a moment" not in head and b"cf-chl" not in head)
    return valid, data, f"curl={p.returncode}; title={bool(title)}; bytes={len(data)}"

def main(args):
    rows = list(csv.DictReader(Path(args.inventory).expanduser().open(encoding="utf-8")))
    root = Path(args.root).expanduser()
    out = root / "snapshots"
    out.mkdir(parents=True, exist_ok=True)
    mf = out / "manifest.json"
    manifest = json.loads(mf.read_text()) if mf.exists() else []
    existing = {e["source_url"] for e in manifest if e.get("status") == "historical_html_saved"}
    rows = [r for r in rows if r["source_url"] not in existing]
    rows.sort(key=lambda r: (r["historical_capture_utc"], r["source_url"]), reverse=True)
    selected, counts = [], Counter()
    for typ, quota in QUOTAS.items():
        matches = [r for r in rows if r["template_hint"] == typ]
        # Representative breadth: interleave recent and earlier entries instead of only one recent slug group
        picks = matches[::max(1, len(matches)//max(1,quota))][:quota]
        selected.extend(picks)
        counts[typ] = len(picks)
    selected = selected[:args.max_new]
    attempts, saved = [], 0
    for i, r in enumerate(selected, 1):
        stamp = re.sub(r"\D", "", r["historical_capture_utc"])[:14]
        url = r["source_url"]
        if len(stamp) != 14:
            continue
        valid, data, info = capture(url, stamp)
        item = {"source_url":url, "path":urlsplit(url).path,
                "archive_timestamp":stamp, "page_type":r["template_hint"],
                "status":"historical_html_saved" if valid else "archive_unavailable"}
        if valid:
            name = f"ref-{hashlib.sha256(url.encode()).hexdigest()[:20]}.html"
            (out / name).write_bytes(data)
            item.update({"filename":name,"bytes":len(data),
                         "sha256":hashlib.sha256(data).hexdigest()})
            saved += 1
        else:
            item["error"] = info
        attempts.append(item)
        manifest.append(item)
        mf.write_text(json.dumps(manifest, indent=2, ensure_ascii=False)+"\n",encoding="utf-8")
        print("R3",i,"/",len(selected),item["status"],len(data),r["template_hint"],flush=True)
        if i < len(selected): time.sleep(args.delay)
    result={"new_attempted":len(attempts),"new_saved":saved,
            "total_manifest_records":len(manifest),
            "total_success":sum(m.get("status") == "historical_html_saved" for m in manifest),
            "planned_by_type":dict(counts),"all_page_backup":False,
            "disclaimer":"Private historical reference samples only"}
    (root/"r3_collection_evidence.json").write_text(json.dumps(result,indent=2)+"\n")
    print("R3_RESULT",json.dumps(result,ensure_ascii=False),flush=True)

if __name__ == "__main__":
    p=argparse.ArgumentParser()
    p.add_argument("--inventory",required=True)
    p.add_argument("--root",required=True)
    p.add_argument("--max-new",type=int,default=55)
    p.add_argument("--delay",type=float,default=2.1)
    a=p.parse_args()
    if a.max_new < 1 or a.max_new > 150 or a.delay < 2:
        p.error("Max new 1-150, delay >= 2 seconds")
    main(a)
