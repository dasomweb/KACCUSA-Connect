#!/bin/bash
# Pre-deploy validation script
# Usage: bash scripts/validate-deploy.sh

set -e

ERRORS=0
ROOT="$(cd "$(dirname "$0")/.." && pwd)"

echo "=== Pre-deploy Validation ==="
echo ""

# 1. JSON syntax check
echo "[1/3] Checking JSON syntax..."
for f in "$ROOT"/design-tokens/*.json "$ROOT"/theme-config/*.json; do
  if [ -f "$f" ]; then
    if node -e "JSON.parse(require('fs').readFileSync('$f','utf8'))" 2>/dev/null; then
      echo "  OK: $(basename "$f")"
    else
      echo "  FAIL: $(basename "$f")"
      ERRORS=$((ERRORS + 1))
    fi
  fi
done

# 2. PHP syntax check
echo ""
echo "[2/3] Checking PHP syntax..."
find "$ROOT/includes" "$ROOT/plugins" "$ROOT/scripts" -name "*.php" 2>/dev/null | while read -r f; do
  if php -l "$f" > /dev/null 2>&1; then
    echo "  OK: $(basename "$f")"
  else
    echo "  FAIL: $(basename "$f")"
    ERRORS=$((ERRORS + 1))
  fi
done

# 3. Required files check
echo ""
echo "[3/3] Checking required files..."
REQUIRED_FILES=(
  "design-tokens/colors.json"
  "design-tokens/typography.json"
  "design-tokens/spacing.json"
  "scripts/sync-theme-options.php"
  "scripts/import-block-templates.php"
)

for f in "${REQUIRED_FILES[@]}"; do
  if [ -f "$ROOT/$f" ]; then
    echo "  OK: $f"
  else
    echo "  MISSING: $f"
    ERRORS=$((ERRORS + 1))
  fi
done

echo ""
if [ $ERRORS -gt 0 ]; then
  echo "=== VALIDATION FAILED: $ERRORS error(s) ==="
  exit 1
else
  echo "=== ALL CHECKS PASSED ==="
fi
