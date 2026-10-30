#!/usr/bin/env python3
"""Read-only, LOCAL release artifact integrity check. Never deploys or contacts a host."""
from __future__ import annotations
import argparse
import hashlib
import json
import subprocess
import sys
from pathlib import Path
from zipfile import ZipFile

EXPECTED = {
    "idollmate-discovery/idollmate-discovery.php":
        "48b1392d28bcf9076c712165293cf4d2554bbec5d9d5024f1a5c9e0e79d006c6",
    "idollmate-discovery/assets/discovery.css":
        "dcdbc25b3bba8227f16649ba84753b913dd5ae2bbc56b19628efa4c9c179e86b",
}
ZIP_SHA = "95d1556d8e130730ea7099882c17fd58103f8e5fb8431619bda1f6fbe2e6bc90"
BRANCH = "feat/r8-catalog-os-v1"


def sha(data: bytes) -> str:
    return hashlib.sha256(data).hexdigest()


def main() -> int:
    p = argparse.ArgumentParser(description=__doc__)
    p.add_argument("--zip", type=Path, required=True, dest="archive")
    p.add_argument("--checkout", type=Path, default=Path(__file__).resolve().parents[2])
    args = p.parse_args()
    root = args.checkout.resolve()
    archive = args.archive.resolve()
    def git(*parts: str) -> str:
        return subprocess.check_output(
            ["git", "-C", str(root), *parts], text=True, stderr=subprocess.DEVNULL
        ).strip()

    if git("branch", "--show-current") != BRANCH:
        raise RuntimeError("Not the approved R8 Draft source branch")
    if git("status", "--porcelain"):
        raise RuntimeError("Source checkout is dirty; do not verify an ambiguous package")
    if not archive.is_file() or sha(archive.read_bytes()) != ZIP_SHA:
        raise RuntimeError("Candidate ZIP differs from the sealed preapproval ZIP SHA256")
    with ZipFile(archive) as z:
        names = z.namelist()
        if sorted(names) != sorted(EXPECTED):
            raise RuntimeError(f"Unexpected ZIP membership: {names!r}")
        for name, digest in EXPECTED.items():
            source_file = root / "wordpress" / "plugins" / name
            local_bytes = source_file.read_bytes()
            zip_bytes = z.read(name)
            if sha(local_bytes) != digest or sha(zip_bytes) != digest or local_bytes != zip_bytes:
                raise RuntimeError(f"Source/archive mismatch: {name}")
        php = z.read("idollmate-discovery/idollmate-discovery.php")
        if b"Version: 0.1.2" not in php or b"IDM_DISCOVERY_VERSION = '0.1.2'" not in php:
            raise RuntimeError("Unexpected plugin version")
    print(json.dumps({
        "verdict": "PREAPPROVAL_PACKAGE_INTEGRITY_PASS",
        "release_authorized": False,
        "deployed": False,
        "target": "https://dev.idollmate.com",
        "excluded_site": "https://idollmate.com",
        "draft_branch": BRANCH,
        "git_head": git("rev-parse", "HEAD"),
        "zip_sha256": ZIP_SHA,
        "zip_bytes": archive.stat().st_size,
        "files": EXPECTED,
        "next": "Hostinger theme parity, fresh target readonly preflight and backup require separate Owner PASS",
    }, indent=2))
    return 0


if __name__ == "__main__":
    try:
        sys.exit(main())
    except (RuntimeError, OSError, subprocess.CalledProcessError) as exc:
        print(f"R8_RELEASE_PREFLIGHT_BLOCKED: {exc}", file=sys.stderr)
        sys.exit(2)
