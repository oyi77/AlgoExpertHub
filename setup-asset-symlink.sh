#!/bin/bash
# Setup asset symlink - run after git clone/pull
# This script automatically creates the asset symlink if it doesn't exist

set -e

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$REPO_ROOT" || exit 1

# Configuration - target path for asset symlink
# Default: main/public/asset (relative path from repo root)
ASSET_TARGET="${ASSET_TARGET:-main/public/asset}"

echo "🔗 Setting up asset symlink..."

# Check if asset already exists as symlink
if [ -L "asset" ]; then
    CURRENT_TARGET="$(readlink -f asset)"
    EXPECTED_TARGET="$(cd "$REPO_ROOT" && readlink -f "$ASSET_TARGET" 2>/dev/null || echo "")"
    
    if [ "$CURRENT_TARGET" = "$EXPECTED_TARGET" ] || [ "$CURRENT_TARGET" = "$(cd "$REPO_ROOT" && realpath "$ASSET_TARGET" 2>/dev/null || echo "")" ]; then
        echo "✓ Asset symlink already exists and points to correct location"
        exit 0
    else
        echo "⚠ Asset symlink exists but points to different location"
        echo "  Current: $CURRENT_TARGET"
        echo "  Expected: $ASSET_TARGET"
        read -p "Remove and recreate? (y/N) " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            echo "Skipped"
            exit 0
        fi
        rm -f asset
    fi
fi

# If asset exists as directory/file, remove it
if [ -e "asset" ] && [ ! -L "asset" ]; then
    echo "⚠ Removing existing asset directory/file..."
    rm -rf asset
fi

# Check if target exists
if [ ! -d "$ASSET_TARGET" ] && [ ! -L "$ASSET_TARGET" ]; then
    # Try absolute path
    ABS_TARGET="$(cd "$REPO_ROOT" && realpath "$ASSET_TARGET" 2>/dev/null || echo "$ASSET_TARGET")"
    if [ ! -d "$ABS_TARGET" ] && [ ! -L "$ABS_TARGET" ]; then
        echo "❌ Error: Asset target not found: $ASSET_TARGET"
        echo ""
        echo "Please ensure the target directory exists or set ASSET_TARGET environment variable:"
        echo "  export ASSET_TARGET=/path/to/actual/asset"
        echo "  ./setup-asset-symlink.sh"
        exit 1
    fi
    ASSET_TARGET="$ABS_TARGET"
fi

# Create symlink
echo "Creating asset symlink to $ASSET_TARGET..."
ln -s "$ASSET_TARGET" asset

# Verify symlink
if [ -L "asset" ]; then
    echo "✓ Asset symlink created successfully"
    echo "  Target: $(readlink -f asset)"
else
    echo "❌ Failed to create asset symlink"
    exit 1
fi

