# GitHub Wiki Deployment Guide

## Overview

This guide explains how to deploy the Qoder-generated wiki and docs folder to GitHub Wiki, making your documentation publicly accessible and version-controlled.

## Why GitHub Wiki?

### Advantages

- ✅ **Free Hosting** - No hosting costs
- ✅ **Version Control** - Git-based, full history
- ✅ **Public Access** - Share with community
- ✅ **Search** - Built-in search functionality
- ✅ **Markdown Support** - Native markdown rendering
- ✅ **Easy Editing** - Edit directly on GitHub
- ✅ **Collaboration** - Team can contribute
- ✅ **No Build Required** - Instant deployment

### Limitations

- ⚠️ **Flat Structure** - No nested folders (files are flattened)
- ⚠️ **Public Only** - Private wikis require private repo
- ⚠️ **Basic UI** - Simple GitHub styling
- ⚠️ **No Custom Domain** - Uses github.com domain

## Prerequisites

### 1. GitHub Repository

You need a GitHub repository. If you don't have one:

```bash
# Create a new repository on GitHub
# Go to: https://github.com/new
# Repository name: algoexperthub (or your preferred name)
# Public or Private (wiki visibility matches repo visibility)
```

### 2. Initialize Wiki

GitHub wikis must be initialized before you can push to them:

1. Go to your repository: `https://github.com/username/repository`
2. Click **Wiki** tab
3. Click **Create the first page**
4. Enter any title and content
5. Click **Save Page**

This creates the wiki repository at `https://github.com/username/repository.wiki`

### 3. Git Configuration

Ensure git is configured with your credentials:

```bash
# Configure git user
git config --global user.name "Your Name"
git config --global user.email "your.email@example.com"

# Configure credential storage (optional)
git config --global credential.helper store
```

### 4. GitHub Authentication

Choose one of these authentication methods:

#### Option A: HTTPS with Personal Access Token (Recommended)

1. Generate token: https://github.com/settings/tokens
2. Select scopes: `repo` (full control)
3. Copy token
4. Use token as password when pushing

#### Option B: SSH Key

```bash
# Generate SSH key
ssh-keygen -t ed25519 -C "your.email@example.com"

# Add to GitHub
cat ~/.ssh/id_ed25519.pub
# Copy output and add to: https://github.com/settings/keys
```

## Deployment Methods

### Method 1: Automated Script (Recommended)

We've created a deployment script that handles everything automatically.

#### Step 1: Configure the Script

Edit `scripts/deploy-github-wiki.sh`:

```bash
# Change this line to your GitHub repository
GITHUB_REPO="yourusername/algoexperthub"
```

Example:
```bash
GITHUB_REPO="johndoe/trading-platform"
```

#### Step 2: Make Script Executable

```bash
chmod +x scripts/deploy-github-wiki.sh
```

#### Step 3: Run Deployment

```bash
cd /opt/1panel/apps/openresty/openresty/www/sites/aitradepulse.com/index
./scripts/deploy-github-wiki.sh
```

The script will:
1. ✅ Clone your wiki repository
2. ✅ Clear existing content
3. ✅ Copy all wiki files (76 pages)
4. ✅ Copy all docs files (25+ guides)
5. ✅ Convert filenames to GitHub Wiki format
6. ✅ Create Home page with navigation
7. ✅ Create sidebar (_Sidebar.md)
8. ✅ Create footer (_Footer.md)
9. ✅ Commit and push to GitHub

#### Expected Output

```
========================================
GitHub Wiki Deployment Script
========================================

✓ Git is installed
✓ GitHub repository configured: johndoe/trading-platform
→ Creating temporary directory...
✓ Temporary directory created: /tmp/github-wiki-deploy
→ Cloning GitHub Wiki repository...
✓ Wiki repository cloned successfully
→ Configuring git...
✓ Git configured
→ Clearing existing wiki content...
✓ Existing content cleared
→ Creating Home page...
✓ Home page created
→ Copying wiki files...
  → Copied: Project Overview.md → Project-Overview.md
  → Copied: Architecture Overview/Architecture Overview.md → Architecture-Overview-Architecture-Overview.md
  ... (76 files)
✓ Wiki files copied
→ Copying docs files...
  → Copied: laravel-10-upgrade-summary.md → Laravel-10-Upgrade-Summary.md
  ... (25+ files)
✓ Docs files copied
→ Creating sidebar navigation...
✓ Sidebar created
→ Creating footer...
✓ Footer created
→ Total files prepared: 102
→ Staging files for commit...
✓ Files staged
→ Committing changes...
✓ Changes committed
→ Pushing to GitHub Wiki...
✓ Successfully pushed to GitHub Wiki!
→ Cleaning up temporary files...
✓ Cleanup complete

========================================
Deployment Complete!
========================================

Your wiki is now available at:
https://github.com/johndoe/trading-platform/wiki

Statistics:
  • Total pages deployed: 102
  • Home page: ✓
  • Sidebar navigation: ✓
  • Footer: ✓

Next steps:
  1. Visit your wiki: https://github.com/johndoe/trading-platform/wiki
  2. Verify all pages are accessible
  3. Test navigation links
  4. Share with your team!
```

### Method 2: Manual Deployment

If you prefer manual control or the script fails:

#### Step 1: Clone Wiki Repository

```bash
# Create temp directory
mkdir -p /tmp/wiki-deploy
cd /tmp/wiki-deploy

# Clone wiki (replace with your repo)
git clone https://github.com/username/repository.wiki.git
cd repository.wiki
```

#### Step 2: Copy Documentation

```bash
# Copy wiki files
cp -r /opt/1panel/apps/openresty/openresty/www/sites/aitradepulse.com/index/docs/* .
```

#### Step 3: Flatten Structure

GitHub Wiki doesn't support nested folders. Flatten the structure:

```bash
# Find all markdown files in subdirectories and move to root
find . -name "*.md" -type f | while read file; do
    # Get filename without path
    filename=$(basename "$file")
    # Move to root if not already there
    if [ "$file" != "./$filename" ]; then
        mv "$file" "./$filename"
    fi
done

# Remove empty directories
find . -type d -empty -delete
```

#### Step 4: Rename Files

GitHub Wiki uses hyphens instead of spaces:

```bash
# Rename files with spaces to use hyphens
for file in *.md; do
    newname=$(echo "$file" | sed 's/ /-/g')
    if [ "$file" != "$newname" ]; then
        mv "$file" "$newname"
    fi
done
```

#### Step 5: Create Home Page

```bash
cat > Home.md << 'EOF'
# AlgoExpertHub Technical Documentation

Welcome to the comprehensive technical documentation.

## Documentation Sections

- [Project Overview](Project-Overview)
- [Architecture Overview](Architecture-Overview)
- [API Reference](API-Reference)
- [Deployment Guide](Deployment)

[View all pages →](https://github.com/username/repository/wiki/_pages)
EOF
```

#### Step 6: Create Sidebar

```bash
cat > _Sidebar.md << 'EOF'
## Navigation

### Getting Started
- [Home](Home)
- [Project Overview](Project-Overview)
- [Installation](Installation-&-Setup)

### Architecture
- [Overview](Architecture-Overview)
- [Core Architecture](Core-Architecture)

### API
- [API Reference](API-Reference)
- [Authentication](Authentication)

### Guides
- [Deployment](Deployment)
- [Troubleshooting](Troubleshooting-Guide)
EOF
```

#### Step 7: Commit and Push

```bash
# Stage all files
git add .

# Commit
git commit -m "Deploy AlgoExpertHub documentation"

# Push to GitHub
git push origin master
```

## Post-Deployment

### 1. Verify Deployment

Visit your wiki: `https://github.com/username/repository/wiki`

Check:
- ✅ Home page loads correctly
- ✅ Sidebar navigation works
- ✅ All pages are accessible
- ✅ Links work correctly
- ✅ Code blocks render properly
- ✅ Images display (if any)

### 2. Fix Broken Links

GitHub Wiki uses specific link format:

**Correct**:
```markdown
[Project Overview](Project-Overview)
[API Reference](API-Reference)
```

**Incorrect**:
```markdown
[Project Overview](Project Overview.md)
[API Reference](./api-reference.md)
```

### 3. Update Internal Links

If your markdown files have internal links, update them:

```bash
# Replace .md links with wiki links
find . -name "*.md" -exec sed -i 's/\](.*\.md)/](\1)/g' {} \;
find . -name "*.md" -exec sed -i 's/\.md)/))/g' {} \;
```

### 4. Add Navigation

Edit `_Sidebar.md` to add all important pages:

```markdown
## Documentation

### Getting Started
- [Home](Home)
- [Project Overview](Project-Overview)
- [Technology Stack](Technology-Stack-&-Dependencies)

### Architecture
- [Architecture Overview](Architecture-Overview)
- [Core Architecture](Core-Architecture)
- [Addon System](Addon-System-Architecture)

... (add more sections)
```

### 5. Configure Wiki Settings

Go to: `https://github.com/username/repository/settings`

Under **Features**:
- ✅ Enable **Wikis**
- ✅ Enable **Restrict editing to collaborators only** (optional)

## Updating Documentation

### Automated Updates

Re-run the deployment script:

```bash
./scripts/deploy-github-wiki.sh
```

This will:
1. Pull latest changes from GitHub
2. Update with new documentation
3. Commit and push changes

### Manual Updates

#### Update Single Page

1. Go to: `https://github.com/username/repository/wiki/Page-Name`
2. Click **Edit**
3. Make changes
4. Click **Save Page**

#### Update Multiple Pages

```bash
# Clone wiki
git clone https://github.com/username/repository.wiki.git
cd repository.wiki

# Make changes
# Edit files...

# Commit and push
git add .
git commit -m "Update documentation"
git push origin master
```

## Troubleshooting

### Issue: Cannot clone wiki repository

**Error**: `Repository not found`

**Solution**:
1. Ensure wiki is initialized (create first page on GitHub)
2. Check repository name is correct
3. Verify you have access to the repository

### Issue: Authentication failed

**Error**: `Authentication failed`

**Solution**:

**For HTTPS**:
```bash
# Use personal access token as password
# Generate token: https://github.com/settings/tokens
# When prompted for password, use the token
```

**For SSH**:
```bash
# Change remote URL to SSH
cd repository.wiki
git remote set-url origin git@github.com:username/repository.wiki.git
```

### Issue: Links not working

**Problem**: Links show 404 errors

**Solution**:
- GitHub Wiki links don't use `.md` extension
- Use format: `[Link Text](Page-Name)` not `[Link Text](page-name.md)`
- Spaces in filenames become hyphens: `Project Overview.md` → `Project-Overview`

### Issue: Images not displaying

**Problem**: Images don't show up

**Solution**:
- GitHub Wiki doesn't support relative image paths
- Upload images to GitHub repo and use absolute URLs
- Or use external image hosting (imgur, etc.)

### Issue: Code blocks not highlighting

**Problem**: Code blocks render but without syntax highlighting

**Solution**:
- Ensure code blocks specify language:
  ````markdown
  ```php
  // code here
  ```
  ````

### Issue: Sidebar not showing

**Problem**: Sidebar doesn't appear

**Solution**:
- File must be named exactly `_Sidebar.md` (case-sensitive)
- Must be in root of wiki repository
- Verify file was committed and pushed

## Advanced Configuration

### Custom Home Page

Create a comprehensive home page with:

```markdown
# Project Name

Brief description

## Quick Start

1. [Installation](Installation)
2. [Configuration](Configuration)
3. [First Steps](Getting-Started)

## Documentation

### For Developers
- [API Reference](API-Reference)
- [Architecture](Architecture-Overview)
- [Contributing](Contributing)

### For Users
- [User Guide](User-Guide)
- [FAQ](FAQ)
- [Troubleshooting](Troubleshooting)

## Resources

- [GitHub Repository](https://github.com/username/repo)
- [Live Demo](https://example.com)
- [Support](https://github.com/username/repo/issues)
```

### Footer with Links

Create `_Footer.md`:

```markdown
---

**Project Name** | [Website](https://example.com) | [GitHub](https://github.com/username/repo) | [Issues](https://github.com/username/repo/issues)

© 2025 Your Company | [Privacy Policy](Privacy) | [Terms of Service](Terms)
```

### Search Optimization

Add keywords to pages for better search:

```markdown
---
keywords: api, rest, authentication, oauth
---

# API Reference

Your content here...
```

## Automation

### GitHub Actions

Automate wiki deployment with GitHub Actions:

Create `.github/workflows/deploy-wiki.yml`:

```yaml
name: Deploy Wiki

on:
  push:
    branches: [ main ]
    paths:
      - '.qoder/repowiki/**'
      - 'docs/**'

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - name: Checkout code
        uses: actions/checkout@v3
        
      - name: Deploy to Wiki
        run: |
          chmod +x scripts/deploy-github-wiki.sh
          ./scripts/deploy-github-wiki.sh
        env:
          GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}
```

### Scheduled Updates

Update wiki daily:

```yaml
on:
  schedule:
    - cron: '0 0 * * *'  # Daily at midnight
```

## Best Practices

### 1. Clear Navigation

- Create comprehensive sidebar
- Use consistent naming
- Group related pages
- Add breadcrumbs in content

### 2. Link Maintenance

- Use relative links for wiki pages
- Test all links after deployment
- Update links when renaming pages

### 3. Version Control

- Commit frequently
- Use descriptive commit messages
- Tag releases

### 4. Content Organization

- Keep pages focused
- Use clear headings
- Add table of contents for long pages
- Cross-reference related pages

### 5. Regular Updates

- Update when code changes
- Review and update outdated content
- Add new guides as features are added

## Comparison: GitHub Wiki vs Other Options

| Feature | GitHub Wiki | MkDocs | Docsify | GitBook |
|---------|-------------|--------|---------|---------|
| **Cost** | Free | Free | Free | Free/Paid |
| **Hosting** | GitHub | Self/GH Pages | Self/GH Pages | GitBook |
| **Build** | None | Required | None | Required |
| **Search** | Built-in | Plugin | Plugin | Built-in |
| **Custom Domain** | No | Yes | Yes | Yes (paid) |
| **Themes** | Fixed | Many | Many | Limited |
| **Versioning** | Git | Manual | Manual | Built-in |
| **Ease of Use** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ |

## Conclusion

GitHub Wiki is an excellent choice for:
- ✅ Open source projects
- ✅ Quick documentation deployment
- ✅ Team collaboration
- ✅ Version-controlled documentation
- ✅ Free hosting needs

Use the automated script for easiest deployment, or follow manual steps for more control.

---

**Deployment Script**: `scripts/deploy-github-wiki.sh`  
**Last Updated**: December 14, 2025  
**Maintained By**: AlgoExpertHub Development Team

