#!/usr/bin/env bash
set -euo pipefail

if ! python3 - <<'PY'
from pathlib import Path
content = Path("docs/mkdocs.yml").read_text(encoding="utf-8")
raise SystemExit(0 if "nav:" in content else 1)
PY
then
  echo "mkdocs nav section is missing"
  exit 1
fi

if ! python3 - <<'PY'
from pathlib import Path
content = Path("docs/mkdocs.yml").read_text(encoding="utf-8")
raise SystemExit(0 if "compatibility-matrix.md" in content else 1)
PY
then
  echo "compatibility matrix is missing in nav"
  exit 1
fi

echo "docs nav check passed"
