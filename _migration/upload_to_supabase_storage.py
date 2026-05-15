#!/usr/bin/env python3
"""
Upload Kamgus production uploads to Supabase Storage.

Source: /Users/teamsolutionsslatam/Desktop/Kamgus/_hosting_snapshot/apikamgusv2/storage/app/public/
Target: bucket kamgus-public (preserves folder structure for URL compatibility)

What we migrate:
  - profiles/    → kamgus-public/profiles/    (driver docs, vehicles, service photos)
  - app-usuario/ → kamgus-public/app-usuario/ (catalog images)
  - support/     → kamgus-public/support/     (support attachments)

What we skip:
  - test1/                  (test data not needed in prod)
  - *.json files at root    (old Firebase keys, rotated already)

Auth: SUPABASE_SERVICE_ROLE_KEY (paste below or set via env var).

Run:  python3 upload_to_supabase_storage.py
"""

import os
import sys
import mimetypes
from pathlib import Path
from concurrent.futures import ThreadPoolExecutor, as_completed
import urllib.request
import urllib.error

# ---------------------------------------------------------------------------
SUPABASE_URL = "https://xobgiiyizjtvezmshyvf.supabase.co"
BUCKET = "kamgus-public"
SOURCE_ROOT = Path("/Users/teamsolutionsslatam/Desktop/Kamgus/_hosting_snapshot/apikamgusv2/storage/app/public")
INCLUDE_DIRS = ["profiles", "app-usuario", "support"]
SKIP_DIRS = ["test1"]
PARALLEL_UPLOADS = 8
# ---------------------------------------------------------------------------

SERVICE_ROLE_KEY = os.environ.get("SUPABASE_SERVICE_ROLE_KEY", "").strip()
if not SERVICE_ROLE_KEY:
    print("ERROR: SUPABASE_SERVICE_ROLE_KEY env var not set", file=sys.stderr)
    sys.exit(1)


def collect_files():
    files = []
    for d in INCLUDE_DIRS:
        root = SOURCE_ROOT / d
        if not root.exists():
            print(f"  WARN: {d}/ not found in snapshot, skipping")
            continue
        for p in root.rglob("*"):
            if p.is_file() and not p.name.startswith("."):
                rel = p.relative_to(SOURCE_ROOT)
                files.append((p, str(rel)))
    return files


def upload_one(local_path: Path, remote_key: str) -> tuple[bool, str, int]:
    """Returns (ok, key, size)."""
    url = f"{SUPABASE_URL}/storage/v1/object/{BUCKET}/{remote_key}"
    mime, _ = mimetypes.guess_type(str(local_path))
    if mime is None:
        mime = "application/octet-stream"

    try:
        size = local_path.stat().st_size
        with open(local_path, "rb") as f:
            body = f.read()
        req = urllib.request.Request(
            url,
            data=body,
            method="PUT",
            headers={
                "Authorization": f"Bearer {SERVICE_ROLE_KEY}",
                "Content-Type": mime,
                "x-upsert": "true",
            },
        )
        with urllib.request.urlopen(req, timeout=60) as resp:
            if 200 <= resp.status < 300:
                return True, remote_key, size
            return False, f"{remote_key} HTTP {resp.status}", size
    except urllib.error.HTTPError as e:
        body = e.read().decode("utf-8", errors="replace")[:200]
        return False, f"{remote_key} HTTP {e.code}: {body}", 0
    except Exception as e:
        return False, f"{remote_key} EXC: {e}", 0


def main():
    print(f"Source: {SOURCE_ROOT}")
    print(f"Target: {SUPABASE_URL}/storage/v1/object/{BUCKET}/<path>")
    print(f"Including: {INCLUDE_DIRS}")
    print(f"Skipping:  {SKIP_DIRS}")
    print()

    files = collect_files()
    total = len(files)
    total_size = sum(p.stat().st_size for p, _ in files)
    print(f"Files to upload: {total:,}")
    print(f"Total size:      {total_size / (1024 * 1024):,.1f} MB")
    print()

    if not files:
        print("Nothing to upload.")
        return

    ok_count = 0
    fail_count = 0
    bytes_done = 0
    errors = []

    with ThreadPoolExecutor(max_workers=PARALLEL_UPLOADS) as exe:
        futures = {exe.submit(upload_one, p, k): (p, k) for p, k in files}
        for i, fut in enumerate(as_completed(futures), 1):
            ok, msg, size = fut.result()
            if ok:
                ok_count += 1
                bytes_done += size
            else:
                fail_count += 1
                errors.append(msg)
            if i % 100 == 0 or i == total:
                pct = (i / total) * 100
                mb = bytes_done / (1024 * 1024)
                print(f"  [{i:>5}/{total}] {pct:5.1f}%  uploaded {mb:>7.1f} MB  ok={ok_count} fail={fail_count}")

    print()
    print(f"Done. {ok_count:,} ok / {fail_count:,} failed.")
    if errors:
        print("First 10 errors:")
        for e in errors[:10]:
            print(f"  - {e}")


if __name__ == "__main__":
    main()
