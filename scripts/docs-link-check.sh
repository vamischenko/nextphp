#!/usr/bin/env bash
set -euo pipefail

python3 - <<'PY'
import re
from pathlib import Path

broken = False
for path in Path("docs").rglob("*.md"):
    text = path.read_text(encoding="utf-8")
    for target in re.findall(r"\]\(([^)]+)\)", text):
        if target.startswith(("http", "#")):
            continue
        resolved = (path.parent / target).resolve()
        if not resolved.exists():
            print(f"Broken link in {path} -> {target}")
            broken = True

raise SystemExit(1 if broken else 0)
PY

echo "docs link check passed"
