#!/usr/bin/env python3
"""Inventory a permitted public reference site's sitemap, without bypassing access controls."""
import argparse
import csv
import hashlib
import pathlib
import time
import urllib.error
import urllib.parse
import urllib.request
import xml.etree.ElementTree as ET
from urllib import robotparser

UA = "iDollmate-ReferenceAudit/1.0 (+manual research; no security bypass)"
MAX_BYTES = 5_000_000

def download(url):
    req = urllib.request.Request(url, headers={"User-Agent": UA})
    try:
        with urllib.request.urlopen(req, timeout=15) as response:
            data = response.read(MAX_BYTES + 1)
            if len(data) > MAX_BYTES:
                raise RuntimeError("Oversized response: " + url)
            content_type = response.headers.get("Content-Type", "")
    except urllib.error.HTTPError as e:
        raise RuntimeError(f"Access blocked ({e.code}) at {url}; stop and obtain permission/export") from e
    text = data[:2000].decode("utf-8", "ignore").lower()
    if "just a moment" in text and "cloudflare" in text or "cf-chl" in text or "captcha" in text:
        raise RuntimeError("Challenge page encountered; stopping without bypass: " + url)
    return data, content_type

def same_site(url, domain):
    host = urllib.parse.urlparse(url).hostname or ""
    return host == domain or host == "www." + domain or domain == "www." + host

def inventory(base, output, save_html=False, delay=2.0, limit=5000):
    base = base.rstrip("/")
    domain = urllib.parse.urlparse(base).hostname
    robots_url = urllib.parse.urljoin(base + "/", "/robots.txt")
    robots_bytes, _ = download(robots_url)  # failure is a hard stop
    lines = robots_bytes.decode("utf-8", "replace").splitlines()
    rp = robotparser.RobotFileParser()
    rp.parse(lines)
    if not rp.can_fetch(UA, base + "/"):
        raise RuntimeError("robots.txt disallows access; stopping")
    sitemaps = [line.split(":", 1)[1].strip() for line in lines
                if line.lower().startswith("sitemap:")]
    if not sitemaps:
        sitemaps = [base + "/sitemap_index.xml", base + "/sitemap.xml"]
    output.mkdir(parents=True, exist_ok=True)
    queue, seen_xml, urls = list(sitemaps), set(), set()
    while queue and len(seen_xml) < 100 and len(urls) < limit:
        source = queue.pop(0)
        if source in seen_xml or not same_site(source, domain) or not rp.can_fetch(UA, source):
            continue
        seen_xml.add(source)
        time.sleep(delay)
        try:
            raw, _ = download(source)
            root = ET.fromstring(raw)
        except (RuntimeError, ET.ParseError):
            if len(seen_xml) == 1:
                raise
            continue
        typ = root.tag.rsplit("}", 1)[-1].lower()
        locs = [el.text.strip() for el in root.iter()
                if el.tag.rsplit("}", 1)[-1] == "loc" and el.text]
        if typ == "sitemapindex":
            queue.extend(locs)
        elif typ == "urlset":
            urls.update(u for u in locs if same_site(u, domain) and rp.can_fetch(UA, u))
    pages = sorted(urls)[:limit]
    with (output / "urls.csv").open("w", newline="", encoding="utf-8") as fh:
        writer = csv.writer(fh)
        writer.writerow(["source_url", "capture_status", "local_file"])
        for url in pages:
            file_name, status = "", "indexed"
            if save_html:
                time.sleep(delay)
                raw, typ = download(url)
                if "html" not in typ:
                    status = "non-html"
                else:
                    file_name = hashlib.sha256(url.encode()).hexdigest()[:20] + ".html"
                    (output / file_name).write_bytes(raw)
                    status = "saved-private"
            writer.writerow([url, status, file_name])
    print(f"Discovered {len(pages)} URLs from {len(seen_xml)} sitemaps; local destination: {output}")

if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument("--site", default="https://www.yourdoll.com")
    parser.add_argument("--output", default="reference-archive/yourdoll")
    parser.add_argument("--save-html", action="store_true",
                        help="Only for materials you are authorized to retain, never commit outputs")
    parser.add_argument("--delay", type=float, default=2.0)
    parser.add_argument("--max-pages", type=int, default=5000)
    args = parser.parse_args()
    if args.delay < 2:
        parser.error("Minimum crawl delay is 2 seconds")
    inventory(args.site, pathlib.Path(args.output),
              args.save_html, args.delay, args.max_pages)
