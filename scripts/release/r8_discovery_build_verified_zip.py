#!/usr/bin/env python3
"""Deterministic, PRE-APPROVAL zip from raw Git blobs; never deploys anything.

Raw Git blobs have LF line endings. Windows autocrlf checkouts do not, so this
build deliberately avoids copying working-tree bytes into a Hostinger release.
"""
from __future__ import annotations
import argparse
import hashlib
from pathlib import Path
import subprocess
from zipfile import ZipFile, ZipInfo, ZIP_DEFLATED

BRANCH = "feat/r8-catalog-os-v1"
SOURCES = {
    "idollmate-discovery/idollmate-discovery.php":
        "0b8a126785a29d99351f196eb19bc58b5fc7dc89d9935aabdc7c3391f192905d",
    "idollmate-discovery/assets/discovery.css":
        "efb9b680c95b6d0c7652ef959904880e1c506fa985b5faf48581127766efbdce",
}


def git(root: Path, *args: str) -> bytes:
    return subprocess.check_output(["git", "-C", str(root), *args])


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--output", required=True, type=Path)
    parser.add_argument("--checkout", type=Path, default=Path(__file__).resolve().parents[2])
    args = parser.parse_args()
    root = args.checkout.resolve()
    dest = args.output.resolve()

    if git(root, "branch", "--show-current").decode().strip() != BRANCH:
        raise RuntimeError("Wrong Draft branch")
    if git(root, "status", "--porcelain").strip():
        raise RuntimeError("Dirty checkout")
    if dest.exists():
        raise RuntimeError("Output already exists; refusing overwrite")
    dest.parent.mkdir(parents=True, exist_ok=True)

    with ZipFile(dest, "x") as z:
        for name in sorted(SOURCES):
            data = git(root, "show", "HEAD:wordpress/plugins/" + name)
            digest = hashlib.sha256(data).hexdigest()
            if digest != SOURCES[name]:
                raise RuntimeError("SOURCE BLOB DRIFT: " + name)
            if name.endswith(".php") and b"Version: 0.1.2" not in data:
                raise RuntimeError("Plugin version mismatch")
            info = ZipInfo(name, date_time=(1980, 1, 1, 0, 0, 0))
            info.compress_type = ZIP_DEFLATED
            info.create_system = 3
            info.external_attr = 0o100644 << 16
            z.writestr(info, data)

    digest = hashlib.sha256(dest.read_bytes()).hexdigest()
    print("R8_LF_RELEASE_ZIP_CREATED_PREAPPROVAL_ONLY")
    print("SOURCE_HEAD=" + git(root, "rev-parse", "HEAD").decode().strip())
    print("ZIP_BYTES=" + str(dest.stat().st_size))
    print("ZIP_SHA256=" + digest)
    for path, value in SOURCES.items():
        print("FILE_SHA256=" + path + " " + value)


if __name__ == "__main__":
    main()
