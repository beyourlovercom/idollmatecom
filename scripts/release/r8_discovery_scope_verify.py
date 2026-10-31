#!/usr/bin/env python3
"""Fail closed unless Discovery 0.1.2 changes ONLY its admin QA resolver and version.

Compares actual old 0.1.1 Git blob to current plugin byte-for-byte after
reversing the exact, approved admin-only deltas. Read-only, no network.
"""
from __future__ import annotations
import difflib
import hashlib
import subprocess
import sys
from pathlib import Path

EXPECTED_CANDIDATE_SHA256 = "48b1392d28bcf9076c712165293cf4d2554bbec5d9d5024f1a5c9e0e79d006c6"
OLD_REF = "a417475"
FILE = "wordpress/plugins/idollmate-discovery/idollmate-discovery.php"


def replace_exact(text: str, old: str, replacement: str) -> str:
    if text.count(old) != 1:
        raise ValueError(f"Approved delta not found exactly once: {old[:90]!r}")
    return text.replace(old, replacement, 1)


def verify(old: str, current: str) -> None:
    if "Version: 0.1.1" not in old or "Version: 0.1.2" not in current:
        raise ValueError("Version baseline/candidate mismatch")
    normalized = replace_exact(current, " * Version: 0.1.2", " * Version: 0.1.1")
    normalized = replace_exact(normalized,
                               "const IDM_DISCOVERY_VERSION = '0.1.2';",
                               "const IDM_DISCOVERY_VERSION = '0.1.1';")
    marker = "/**\n * Validate the exact synthetic QA batch in THIS Woo database."
    start = normalized.index(marker)
    end = normalized.index("function idm_discovery_admin_qa(): void {", start)
    new_helper = normalized[start:end]
    if new_helper.count("function idm_discovery_qa_batch_ids(") != 1:
        raise ValueError("Noncanonical admin QA resolver helper")
    if len(new_helper) > 5000:
        raise ValueError("Unexpectedly large injected resolver")
    normalized = normalized[:start] + normalized[end:]
    normalized = replace_exact(normalized,
        "    $qa_ids=idm_discovery_qa_batch_ids($journal,$batch);\n"
        "    if (!$qa_ids) {",
        "    if (!is_array($journal) || ($journal['state']??null)!=='applied'\n"
        "        || ($journal['created_count']??null)!==4\n"
        "        || ($journal['ids']??null)!==array(32,33,34,35)) {")
    normalized = replace_exact(normalized,
        "foreach (array($qa_ids['simple'],$qa_ids['parent']) as $pid) {",
        "foreach (array(32,33) as $pid) {")
    if normalized != old:
        diff = "".join(difflib.unified_diff(
            old.splitlines(True), normalized.splitlines(True),
            fromfile="original_0.1.1", tofile="candidate_normalized"))
        raise ValueError("Frontend or other unapproved source delta remains:\n" + diff[:3500])


def main() -> int:
    root = Path(__file__).resolve().parents[2]
    old = subprocess.check_output(
        ["git", "-C", str(root), "show", f"{OLD_REF}:{FILE}"],
        stderr=subprocess.DEVNULL).decode("utf-8")
    raw = (root / FILE).read_bytes()
    if hashlib.sha256(raw).hexdigest() != EXPECTED_CANDIDATE_SHA256:
        raise ValueError("Candidate file differs from sealed 0.1.2 PHP bytes")
    current = raw.decode("utf-8").replace("\r\n", "\n")
    verify(old, current)
    print("R8_DISCOVERY_012_SCOPE_PASS admin QA SKU/journal resolver only, plugin version only; ALL OTHER PHP BYTES unchanged from R8 0.1.1")
    print("Theme, frontend archive hooks, checkout, product detail rendering, scripts and CSS source changes=ZERO")
    print("Result supports narrowly scoped Owner risk review, NOT production approval or target theme parity proof.")
    return 0


if __name__ == "__main__":
    try:
        sys.exit(main())
    except (ValueError, OSError, subprocess.CalledProcessError) as exc:
        print(f"R8_DISCOVERY_012_SCOPE_BLOCKED: {exc}", file=sys.stderr)
        sys.exit(2)
