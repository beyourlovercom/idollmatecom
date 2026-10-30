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
        "0b8a126785a29d99351f196eb19bc58b5fc7dc89d9935aabdc7c3391f192905d",
    "idollmate-discovery/assets/discovery.css":
        "efb9b680c95b6d0c7652ef959904880e1c506fa985b5faf48581127766efbdce",
}
ZIP_SHA = "6053a965238180c70f941b848a27466c840edecbe643123001f429ba32146489"
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
            git_blob = subprocess.check_output(
                ["git", "-C", str(root), "show", "HEAD:wordpress/plugins/" + name]
            )
            zip_bytes = z.read(name)
            if sha(git_blob) != digest or sha(zip_bytes) != digest or git_blob != zip_bytes:
                raise RuntimeError(f"Raw Git blob/archive byte mismatch: {name}")
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
