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
ASSET_EXISTS=false
if [ -L "asset" ]; then
    CURRENT_TARGET="$(readlink -f asset)"
    EXPECTED_TARGET="$(cd "$REPO_ROOT" && readlink -f "$ASSET_TARGET" 2>/dev/null || echo "")"
    
    if [ "$CURRENT_TARGET" = "$EXPECTED_TARGET" ] || [ "$CURRENT_TARGET" = "$(cd "$REPO_ROOT" && realpath "$ASSET_TARGET" 2>/dev/null || echo "")" ]; then
        echo "✓ Asset symlink already exists and points to correct location"
        ASSET_EXISTS=true
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
        ASSET_EXISTS=false
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

# Create symlink (only if it doesn't exist)
if [ "$ASSET_EXISTS" = false ]; then
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
fi

# Setup CSS and JS symlinks inside asset folder
echo ""
echo "🔗 Setting up CSS and JS symlinks..."

# Get the actual asset directory path (follow symlink if needed)
ASSET_DIR="$(cd "$REPO_ROOT" && readlink -f asset 2>/dev/null || echo "$REPO_ROOT/main/public/asset")"

# Ensure asset directory exists
if [ ! -d "$ASSET_DIR" ] && [ ! -L "$ASSET_DIR" ]; then
    echo "⚠ Warning: Asset directory not found: $ASSET_DIR"
    echo "  Skipping CSS/JS symlink setup"
else
    # CSS symlink (relative path from inside asset directory)
    if [ -L "$ASSET_DIR/css" ]; then
        echo "✓ CSS symlink already exists"
    elif [ -e "$ASSET_DIR/css" ]; then
        echo "⚠ Removing existing css directory in asset..."
        rm -rf "$ASSET_DIR/css"
        (cd "$ASSET_DIR" && ln -s ../css css)
        echo "✓ CSS symlink created"
    else
        (cd "$ASSET_DIR" && ln -s ../css css)
        echo "✓ CSS symlink created"
    fi

    # JS symlink (relative path from inside asset directory)
    if [ -L "$ASSET_DIR/js" ]; then
        echo "✓ JS symlink already exists"
    elif [ -e "$ASSET_DIR/js" ]; then
        echo "⚠ Removing existing js directory in asset..."
        rm -rf "$ASSET_DIR/js"
        (cd "$ASSET_DIR" && ln -s ../js js)
        echo "✓ JS symlink created"
    else
        (cd "$ASSET_DIR" && ln -s ../js js)
        echo "✓ JS symlink created"
    fi
fi

echo ""
echo "✅ All symlinks set up successfully!"

