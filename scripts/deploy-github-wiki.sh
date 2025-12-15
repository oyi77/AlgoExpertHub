#!/bin/bash

#############################################
# GitHub Wiki Deployment Script
# Deploys Qoder wiki + docs to GitHub Wiki
#############################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
GITHUB_REPO="oyi77/AlgoExpertHub"  # Change this to your GitHub repo
WIKI_REPO="${GITHUB_REPO}.wiki"
TEMP_DIR="/tmp/github-wiki-deploy"

# Get the absolute path of the project root (parent of scripts directory)
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
SOURCE_WIKI_DIR="$PROJECT_ROOT/.qoder/repowiki/en/content"
SOURCE_DOCS_DIR="$PROJECT_ROOT/docs"

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}GitHub Wiki Deployment Script${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# Function to print colored messages
print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_info() {
    echo -e "${YELLOW}→ $1${NC}"
}

# Check if git is installed
if ! command -v git &> /dev/null; then
    print_error "Git is not installed. Please install git first."
    exit 1
fi

print_success "Git is installed"

# Display configuration
print_info "Project root: $PROJECT_ROOT"
print_info "Wiki source: $SOURCE_WIKI_DIR"
print_info "Docs source: $SOURCE_DOCS_DIR"

# Check if GitHub repo is configured
if [ "$GITHUB_REPO" == "yourusername/algoexperthub" ]; then
    print_error "Please configure GITHUB_REPO variable in the script"
    echo "Edit this script and set GITHUB_REPO to your GitHub repository"
    echo "Example: GITHUB_REPO=\"username/repository-name\""
    exit 1
fi

print_success "GitHub repository configured: $GITHUB_REPO"

# Clean up temp directory if exists
if [ -d "$TEMP_DIR" ]; then
    print_info "Cleaning up previous deployment directory..."
    rm -rf "$TEMP_DIR"
fi

# Create temp directory
print_info "Creating temporary directory..."
mkdir -p "$TEMP_DIR"
print_success "Temporary directory created: $TEMP_DIR"

# Clone the wiki repository
print_info "Cloning GitHub Wiki repository..."
cd "$TEMP_DIR"

if git clone "https://github.com/${WIKI_REPO}.git" wiki; then
    print_success "Wiki repository cloned successfully"
else
    print_error "Failed to clone wiki repository"
    echo "Make sure:"
    echo "  1. The repository exists: https://github.com/${GITHUB_REPO}"
    echo "  2. The wiki is initialized (create at least one page on GitHub)"
    echo "  3. You have access to the repository"
    echo "  4. Your GitHub credentials are configured"
    exit 1
fi

cd wiki

# Configure git
print_info "Configuring git..."
git config user.name "AlgoExpertHub Bot"
git config user.email "bot@algoexperthub.com"
print_success "Git configured"

# Clear existing content (except .git)
print_info "Clearing existing wiki content..."
find . -not -path './.git/*' -not -name '.git' -type f -delete
print_success "Existing content cleared"

# Create Home page
print_info "Creating Home page..."
cat > Home.md << EOF
# AlgoExpertHub Technical Documentation

Welcome to the comprehensive technical documentation for the **AlgoExpertHub Trading Signal Platform**.

## 🚀 Platform Overview

AlgoExpertHub is an AI-powered trading signal platform built on **Laravel 10** with advanced performance optimizations.

### Technology Stack
- **Framework**: Laravel 10.x with Octane 2.0
- **Queue**: Laravel Horizon 5.0 with Redis
- **API**: Laravel Sanctum 3.2
- **Performance**: 5x faster with < 200ms response time
- **AI**: OpenAI, Google Gemini, OpenRouter (400+ models)
- **Exchanges**: CCXT for crypto, mtapi.io for Forex

### Key Features
- 🤖 **Multi-Channel Signal Ingestion** - Telegram, RSS, Web scraping
- 🧠 **AI-Powered Analysis** - Market analysis and signal validation
- ⚡ **Automated Trade Execution** - CCXT and MT4/MT5 integration
- 📊 **Real-time Monitoring** - WebSocket broadcasting
- 🎯 **Risk Management** - Position sizing and stop-loss automation
- 📈 **Copy Trading** - Social trading capabilities

## 📚 Documentation Sections

### Getting Started
- [Project Overview](Project-Overview)
- [Technology Stack & Dependencies](Technology-Stack-&-Dependencies)
- [Installation & Setup](Installation-&-Setup)

### Architecture
- [Architecture Overview](Architecture-Overview)
- [Core Architecture](Core-Architecture)
- [Addon System Architecture](Addon-System-Architecture)
- [Data Flow Architecture](Data-Flow-Architecture)

### Core Modules
- [Trading Management System](Trading-Management-System)
- [Multi-Channel Signal Processing](Multi-Channel-Signal-Processing)
- [AI Integration System](AI-Integration-System)

### API Reference
- [API Overview](API-Reference)
- [Authentication](Authentication)
- [User Management](User-Management)
- [Trading Operations](Trading-Operations)
- [Signal Processing](Signal-Processing)
- [Webhooks](Webhooks)
- [Real-time Communication](Real-time-Communication)

### Configuration
- [Environment Configuration](Environment-Configuration)
- [Database, Cache & Queue Configuration](Database-Cache-Queue-Configuration)
- [Service Integration Configuration](Service-Integration-Configuration)

### Database Schema
- [Database Schema Overview](Database-Schema)
- [User Management Schema](User-Management-Schema)
- [Trading Operations Schema](Trading-Operations-Schema)
- [AI Integration Schema](AI-Integration-Schema)

### Deployment & Operations
- [Deployment Guide](Deployment)
- [Laravel 10 Upgrade Summary](Laravel-10-Upgrade-Summary)
- [Performance Optimization](Performance-Optimization-Implementation)
- [Troubleshooting Guide](Troubleshooting-Guide)

### Guides & Tutorials
- [Trading Execution Flow](Trading-Execution-Flow)
- [Multi-Channel Signal Ingestion](Multi-Channel-Signal-Ingestion)
- [AI Trading Integration](AI-Trading-Integration)
- [Payment Gateway Integration](Payment-Gateway-Integration)
- [Copy Trading System](Copy-Trading-System)
- [Filter Strategy Guide](Filter-Strategy-Guide)
- [Trading Presets](Trading-Presets)

### Development
- [Addon Development](Addon-Development)
- [Theme Development](Theme-Development)
- [API Development](API-Reference)

## 🔗 Quick Links

- **Live Platform**: https://aitradepulse.com
- **Admin Panel**: https://aitradepulse.com/admin
- **API Documentation**: https://aitradepulse.com/admin/wiki
- **GitHub Repository**: https://github.com/${GITHUB_REPO}

## 📝 Recent Updates

### Laravel 10 Upgrade (December 2025)
- ✅ Upgraded from Laravel 9 to Laravel 10
- ✅ Updated Octane to 2.0 (5x performance improvement)
- ✅ Updated Horizon to 5.0 (enhanced queue monitoring)
- ✅ Migrated from Nexmo to Vonage (SMS notifications)
- ✅ Fixed MetaApiAdapter logging issue

### Performance Improvements
- 🚀 **5x faster** request processing
- 🚀 **< 200ms** average response time
- 🚀 **> 500 req/s** throughput
- 🚀 **Real-time** WebSocket events

## 🛠️ System Requirements

- **PHP**: 8.1+ (required for Laravel 10)
- **MySQL**: 5.7+ or MariaDB 10.3+
- **Redis**: 6.0+ (for cache, queue, broadcasting)
- **Node.js**: 14+ (for asset compilation)
- **Swoole**: 4.8+ (for Octane performance)

## 📖 About This Documentation

This documentation is automatically generated from the codebase using Qoder and includes:
- **76 Wiki Pages** - Comprehensive technical documentation
- **25+ Guides** - Tutorials and references
- **Architecture Diagrams** - Mermaid.js visualizations
- **Code Examples** - Syntax-highlighted snippets

---

**Last Updated**: December 14, 2025  
**Version**: 1.0  
**Maintained By**: AlgoExpertHub Development Team
EOF

print_success "Home page created"

# Function to convert filename to GitHub Wiki format
convert_filename() {
    local filename="$1"
    # Remove .md extension
    filename="${filename%.md}"
    # Replace spaces with hyphens
    filename="${filename// /-}"
    # Replace slashes with hyphens
    filename="${filename//\//-}"
    # Remove special characters except hyphens and underscores
    filename=$(echo "$filename" | sed 's/[^a-zA-Z0-9_-]/-/g')
    # Remove multiple consecutive hyphens
    filename=$(echo "$filename" | sed 's/-\+/-/g')
    # Remove leading/trailing hyphens
    filename=$(echo "$filename" | sed 's/^-//;s/-$//')
    echo "$filename"
}

# Copy and convert wiki files
print_info "Copying wiki files..."
cd "$TEMP_DIR/wiki"

WIKI_COUNT=0
if [ -d "$SOURCE_WIKI_DIR" ]; then
    while IFS= read -r file; do
        # Get relative path from source wiki dir
        rel_path="${file#$SOURCE_WIKI_DIR/}"
        
        # Convert to GitHub Wiki filename
        wiki_name=$(convert_filename "$rel_path")
        
        # Copy file with new name
        cp "$file" "${wiki_name}.md"
        
        WIKI_COUNT=$((WIKI_COUNT + 1))
        echo "  → Copied: $rel_path → ${wiki_name}.md"
    done < <(find "$SOURCE_WIKI_DIR" -name "*.md" -type f)
    print_success "Wiki files copied ($WIKI_COUNT files)"
else
    print_error "Wiki source directory not found: $SOURCE_WIKI_DIR"
    print_info "Current directory: $(pwd)"
    print_info "Project root: $PROJECT_ROOT"
fi

# Copy and convert docs files
print_info "Copying docs files..."
DOCS_COUNT=0
if [ -d "$SOURCE_DOCS_DIR" ]; then
    while IFS= read -r file; do
        # Get filename without path
        filename=$(basename "$file")
        
        # Convert to GitHub Wiki filename
        wiki_name=$(convert_filename "$filename")
        
        # Copy file with new name
        cp "$file" "${wiki_name}.md"
        
        DOCS_COUNT=$((DOCS_COUNT + 1))
        echo "  → Copied: $filename → ${wiki_name}.md"
    done < <(find "$SOURCE_DOCS_DIR" -name "*.md" -type f)
    print_success "Docs files copied ($DOCS_COUNT files)"
else
    print_error "Docs source directory not found: $SOURCE_DOCS_DIR"
    print_info "Current directory: $(pwd)"
    print_info "Project root: $PROJECT_ROOT"
fi

# Create _Sidebar.md for navigation
print_info "Creating sidebar navigation..."
cat > _Sidebar.md << 'EOF'
## 📚 Documentation

### Getting Started
- [Home](Home)
- [Project Overview](Project-Overview)
- [Technology Stack](Technology-Stack-&-Dependencies)
- [Installation](Installation-&-Setup)

### Architecture
- [Overview](Architecture-Overview)
- [Core Architecture](Core-Architecture)
- [Addon System](Addon-System-Architecture)
- [Data Flow](Data-Flow-Architecture)

### Core Modules
- [Trading Management](Trading-Management-System)
- [Multi-Channel Signals](Multi-Channel-Signal-Processing)
- [AI Integration](AI-Integration-System)

### API Reference
- [API Overview](API-Reference)
- [Authentication](Authentication)
- [Trading Operations](Trading-Operations)
- [Signal Processing](Signal-Processing)

### Deployment
- [Deployment Guide](Deployment)
- [Laravel 10 Upgrade](Laravel-10-Upgrade-Summary)
- [Performance](Performance-Optimization-Implementation)

### Guides
- [Trading Execution](Trading-Execution-Flow)
- [Signal Ingestion](Multi-Channel-Signal-Ingestion)
- [AI Trading](AI-Trading-Integration)
- [Payment Gateway](Payment-Gateway-Integration)

### Development
- [Addon Development](Addon-Development)
- [Theme Development](Theme-Development)
- [Troubleshooting](Troubleshooting-Guide)
EOF

print_success "Sidebar created"

# Create _Footer.md
print_info "Creating footer..."
cat > _Footer.md << EOF
---
**AlgoExpertHub** | [Website](https://aitradepulse.com) | [GitHub](https://github.com/${GITHUB_REPO}) | [Admin Panel](https://aitradepulse.com/admin)

Last Updated: December 14, 2025 | Version: 1.0
EOF

print_success "Footer created"

# Count files
FILE_COUNT=$(find . -name "*.md" -not -name "_*" | wc -l)
print_info "Total files prepared: $FILE_COUNT"

# Git add all files
print_info "Staging files for commit..."
git add .
print_success "Files staged"

# Check if there are changes to commit
if git diff --cached --quiet; then
    print_info "No changes to commit"
else
    # Commit changes
    print_info "Committing changes..."
    git commit -m "Deploy AlgoExpertHub documentation

- Added $FILE_COUNT documentation pages
- Includes wiki pages from Qoder
- Includes guides and tutorials
- Created Home page with navigation
- Added sidebar and footer

Deployment Date: $(date '+%Y-%m-%d %H:%M:%S')
"
    print_success "Changes committed"
    
    # Push to GitHub
    print_info "Pushing to GitHub Wiki..."
    if git push origin master; then
        print_success "Successfully pushed to GitHub Wiki!"
    else
        print_error "Failed to push to GitHub"
        echo "You may need to authenticate. Try:"
        echo "  1. Set up GitHub credentials: git config --global credential.helper store"
        echo "  2. Or use SSH: git remote set-url origin git@github.com:${WIKI_REPO}.git"
        exit 1
    fi
fi

# Cleanup
print_info "Cleaning up temporary files..."
cd /
rm -rf "$TEMP_DIR"
print_success "Cleanup complete"

echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}Deployment Complete!${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "${BLUE}Your wiki is now available at:${NC}"
echo -e "${YELLOW}https://github.com/${GITHUB_REPO}/wiki${NC}"
echo ""
echo -e "${BLUE}Statistics:${NC}"
echo "  • Total pages deployed: $FILE_COUNT"
echo "  • Home page: ✓"
echo "  • Sidebar navigation: ✓"
echo "  • Footer: ✓"
echo ""
echo -e "${GREEN}Next steps:${NC}"
echo "  1. Visit your wiki: https://github.com/${GITHUB_REPO}/wiki"
echo "  2. Verify all pages are accessible"
echo "  3. Test navigation links"
echo "  4. Share with your team!"
echo ""

