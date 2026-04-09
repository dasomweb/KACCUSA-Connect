#!/bin/bash
# Server Setup Script for SiteGround
# Run this once on the server after SSH access is configured.
#
# Usage: bash scripts/setup-server.sh

set -e

WP_PATH=~/public_html
PLUGIN_SOURCE="$WP_PATH/plugins/dw-core"
PLUGIN_TARGET="$WP_PATH/wp-content/plugins/dw-core"

echo "=== KACCUSA-Connect Server Setup ==="

# 1. Check WP-CLI
echo ""
echo "[1/5] Checking WP-CLI..."
if command -v wp &> /dev/null; then
    echo "  WP-CLI version: $(wp --version)"
else
    echo "  ERROR: WP-CLI not found"
    exit 1
fi

# 2. Check WordPress
echo ""
echo "[2/5] Checking WordPress..."
if wp core is-installed --path="$WP_PATH" 2>/dev/null; then
    echo "  WordPress version: $(wp core version --path="$WP_PATH")"
else
    echo "  ERROR: WordPress not found at $WP_PATH"
    exit 1
fi

# 3. Symlink plugin
echo ""
echo "[3/5] Setting up dw-core plugin symlink..."
if [ -d "$PLUGIN_SOURCE" ]; then
    if [ -L "$PLUGIN_TARGET" ]; then
        echo "  Symlink already exists"
    elif [ -d "$PLUGIN_TARGET" ]; then
        echo "  WARNING: Directory already exists at target, skipping symlink"
    else
        ln -s "$PLUGIN_SOURCE" "$PLUGIN_TARGET"
        echo "  Symlink created: $PLUGIN_TARGET -> $PLUGIN_SOURCE"
    fi
else
    echo "  ERROR: Plugin source not found at $PLUGIN_SOURCE"
    exit 1
fi

# 4. Activate plugin
echo ""
echo "[4/5] Activating dw-core plugin..."
wp plugin activate dw-core --path="$WP_PATH" 2>/dev/null || echo "  Already active or activation failed"

# 5. Initial sync
echo ""
echo "[5/5] Running initial sync..."
wp eval-file scripts/sync-theme-options.php --path="$WP_PATH"
wp eval-file scripts/import-block-templates.php --path="$WP_PATH"
wp rewrite flush --path="$WP_PATH"
wp cache flush --path="$WP_PATH"

echo ""
echo "=== Setup Complete ==="
echo "Verify: curl -s $(wp option get siteurl --path="$WP_PATH")/wp-json/dw/v1/health"
