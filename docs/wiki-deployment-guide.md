# Wiki Deployment Guide

## Overview

This guide provides multiple deployment options for the Qoder-generated wiki (76 markdown files) and the docs folder, making comprehensive technical documentation accessible via web browser.

## Wiki Structure

```
.qoder/repowiki/en/
├── content/              # 76 markdown documentation files
│   ├── Project Overview.md
│   ├── Architecture Overview/
│   ├── Core Modules/
│   ├── API Reference/
│   ├── Configuration/
│   ├── Database Schema/
│   ├── Technology Stack & Dependencies.md
│   ├── Deployment.md
│   └── ... (70+ more files)
├── meta/
│   └── repowiki-metadata.json
docs/                     # Additional documentation
├── README.md
├── laravel-10-upgrade-summary.md
├── deployment-guide.md
├── api-reference.md
└── ... (25+ more files)
```

## Deployment Options

### Option 1: Static Site Generator (Recommended)

Deploy as a static documentation site using a markdown-to-HTML generator.

#### Using MkDocs (Python)

**Advantages**: Beautiful theme, search functionality, navigation, mobile-responsive

**Installation**:
```bash
# Install MkDocs
pip install mkdocs mkdocs-material

# Create mkdocs.yml configuration
cat > mkdocs.yml << 'EOF'
site_name: AlgoExpertHub Technical Documentation
site_description: Comprehensive technical documentation for AlgoExpertHub Trading Platform
site_author: AlgoExpertHub Team
site_url: https://aitradepulse.com/wiki

theme:
  name: material
  palette:
    primary: indigo
    accent: indigo
  features:
    - navigation.tabs
    - navigation.sections
    - navigation.expand
    - search.suggest
    - search.highlight
    - content.code.copy

nav:
  - Home: index.md
  - Getting Started:
    - Project Overview: qoder/Project-Overview.md
    - Installation & Setup: qoder/Installation-Setup.md
    - Technology Stack: qoder/Technology-Stack-Dependencies.md
  - Architecture:
    - Overview: qoder/Architecture-Overview.md
    - Core Architecture: qoder/Core-Architecture.md
    - Addon System: qoder/Addon-System-Architecture.md
    - Data Flow: qoder/Data-Flow-Architecture.md
  - Core Modules:
    - Overview: qoder/Core-Modules.md
    - Trading Management: qoder/Trading-Management-System.md
    - Multi-Channel Signals: qoder/Multi-Channel-Signal-Processing.md
    - AI Integration: qoder/AI-Integration-System.md
  - API Reference:
    - Overview: qoder/API-Reference.md
    - Authentication: qoder/Authentication.md
    - User Management: qoder/User-Management.md
    - Trading Operations: qoder/Trading-Operations.md
    - Signal Processing: qoder/Signal-Processing.md
    - Webhooks: qoder/Webhooks.md
    - Real-time Communication: qoder/Real-time-Communication.md
  - Configuration:
    - Overview: qoder/Configuration.md
    - Environment: qoder/Environment-Configuration.md
    - Database & Queue: qoder/Database-Cache-Queue-Configuration.md
    - Service Integration: qoder/Service-Integration-Configuration.md
  - Database Schema:
    - Overview: qoder/Database-Schema.md
    - User Management: qoder/User-Management-Schema.md
    - Trading Operations: qoder/Trading-Operations-Schema.md
    - AI Integration: qoder/AI-Integration-Schema.md
  - Deployment:
    - Deployment Guide: qoder/Deployment.md
    - Laravel 10 Upgrade: docs/laravel-10-upgrade-summary.md
    - Performance Optimization: docs/performance-optimization-implementation.md
  - Guides:
    - Trading Execution Flow: docs/trading-execution-flow.md
    - Multi-Channel Ingestion: docs/multi-channel-signal-ingestion.md
    - AI Trading Integration: docs/ai-trading-integration.md
    - Payment Gateway: docs/payment-gateway-integration.md
    - Copy Trading: docs/copy-trading-system.md
    - Filter Strategy: docs/filter-strategy-guide.md
  - Troubleshooting:
    - Guide: qoder/Troubleshooting-Guide.md
    - Common Issues: docs/troubleshooting-guide.md
  - Addon Development: qoder/Addon-Development.md

markdown_extensions:
  - pymdownx.highlight
  - pymdownx.superfences
  - pymdownx.tabbed
  - admonition
  - codehilite
  - toc:
      permalink: true

extra:
  social:
    - icon: fontawesome/brands/github
      link: https://github.com/yourusername/algoexperthub
EOF
```

**Prepare Documentation**:
```bash
# Create docs directory for MkDocs
mkdir -p mkdocs-site/docs/qoder
mkdir -p mkdocs-site/docs/docs

# Copy and flatten Qoder wiki
cd .qoder/repowiki/en/content
find . -name "*.md" -exec bash -c 'cp "$1" "../../../../mkdocs-site/docs/qoder/$(echo "$1" | sed "s/\//-/g" | sed "s/^.\-//g")"' _ {} \;

# Copy docs folder
cp -r docs/* mkdocs-site/docs/docs/

# Create index page
cat > mkdocs-site/docs/index.md << 'EOF'
# AlgoExpertHub Technical Documentation

Welcome to the comprehensive technical documentation for the AlgoExpertHub Trading Signal Platform.

## 🚀 Platform Overview

AlgoExpertHub is an AI-powered trading signal platform built on **Laravel 10** with advanced performance optimizations.

### Technology Stack
- **Framework**: Laravel 10.x with Octane 2.0
- **Queue**: Laravel Horizon 5.0 with Redis
- **API**: Laravel Sanctum 3.2
- **Performance**: 5x faster with < 200ms response time
- **AI**: OpenAI, Google Gemini, OpenRouter (400+ models)

## 📚 Documentation Sections

### Getting Started
- [Project Overview](qoder/Project-Overview.md)
- [Installation & Setup](qoder/Installation-Setup.md)
- [Technology Stack](qoder/Technology-Stack-Dependencies.md)

### Architecture
- [Architecture Overview](qoder/Architecture-Overview.md)
- [Core Architecture](qoder/Core-Architecture.md)
- [Addon System](qoder/Addon-System-Architecture.md)

### API Reference
- [API Overview](qoder/API-Reference.md)
- [Authentication](qoder/Authentication.md)
- [Trading Operations](qoder/Trading-Operations.md)

### Deployment
- [Deployment Guide](qoder/Deployment.md)
- [Laravel 10 Upgrade](docs/laravel-10-upgrade-summary.md)

## 🔗 Quick Links

- [Trading Execution Flow](docs/trading-execution-flow.md)
- [Multi-Channel Signal Ingestion](docs/multi-channel-signal-ingestion.md)
- [AI Trading Integration](docs/ai-trading-integration.md)
- [Troubleshooting Guide](qoder/Troubleshooting-Guide.md)
EOF
```

**Build and Deploy**:
```bash
cd mkdocs-site

# Build static site
mkdocs build

# Deploy to public/wiki directory
mkdir -p ../main/public/wiki
cp -r site/* ../main/public/wiki/

# Access at: https://aitradepulse.com/wiki
```

#### Using Docsify (JavaScript)

**Advantages**: No build step, runs in browser, lightweight

**Setup**:
```bash
# Create wiki directory
mkdir -p main/public/wiki

# Copy documentation
cp -r .qoder/repowiki/en/content/* main/public/wiki/
cp -r docs/* main/public/wiki/docs/

# Create index.html
cat > main/public/wiki/index.html << 'EOF'
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>AlgoExpertHub Documentation</title>
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
  <meta name="description" content="Comprehensive technical documentation">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0">
  <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/docsify@4/lib/themes/vue.css">
</head>
<body>
  <div id="app"></div>
  <script>
    window.$docsify = {
      name: 'AlgoExpertHub Docs',
      repo: '',
      loadSidebar: true,
      subMaxLevel: 3,
      search: 'auto',
      search: {
        maxAge: 86400000,
        paths: 'auto',
        placeholder: 'Search documentation',
        noData: 'No Results!',
        depth: 6
      },
      pagination: {
        previousText: 'Previous',
        nextText: 'Next',
        crossChapter: true
      }
    }
  </script>
  <script src="//cdn.jsdelivr.net/npm/docsify@4"></script>
  <script src="//cdn.jsdelivr.net/npm/docsify/lib/plugins/search.min.js"></script>
  <script src="//cdn.jsdelivr.net/npm/prismjs@1/components/prism-php.min.js"></script>
  <script src="//cdn.jsdelivr.net/npm/prismjs@1/components/prism-bash.min.js"></script>
  <script src="//cdn.jsdelivr.net/npm/prismjs@1/components/prism-json.min.js"></script>
</body>
</html>
EOF

# Create sidebar
cat > main/public/wiki/_sidebar.md << 'EOF'
* Getting Started
  * [Project Overview](Project-Overview.md)
  * [Technology Stack](Technology-Stack-&-Dependencies.md)
  * [Installation & Setup](Installation-&-Setup/Installation-&-Setup.md)

* Architecture
  * [Overview](Architecture-Overview/Architecture-Overview.md)
  * [Core Architecture](Architecture-Overview/Core-Architecture/Core-Architecture.md)
  * [Addon System](Architecture-Overview/Addon-System-Architecture.md)

* Core Modules
  * [Overview](Core-Modules/Core-Modules.md)
  * [Trading Management](Core-Modules/Trading-Management-System/Trading-Management-System.md)
  * [Multi-Channel Signals](Core-Modules/Multi-Channel-Signal-Processing/Multi-Channel-Signal-Processing.md)

* API Reference
  * [Overview](API-Reference/API-Reference.md)
  * [Authentication](API-Reference/Authentication.md)
  * [Trading Operations](API-Reference/Trading-Operations.md)

* Deployment
  * [Deployment Guide](Deployment.md)
  * [Laravel 10 Upgrade](docs/laravel-10-upgrade-summary.md)
EOF

# Access at: https://aitradepulse.com/wiki
```

### Option 2: Laravel Route Integration

Serve documentation directly through Laravel routes with markdown rendering.

**Install Markdown Parser**:
```bash
cd main
composer require erusev/parsedown
composer require league/commonmark
```

**Create Documentation Controller**:
```php
// main/app/Http/Controllers/DocumentationController.php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use League\CommonMark\CommonMarkConverter;

class DocumentationController extends Controller
{
    protected $converter;
    protected $wikiPath;
    protected $docsPath;
    
    public function __construct()
    {
        $this->converter = new CommonMarkConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
        $this->wikiPath = base_path('../.qoder/repowiki/en/content');
        $this->docsPath = base_path('../docs');
    }
    
    public function index()
    {
        return view('documentation.index');
    }
    
    public function showWiki($path = null)
    {
        $filePath = $this->wikiPath . '/' . ($path ?? 'Project Overview.md');
        
        if (!File::exists($filePath)) {
            abort(404, 'Documentation not found');
        }
        
        $markdown = File::get($filePath);
        $html = $this->converter->convert($markdown);
        
        return view('documentation.show', [
            'title' => basename($filePath, '.md'),
            'content' => $html,
            'type' => 'wiki'
        ]);
    }
    
    public function showDocs($file)
    {
        $filePath = $this->docsPath . '/' . $file . '.md';
        
        if (!File::exists($filePath)) {
            abort(404, 'Documentation not found');
        }
        
        $markdown = File::get($filePath);
        $html = $this->converter->convert($markdown);
        
        return view('documentation.show', [
            'title' => basename($filePath, '.md'),
            'content' => $html,
            'type' => 'docs'
        ]);
    }
    
    public function search(Request $request)
    {
        $query = $request->input('q');
        $results = [];
        
        // Search wiki files
        $wikiFiles = File::allFiles($this->wikiPath);
        foreach ($wikiFiles as $file) {
            if ($file->getExtension() === 'md') {
                $content = File::get($file->getPathname());
                if (stripos($content, $query) !== false) {
                    $results[] = [
                        'title' => $file->getFilenameWithoutExtension(),
                        'path' => str_replace($this->wikiPath . '/', '', $file->getPathname()),
                        'type' => 'wiki'
                    ];
                }
            }
        }
        
        // Search docs files
        $docsFiles = File::files($this->docsPath);
        foreach ($docsFiles as $file) {
            if ($file->getExtension() === 'md') {
                $content = File::get($file->getPathname());
                if (stripos($content, $query) !== false) {
                    $results[] = [
                        'title' => $file->getFilenameWithoutExtension(),
                        'path' => $file->getFilenameWithoutExtension(),
                        'type' => 'docs'
                    ];
                }
            }
        }
        
        return view('documentation.search', ['results' => $results, 'query' => $query]);
    }
}
```

**Add Routes**:
```php
// main/routes/web.php
Route::prefix('wiki')->group(function () {
    Route::get('/', [DocumentationController::class, 'index'])->name('wiki.index');
    Route::get('/search', [DocumentationController::class, 'search'])->name('wiki.search');
    Route::get('/docs/{file}', [DocumentationController::class, 'showDocs'])->name('wiki.docs');
    Route::get('/{path?}', [DocumentationController::class, 'showWiki'])->name('wiki.show')
        ->where('path', '.*');
});
```

**Create Views**:
```blade
{{-- main/resources/views/documentation/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <h5>Documentation</h5>
                </div>
                <div class="card-body">
                    <h6>Getting Started</h6>
                    <ul class="list-unstyled">
                        <li><a href="{{ route('wiki.show', 'Project Overview') }}">Project Overview</a></li>
                        <li><a href="{{ route('wiki.show', 'Technology Stack & Dependencies') }}">Technology Stack</a></li>
                    </ul>
                    
                    <h6>Architecture</h6>
                    <ul class="list-unstyled">
                        <li><a href="{{ route('wiki.show', 'Architecture Overview/Architecture Overview') }}">Overview</a></li>
                        <li><a href="{{ route('wiki.show', 'Architecture Overview/Core Architecture/Core Architecture') }}">Core Architecture</a></li>
                    </ul>
                    
                    <h6>Guides</h6>
                    <ul class="list-unstyled">
                        <li><a href="{{ route('wiki.docs', 'laravel-10-upgrade-summary') }}">Laravel 10 Upgrade</a></li>
                        <li><a href="{{ route('wiki.docs', 'deployment-guide') }}">Deployment Guide</a></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="col-md-9">
            <div class="card">
                <div class="card-header">
                    <h3>AlgoExpertHub Technical Documentation</h3>
                </div>
                <div class="card-body">
                    <p>Welcome to the comprehensive technical documentation.</p>
                    <p>Select a topic from the sidebar to get started.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
```

### Option 3: GitHub Pages / GitBook

Deploy to GitHub Pages or GitBook for free hosting.

**GitHub Pages Setup**:
```bash
# Create gh-pages branch
git checkout -b gh-pages

# Copy documentation
mkdir -p docs
cp -r .qoder/repowiki/en/content/* docs/wiki/
cp -r docs/* docs/guides/

# Create _config.yml for Jekyll
cat > _config.yml << 'EOF'
title: AlgoExpertHub Documentation
description: Comprehensive technical documentation
theme: jekyll-theme-cayman
markdown: kramdown
EOF

# Push to GitHub
git add .
git commit -m "Deploy documentation to GitHub Pages"
git push origin gh-pages

# Enable GitHub Pages in repository settings
# Access at: https://yourusername.github.io/algoexperthub/
```

### Option 4: Docker Container with Nginx

Deploy as a standalone Docker container.

**Create Dockerfile**:
```dockerfile
# Dockerfile.docs
FROM nginx:alpine

# Install markdown-to-html converter
RUN apk add --no-cache python3 py3-pip
RUN pip3 install markdown

# Copy documentation
COPY .qoder/repowiki/en/content /usr/share/nginx/html/wiki
COPY docs /usr/share/nginx/html/docs

# Convert markdown to HTML
RUN find /usr/share/nginx/html -name "*.md" -exec sh -c 'python3 -m markdown "$1" > "${1%.md}.html"' _ {} \;

# Nginx configuration
COPY nginx-docs.conf /etc/nginx/conf.d/default.conf

EXPOSE 80
```

**Nginx Configuration**:
```nginx
# nginx-docs.conf
server {
    listen 80;
    server_name _;
    root /usr/share/nginx/html;
    index index.html;
    
    location / {
        try_files $uri $uri.html $uri/ =404;
    }
    
    location /wiki {
        alias /usr/share/nginx/html/wiki;
        try_files $uri $uri.html $uri/ =404;
    }
    
    location /docs {
        alias /usr/share/nginx/html/docs;
        try_files $uri $uri.html $uri/ =404;
    }
}
```

**Build and Run**:
```bash
docker build -f Dockerfile.docs -t algoexperthub-docs .
docker run -d -p 8080:80 --name docs algoexperthub-docs

# Access at: http://localhost:8080
```

### Option 5: Simple PHP Markdown Viewer

Lightweight PHP-based markdown viewer without framework dependencies.

**Create viewer.php**:
```php
<?php
// main/public/wiki/viewer.php
require __DIR__ . '/../../vendor/autoload.php';

use League\CommonMark\CommonMarkConverter;

$converter = new CommonMarkConverter([
    'html_input' => 'strip',
    'allow_unsafe_links' => false,
]);

$wikiPath = realpath(__DIR__ . '/../../../.qoder/repowiki/en/content');
$docsPath = realpath(__DIR__ . '/../../../docs');

$file = $_GET['file'] ?? 'Project Overview.md';
$type = $_GET['type'] ?? 'wiki';

$basePath = $type === 'wiki' ? $wikiPath : $docsPath;
$filePath = $basePath . '/' . $file;

// Security: prevent directory traversal
$realPath = realpath($filePath);
if (!$realPath || strpos($realPath, $basePath) !== 0) {
    http_response_code(404);
    die('File not found');
}

if (!file_exists($realPath)) {
    http_response_code(404);
    die('File not found');
}

$markdown = file_get_contents($realPath);
$html = $converter->convert($markdown);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo basename($file, '.md'); ?> - AlgoExpertHub Docs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/themes/prism.min.css" rel="stylesheet">
    <style>
        body { padding-top: 20px; }
        .sidebar { position: sticky; top: 20px; max-height: calc(100vh - 40px); overflow-y: auto; }
        .content { padding: 20px; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 sidebar">
                <h5>Documentation</h5>
                <ul class="list-unstyled">
                    <li><a href="?type=wiki&file=Project Overview.md">Project Overview</a></li>
                    <li><a href="?type=wiki&file=Architecture Overview/Architecture Overview.md">Architecture</a></li>
                    <li><a href="?type=docs&file=laravel-10-upgrade-summary.md">Laravel 10 Upgrade</a></li>
                </ul>
            </div>
            <div class="col-md-9 content">
                <?php echo $html; ?>
            </div>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/prism.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/components/prism-php.min.js"></script>
</body>
</html>
```

**Access**: `https://aitradepulse.com/wiki/viewer.php`

### Option 6: VitePress (Vue-based SSG)

Modern, fast documentation site with Vue.js.

**Setup**:
```bash
npm init
npm install -D vitepress

# Create .vitepress/config.js
mkdir -p .vitepress
cat > .vitepress/config.js << 'EOF'
export default {
  title: 'AlgoExpertHub Documentation',
  description: 'Comprehensive technical documentation',
  themeConfig: {
    nav: [
      { text: 'Home', link: '/' },
      { text: 'Architecture', link: '/architecture/' },
      { text: 'API', link: '/api/' },
      { text: 'Guides', link: '/guides/' }
    ],
    sidebar: {
      '/': [
        {
          text: 'Getting Started',
          items: [
            { text: 'Project Overview', link: '/project-overview' },
            { text: 'Technology Stack', link: '/technology-stack' }
          ]
        },
        {
          text: 'Architecture',
          items: [
            { text: 'Overview', link: '/architecture/' },
            { text: 'Core Architecture', link: '/architecture/core' }
          ]
        }
      ]
    }
  }
}
EOF

# Build
npx vitepress build

# Deploy
cp -r .vitepress/dist/* main/public/wiki/
```

## Recommended Deployment

**For Production**: Option 1 (MkDocs) - Professional, searchable, mobile-responsive

**For Quick Setup**: Option 2 (Laravel Routes) - Integrated with existing app

**For Standalone**: Option 4 (Docker) - Isolated, scalable

## Post-Deployment

### Add to Main Navigation

Update `main/resources/views/layouts/app.blade.php`:
```blade
<li class="nav-item">
    <a class="nav-link" href="{{ url('/wiki') }}">
        <i class="fas fa-book"></i> Documentation
    </a>
</li>
```

### Enable Search Indexing

Add to `main/public/robots.txt`:
```
User-agent: *
Allow: /wiki/
Sitemap: https://aitradepulse.com/wiki/sitemap.xml
```

### Monitor Access

Add analytics to track documentation usage:
```javascript
// Google Analytics
gtag('config', 'GA_MEASUREMENT_ID', {
  'page_path': '/wiki' + window.location.pathname
});
```

## Maintenance

### Update Documentation

```bash
# Regenerate wiki with Qoder
# Then rebuild/redeploy using chosen method

# For MkDocs:
cd mkdocs-site && mkdocs build && cp -r site/* ../main/public/wiki/

# For Laravel routes:
# No rebuild needed - files are read dynamically

# For Docker:
docker build -f Dockerfile.docs -t algoexperthub-docs .
docker stop docs && docker rm docs
docker run -d -p 8080:80 --name docs algoexperthub-docs
```

## Troubleshooting

### Issue: Markdown not rendering
- Check file paths are correct
- Verify markdown parser is installed
- Check file permissions (644 for files, 755 for directories)

### Issue: Links broken
- Use relative paths in markdown
- Convert internal links during build
- Test all navigation links

### Issue: Search not working
- Rebuild search index
- Check JavaScript console for errors
- Verify search plugin is loaded

## Conclusion

Choose the deployment option that best fits your infrastructure and requirements. MkDocs (Option 1) is recommended for production due to its professional appearance, search functionality, and ease of maintenance.

---

**Document Version**: 1.0  
**Last Updated**: December 14, 2025  
**Maintained By**: Development Team

