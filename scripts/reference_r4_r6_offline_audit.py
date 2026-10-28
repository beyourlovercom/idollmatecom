#!/usr/bin/env python3
"""Offline R4-R6 evidence: integrity, template signatures and asset *references*.

This never fetches external assets or contacts YourDoll. Original HTML stays
private; GitHub publication should include only non-copyright source metrics.
"""
import argparse
import csv
import hashlib
import json
import re
from collections import Counter, defaultdict
from datetime import datetime, timezone
from html.parser import HTMLParser
from pathlib import Path
from urllib.parse import urljoin, urlsplit, urlunsplit

ASSET_EXT = {".jpg":"image",".jpeg":"image",".png":"image",".gif":"image",
             ".webp":"image",".svg":"image",".avif":"image",
             ".css":"css",".js":"javascript",".woff":"font",
             ".woff2":"font",".ttf":"font",".otf":"font",
             ".mp4":"video",".webm":"video"}
def clean_url(raw, source):
    if not raw or raw.startswith(("data:","blob:","javascript:","mailto:","#")):
        return None
    try:
        p = urlsplit(urljoin(source, raw.strip()))
    except ValueError:
        return None
    if p.scheme not in ("http","https") or not p.hostname: return None
    return urlunsplit((p.scheme, p.netloc.lower(), p.path, "", ""))

def asset_kind(url, tag, attr, rel):
    suffix = Path(urlsplit(url).path).suffix.lower()
    if suffix in ASSET_EXT: return ASSET_EXT[suffix]
    if tag in ("img","picture"): return "image"
    if tag == "script": return "javascript"
    if tag == "link" and "stylesheet" in rel: return "css"
    if tag in ("video","audio","source"): return "media"
    return None

class ParseHTML(HTMLParser):
    def __init__(self, url):
        super().__init__(convert_charrefs=True)
        self.url, self.title, self.h1, self.h2, self.assets = url, "", [], [], []
        self._text_tag, self._buffer = None, []
        self.selects, self.forms, self.inputs, self.links, self.body_classes = [],[],[],[],[]
        self.ldjson = 0
    def handle_starttag(self, tag, attrs):
        a = dict(attrs)
        if tag in ("title","h1","h2"):
            self._text_tag, self._buffer=tag,[]
        if tag=="body":self.body_classes=a.get("class","").split()
        if tag=="select":self.selects.append(a.get("name") or a.get("id") or "")
        if tag=="form":self.forms.append({"class":a.get("class",""),"action":a.get("action","")})
        if tag=="input":self.inputs.append(a.get("name") or a.get("id") or "")
        if tag=="script" and a.get("type")=="application/ld+json":self.ldjson+=1
        if tag=="a" and len(self.links)<500:
            self.links.append(a.get("href",""))
        pairs=[]
        if tag in ("img","source","video","audio","script","iframe"):
            for k in ("src","data-src","data-lazy-src","poster"):
                if a.get(k):pairs.append((k,a[k]))
        if tag in ("img","source"):
            for k in ("srcset","data-srcset"):
                if a.get(k):
                    for part in a[k].split(","):
                        uri=part.strip().split()[0] if part.strip() else ""
                        if uri:pairs.append((k,uri))
        rel=a.get("rel","")
        if tag=="link" and any(s in rel for s in ("stylesheet","preload","icon")):
            pairs.append(("href",a.get("href","")))
        for attr,raw in pairs:
            url=clean_url(raw,self.url)
            if url:
                typ=asset_kind(url,tag,attr,rel)
                if typ:self.assets.append((url,typ))
    def handle_data(self,data):
        if self._text_tag is not None:self._buffer.append(data)
    def handle_endtag(self,tag):
        if tag == self._text_tag:
            text=" ".join("".join(self._buffer).split())[:180]
            if tag=="title":self.title=text
            elif tag=="h1" and text:self.h1.append(text)
            elif tag=="h2" and text:self.h2.append(text)
            self._text_tag=None
            self._buffer=[]

def main(args):
    root=Path(args.root).expanduser()
    files_dir=root/"snapshots"
    manifest=json.loads((files_dir/"manifest.json").read_text())
    urls=list(csv.DictReader(Path(args.inventory).expanduser().open(encoding="utf-8")))
    inventory={r["source_url"] for r in urls}
    unique, quality, asset_references, fingerprints = set(), [], defaultdict(set), []
    exceptions=[]
    for row in manifest:
        if row.get("status")!="historical_html_saved":continue
        src=row.get("source_url","")
        if src in unique:
            exceptions.append({"url":src,"problem":"duplicate_success_record"})
            continue
        unique.add(src)
        f=files_dir/row["filename"]
        if not f.is_file():
            exceptions.append({"url":src,"problem":"missing_html_file"})
            continue
        raw=f.read_bytes()
        sha=hashlib.sha256(raw).hexdigest()
        integrity=sha==row["sha256"]
        text=raw.decode("utf-8",errors="replace")
        html_valid="<html" in text[:15000].lower() or "<!doctype html" in text[:15000].lower()
        challenge=bool(re.search(r"<title[^>]*>\s*(?:just a moment|attention required)",
                                 text[:12000],re.I))
        parser=ParseHTML(src)
        try:parser.feed(text)
        except Exception as e:exceptions.append({"url":src,"problem":"parse_error","detail":str(e)[:100]})
        if not integrity or not html_valid or challenge or not parser.title:
            exceptions.append({"url":src,"problem":"invalid_hash_or_html","hash_match":integrity,
                               "html_valid":html_valid,"challenge":challenge,"title":bool(parser.title)})
        typ=next((r["template_hint"] for r in urls if r["source_url"]==src),"unmapped")
        flags={key:bool(re.search(regex,text,re.I)) for key,regex in {
            "woocommerce":r"woocommerce","woodmart":r"woodmart",
            "product_gallery":r"woocommerce-product-gallery|product-images",
            "product_variations":r"variations_form|variable-items-wrapper",
            "add_to_cart":r"single_add_to_cart_button|add-to-cart",
            "filters":r"wd-shop-tools|widget_layered_nav|wd-pf",
            "reviews":r"woocommerce-review|reviews_tab",
            "breadcrumbs":r"woocommerce-breadcrumb|yoast-breadcrumb",
            "blog_grid":r"post-grid|blog-post|post-type-archive",
            "faq_schema":r"FAQPage",
        }.items()}
        quality.append({"source_url":src,"type":typ,"filename":row["filename"],
                       "archive_timestamp":row.get("archive_timestamp",""),
                       "bytes":len(raw),"sha256":sha,"hash_ok":integrity,
                       "valid_html":html_valid and not challenge,
                       "title_present":bool(parser.title),"h1_count":len(parser.h1),
                       "asset_refs":len(parser.assets),"h2_count":len(parser.h2)})
        fingerprints.append({"source_url":src,"type":typ,"title":parser.title[:150],
                            "h1":parser.h1[:3],"h2_samples":parser.h2[:12],
                            "selects":parser.selects[:20],
                            "forms_classes":[f["class"] for f in parser.forms[:10]],
                            "feature_flags":flags,
                            "jsonld_tags":parser.ldjson})
        for u,k in parser.assets:asset_references[(u,k)].add(src)
    def write_csv(filename, header, data):
        with (root/filename).open("w",newline="",encoding="utf-8") as f:
            w=csv.DictWriter(f,fieldnames=header)
            w.writeheader();w.writerows(data)
    write_csv("r6_snapshot_qa_private.csv",
              ["source_url","type","filename","archive_timestamp","bytes","sha256",
               "hash_ok","valid_html","title_present","h1_count","asset_refs","h2_count"],quality)
    asset_rows=[{"resource_url":url,"resource_type":kind,
                 "origin":"yourdoll" if (urlsplit(url).hostname or "").endswith("yourdoll.com") else "external",
                 "referenced_by_pages":len(pages),
                 "downloaded_to_private_archive":"no"} for (url,kind),pages in asset_references.items()]
    write_csv("r4_asset_reference_manifest_private.csv",
              ["resource_url","resource_type","origin","referenced_by_pages","downloaded_to_private_archive"],
              sorted(asset_rows,key=lambda r:(r["resource_type"],r["resource_url"])))
    (root/"r5_template_evidence_private.json").write_text(
        json.dumps(fingerprints,indent=2,ensure_ascii=False)+"\n",encoding="utf-8")
    counts=Counter(r["template_hint"] for r in urls)
    kind_counts=Counter(x["resource_type"] for x in asset_rows)
    types_sampled=Counter(r["type"] for r in quality)
    asset_seen=len(asset_rows)
    sampled=len(quality)
    out={"generated_at_utc":datetime.now(timezone.utc).isoformat(),
         "inventory_historical_urls":len(inventory),
         "private_historical_html_snapshots":sampled,
         "snapshots_with_valid_hash_and_html":sum(r["hash_ok"] and r["valid_html"] and r["title_present"] for r in quality),
         "snapshot_sampling_fraction_of_historical_url_inventory":round(sampled/max(1,len(inventory)),5),
         "urls_not_html_backed_up":max(0,len(inventory)-sampled),
         "sources_by_template_hint":dict(counts),
         "snapshots_by_template_hint":dict(types_sampled),
         "unique_static_resource_references":asset_seen,
         "unique_static_resource_references_by_type":dict(kind_counts),
         "resource_file_copies":0,
         "exceptions":exceptions,
         "full_current_site_backup":False,
         "live_source_403":True,
         "conclusion":"Integrity of bounded private historical samples only; source completeness and static files unverified."}
    (root/"r6_acceptance_evidence.json").write_text(json.dumps(out,indent=2,ensure_ascii=False)+"\n")
    print("R6_RESULT",json.dumps({k:out[k] for k in [
          "inventory_historical_urls","private_historical_html_snapshots",
          "snapshots_with_valid_hash_and_html","unique_static_resource_references",
          "unique_static_resource_references_by_type","resource_file_copies",
          "full_current_site_backup","exceptions"]},ensure_ascii=False),flush=True)
if __name__=="__main__":
    ap=argparse.ArgumentParser()
    ap.add_argument("--inventory",required=True)
    ap.add_argument("--root",required=True)
    main(ap.parse_args())
