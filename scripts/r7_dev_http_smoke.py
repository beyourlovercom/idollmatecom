#!/usr/bin/env python3
"""Read-only R7 Dev HTTP checks. HTML and headers only; never downloads images."""
import json
import re
import subprocess
from datetime import datetime, timezone
from urllib.parse import urljoin

ORIGIN = "https://dev.idollmate.com"
ROUTES = {
    "/": {"expect": 200, "noindex": True},
    "/idollmate-home-uat/": {
        "expect": 200, "noindex": True,
        "tokens": ["iDollmate Collection", "Shop by category", "Discover the collection"],
    },
    "/shop/": {"expect": 200, "noindex": True},
    "/cart/": {"expect": 200, "noindex": True},
    "/checkout/": {"expect": 200, "noindex": True},
    "/my-account/": {"expect": 200, "noindex": True},
    "/?p=13": {"expect": 404},
    "/?p=15": {"expect": 404},
    "/wp-content/plugins/idollmate-home/assets/home.css": {
        "expect": 200, "tokens": [".idm-home__hero"],
    },
}

def curl(url):
    p = subprocess.run(
        ["curl", "-sSL", "--proto", "=https", "--tlsv1.2",
         "--max-time", "16", "-w", "\nIDM_CURL_STATUS:%{http_code}\n", url],
        capture_output=True, timeout=21,
    )
    text = p.stdout.decode("utf-8", "replace")
    m = re.search(r"\nIDM_CURL_STATUS:(\d{3})\s*$", text)
    body = text[:m.start()] if m else text
    return p.returncode, int(m.group(1)) if m else 0, body, p.stderr.decode("utf-8", "replace")[:140]

def check():
    passed = 0
    rows = []
    for route, rules in ROUTES.items():
        rc, status, body, err = curl(urljoin(ORIGIN, route))
        flags = []
        if rc: flags.append("curl_nonzero")
        if status != rules["expect"]: flags.append("status_mismatch")
        if rules.get("noindex"):
            tag = re.search(r'<meta\b[^>]*\bname=["\']robots["\'][^>]*>', body, re.I)
            if not tag or not all(t in tag.group(0).lower() for t in ("noindex", "nofollow")):
                flags.append("missing_noindex_nofollow")
        for token in rules.get("tokens", []):
            if token.lower() not in body.lower(): flags.append("missing:" + token)
        rows.append({"route": route, "http_status": status,
                     "pass": not flags, "failures": flags, "error": err if rc else ""})
        if not flags: passed += 1
        print(f'{"PASS" if not flags else "FAIL"} {route} HTTP={status} {",".join(flags)}', flush=True)
    summary = {
        "at_utc": datetime.now(timezone.utc).isoformat(),
        "mode": "read-only-HTTP; no image request; no checkout submission",
        "pass": passed, "total": len(ROUTES), "results": rows,
    }
    print("R7_HTTP_SMOKE", json.dumps(summary, ensure_ascii=False), flush=True)
    return 0 if passed == len(ROUTES) else 1

if __name__ == "__main__":
    raise SystemExit(check())
