#!/bin/bash
################################################################################
# GitHub Wiki Deployment Script
################################################################################
# Deploys documentation from docs/ folder to GitHub Wiki
################################################################################

set -e

WIKI_REPO="${1:-git@github.com:yourusername/AlgoExpertHub.wiki.git}"
TEMP_DIR=$(mktemp -d)
DOCS_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)/docs"

echo "=========================================="
echo "GitHub Wiki Deployment"
echo "=========================================="
echo ""

# Clone wiki repository
echo "Cloning wiki repository..."
git clone "$WIKI_REPO" "$TEMP_DIR"

# Clear existing content
echo "Clearing existing wiki content..."
rm -rf "$TEMP_DIR"/*.md

# Copy and rename documentation
echo "Copying documentation..."

# Home page
cp "$DOCS_DIR/README.md" "$TEMP_DIR/Home.md"

# Docker & Deployment
mkdir -p "$TEMP_DIR/Docker-Deployment"
cp "$DOCS_DIR/docker-deployment/DOCKER_DEPLOYMENT_GUIDE.md" "$TEMP_DIR/Docker-Deployment/Complete-Guide.md"

# Trading System
mkdir -p "$TEMP_DIR/Trading-System"
[ -f "$DOCS_DIR/trading-system/trading-execution-flow.md" ] && \
  cp "$DOCS_DIR/trading-system/trading-execution-flow.md" "$TEMP_DIR/Trading-System/Execution-Flow.md"
[ -f "$DOCS_DIR/trading-system/trading-presets.md" ] && \
  cp "$DOCS_DIR/trading-system/trading-presets.md" "$TEMP_DIR/Trading-System/Presets.md"
[ -f "$DOCS_DIR/trading-system/copy-trading-system.md" ] && \
  cp "$DOCS_DIR/trading-system/copy-trading-system.md" "$TEMP_DIR/Trading-System/Copy-Trading.md"

# API Integration
mkdir -p "$TEMP_DIR/API-Integration"
[ -f "$DOCS_DIR/api-integration/multi-channel-signal-ingestion.md" ] && \
  cp "$DOCS_DIR/api-integration/multi-channel-signal-ingestion.md" "$TEMP_DIR/API-Integration/Multi-Channel-Signals.md"
[ -f "$DOCS_DIR/api-integration/openrouter-integration.md" ] && \
  cp "$DOCS_DIR/api-integration/openrouter-integration.md" "$TEMP_DIR/API-Integration/OpenRouter.md"

# Development Guides
mkdir -p "$TEMP_DIR/Development"
[ -f "$DOCS_DIR/development-guides/theme-development.md" ] && \
  cp "$DOCS_DIR/development-guides/theme-development.md" "$TEMP_DIR/Development/Theme-Development.md"
[ -f "$DOCS_DIR/development-guides/database-schema-reference.md" ] && \
  cp "$DOCS_DIR/development-guides/database-schema-reference.md" "$TEMP_DIR/Development/Database-Schema.md"
[ -f "$DOCS_DIR/development-guides/troubleshooting-guide.md" ] && \
  cp "$DOCS_DIR/development-guides/troubleshooting-guide.md" "$TEMP_DIR/Development/Troubleshooting.md"

# Commit and push
cd "$TEMP_DIR"
git add .
git commit -m "Update documentation - $(date +%Y-%m-%d)" || echo "No changes to commit"
git push origin master

echo ""
echo "=========================================="
echo "Wiki deployment complete!"
echo "=========================================="

# Cleanup
rm -rf "$TEMP_DIR"
