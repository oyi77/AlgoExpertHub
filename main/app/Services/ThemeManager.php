<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use ZipArchive;

class ThemeManager
{
    protected string $themesPath;
    protected string $viewsPath;

    public function __construct()
    {
        $this->themesPath = public_path('asset/frontend');
        $this->viewsPath = resource_path('views/frontend');
    }

    /**
     * List all installed themes
     */
    public function list(): Collection
    {
        if (!File::isDirectory($this->themesPath)) {
            return collect();
        }

        return collect(File::directories($this->themesPath))
            ->map(function (string $directory) {
                $themeName = basename($directory);
                $isActive = $this->isActiveTheme($themeName);
                
                return [
                    'name' => $themeName,
                    'display_name' => Str::title(str_replace(['-', '_'], ' ', $themeName)),
                    'path' => $directory,
                    'is_active' => $isActive,
                    'exists' => $this->themeExists($themeName),
                ];
            })
            ->filter(function ($theme) {
                return $theme['exists'];
            })
            ->values();
    }

    /**
     * Check if theme exists (has both assets and views)
     */
    public function themeExists(string $themeName): bool
    {
        $assetsPath = $this->themesPath . '/' . $themeName;
        $viewsPath = $this->viewsPath . '/' . $themeName;

        return File::isDirectory($assetsPath) && File::isDirectory($viewsPath);
    }

    /**
     * Check if theme is currently active
     */
    public function isActiveTheme(string $themeName): bool
    {
        try {
            $config = \App\Models\Configuration::first();
            return $config && $config->theme === $themeName;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Upload and install a theme from ZIP archive
     */
    public function upload(UploadedFile $package): array
    {
        $this->assertZipArchiveAvailable();

        if ($package->getClientOriginalExtension() !== 'zip') {
            throw new RuntimeException('Theme package must be a ZIP archive.');
        }

        $tmpRoot = storage_path('app/theme_uploads/' . Str::uuid()->toString());
        File::ensureDirectoryExists($tmpRoot);

        $zipPath = $tmpRoot . '/package.zip';
        $package->move($tmpRoot, 'package.zip');

        $extractPath = $tmpRoot . '/extract';
        File::ensureDirectoryExists($extractPath);

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('Unable to open theme archive.');
        }

        if (!$zip->extractTo($extractPath)) {
            $zip->close();
            throw new RuntimeException('Unable to extract theme archive.');
        }
        $zip->close();
        File::delete($zipPath);

        try {
            $themeRoot = $this->findThemeRoot($extractPath);
            if ($themeRoot === null) {
                throw new RuntimeException('Theme archive must contain a valid theme structure.');
            }

            $themeName = $this->extractThemeName($themeRoot);
            
            if (empty($themeName)) {
                throw new RuntimeException('Could not determine theme name from archive structure.');
            }

            // Validate theme structure
            $this->validateThemeStructure($themeRoot, $themeName);

            // Check if theme already exists
            if ($this->themeExists($themeName)) {
                throw new RuntimeException("Theme [{$themeName}] already exists. Please delete it first or use a different name.");
            }

            // Determine assets and views source paths
            $assetsSource = null;
            $viewsSource = null;

            // Pattern 1: Standard structure - assets/ and views/ at root
            if (File::exists($themeRoot . '/assets')) {
                $assetsSource = $themeRoot . '/assets';
            }
            
            if (File::exists($themeRoot . '/views')) {
                $viewsSource = $themeRoot . '/views';
            }
            
            // Pattern 2: Theme-named directory contains assets and views
            if (!$assetsSource && File::exists($themeRoot . '/' . $themeName)) {
                $themeDir = $themeRoot . '/' . $themeName;
                if (File::exists($themeDir . '/assets')) {
                    $assetsSource = $themeDir . '/assets';
                } else if ($this->isThemeAssetsDirectory($themeDir)) {
                    $assetsSource = $themeDir;
                }
                
                if (File::exists($themeDir . '/views')) {
                    $viewsSource = $themeDir . '/views';
                }
            }
            
            // Pattern 3: Root is assets directory (check subdirectories)
            if (!$assetsSource && $this->isThemeAssetsDirectory($themeRoot)) {
                $assetsSource = $themeRoot;
            }

            // If still no assets found, check all subdirectories
            if (!$assetsSource) {
                $directories = File::directories($themeRoot);
                foreach ($directories as $dir) {
                    if ($this->isThemeAssetsDirectory($dir)) {
                        $assetsSource = $dir;
                        break;
                    }
                }
            }

            if (!$assetsSource) {
                throw new RuntimeException('Could not locate theme assets directory. Please ensure your ZIP contains an assets directory or theme-named folder.');
            }

            // Install theme assets
            $assetsDestination = $this->themesPath . '/' . $themeName;
            File::ensureDirectoryExists(dirname($assetsDestination));
            
            if (File::exists($assetsDestination)) {
                File::deleteDirectory($assetsDestination);
            }
            
            File::moveDirectory($assetsSource, $assetsDestination);

            // Install theme views
            $viewsDestination = $this->viewsPath . '/' . $themeName;
            File::ensureDirectoryExists(dirname($viewsDestination));
            
            if ($viewsSource && File::exists($viewsSource)) {
                if (File::exists($viewsDestination)) {
                    File::deleteDirectory($viewsDestination);
                }
                File::moveDirectory($viewsSource, $viewsDestination);
            } else {
                // Create minimal views directory structure with required master.blade.php
                if (File::exists($viewsDestination)) {
                    File::deleteDirectory($viewsDestination);
                }
                File::ensureDirectoryExists($viewsDestination);
                File::ensureDirectoryExists($viewsDestination . '/layout');
                
                // Create minimal master.blade.php
                $masterContent = <<<'BLADE'
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    <link href="{{ asset('asset/frontend/' . config('app.theme', 'default') . '/css/main.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body>
    <div id="app">
        @yield('content')
    </div>
    <script src="{{ asset('asset/frontend/' . config('app.theme', 'default') . '/js/main.js') }}"></script>
    @stack('scripts')
</body>
</html>
BLADE;
                File::put($viewsDestination . '/layout/master.blade.php', $masterContent);
            }

            return [
                'name' => $themeName,
                'display_name' => Str::title(str_replace(['-', '_'], ' ', $themeName)),
            ];
        } catch (Throwable $exception) {
            throw $exception;
        } finally {
            if (File::isDirectory($tmpRoot)) {
                File::deleteDirectory($tmpRoot);
            }
        }
    }

    /**
     * Create a downloadable template theme ZIP
     */
    public function downloadTemplate(): string
    {
        $templateThemeName = 'my-custom-theme';
        $tmpPath = storage_path('app/theme_templates/' . Str::uuid()->toString());
        File::ensureDirectoryExists($tmpPath);

        $zipPath = storage_path('app/theme_templates/theme-template-' . date('Y-m-d-His') . '.zip');

        // Ensure theme_templates directory exists
        File::ensureDirectoryExists(dirname($zipPath));

        // Create template structure
        $templateDir = $tmpPath;
        File::ensureDirectoryExists($templateDir);

        // Create assets directory structure
        $assetsDir = $templateDir . '/assets';
        File::ensureDirectoryExists($assetsDir);
        File::ensureDirectoryExists($assetsDir . '/css');
        File::ensureDirectoryExists($assetsDir . '/js');
        File::ensureDirectoryExists($assetsDir . '/images');
        File::ensureDirectoryExists($assetsDir . '/fonts');
        File::ensureDirectoryExists($assetsDir . '/webfonts');

        // Create sample files
        $this->createTemplateFiles($assetsDir);

        // Create views directory structure (minimal structure)
        $viewsDir = $templateDir . '/views';
        File::ensureDirectoryExists($viewsDir);
        File::ensureDirectoryExists($viewsDir . '/layout');
        File::ensureDirectoryExists($viewsDir . '/auth');
        File::ensureDirectoryExists($viewsDir . '/user');
        File::ensureDirectoryExists($viewsDir . '/widgets');

        // Create sample view files
        $this->createTemplateViews($viewsDir);

        // Create README
        $readme = $this->generateReadme($templateThemeName);
        File::put($templateDir . '/README.md', $readme);

        // Create theme.json manifest
        $manifest = [
            'name' => $templateThemeName,
            'version' => '1.0.0',
            'description' => 'Custom theme template for AlgoExpertHub',
            'author' => 'Your Name',
        ];
        File::put($templateDir . '/theme.json', json_encode($manifest, JSON_PRETTY_PRINT));

        // Create ZIP archive
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Unable to create template ZIP archive.');
        }

        $this->addDirectoryToZip($templateDir, $zip, '');

        $zip->close();

        // Cleanup
        File::deleteDirectory($tmpPath);

        return $zipPath;
    }

    /**
     * Find theme root in extracted archive
     */
    protected function findThemeRoot(string $extractPath): ?string
    {
        // Check if root contains theme structure
        if ($this->hasThemeStructure($extractPath)) {
            return $extractPath;
        }

        // Check subdirectories
        $directories = File::directories($extractPath);
        foreach ($directories as $directory) {
            if ($this->hasThemeStructure($directory)) {
                return $directory;
            }
        }

        return null;
    }

    /**
     * Check if directory has valid theme structure
     */
    protected function hasThemeStructure(string $path): bool
    {
        $hasAssets = File::exists($path . '/assets') || 
                     File::isDirectory($path) && (count(File::directories($path)) > 0 || count(File::files($path)) > 0);
        $hasViews = File::exists($path . '/views') || 
                    File::exists($path . '/resources/views');
        
        return $hasAssets || $hasViews;
    }

    /**
     * Check if directory looks like a theme assets directory
     */
    protected function isThemeAssetsDirectory(string $path): bool
    {
        if (!File::isDirectory($path)) {
            return false;
        }

        // Check for common theme asset subdirectories
        $commonDirs = ['css', 'js', 'images', 'fonts', 'webfonts'];
        $foundDirs = 0;
        
        foreach ($commonDirs as $dir) {
            if (File::isDirectory($path . '/' . $dir)) {
                $foundDirs++;
            }
        }

        // If found at least 2 common directories, likely a theme assets dir
        return $foundDirs >= 2;
    }

    /**
     * Extract theme name from archive structure
     */
    protected function extractThemeName(string $themeRoot): string
    {
        // Try to get name from manifest or directory structure
        if (File::exists($themeRoot . '/theme.json')) {
            $manifest = json_decode(File::get($themeRoot . '/theme.json'), true);
            if (isset($manifest['name'])) {
                return $this->normalizeThemeName($manifest['name']);
            }
        }

        // Try to infer from directory structure
        if (File::exists($themeRoot . '/assets')) {
            $assetsDirs = File::directories($themeRoot);
            foreach ($assetsDirs as $dir) {
                $name = basename($dir);
                if ($name !== 'assets' && $name !== 'views' && $name !== 'resources') {
                    return $this->normalizeThemeName($name);
                }
            }
        }

        // Use directory name as fallback
        return $this->normalizeThemeName(basename($themeRoot));
    }

    /**
     * Normalize theme name (sanitize, lowercase, etc.)
     */
    protected function normalizeThemeName(string $name): string
    {
        $name = strtolower(trim($name));
        $name = preg_replace('/[^a-z0-9_-]/', '', $name);
        return $name ?: 'custom-theme';
    }

    /**
     * Validate theme structure
     */
    protected function validateThemeStructure(string $themeRoot, string $themeName): void
    {
        $hasAssets = File::exists($themeRoot . '/assets') || 
                     File::isDirectory($themeRoot);
        $hasViews = File::exists($themeRoot . '/views') || 
                    File::exists($themeRoot . '/resources/views');

        if (!$hasAssets && !$hasViews) {
            throw new RuntimeException('Theme archive must contain either assets or views directory.');
        }
    }

    /**
     * Create template files
     */
    protected function createTemplateFiles(string $assetsDir): void
    {
        // Create sample CSS
        $cssContent = <<<'CSS'
/* Theme Template - Main CSS File */
/* Replace this with your custom styles */

body {
    font-family: Arial, sans-serif;
}

/* Add your styles here */
CSS;
        File::put($assetsDir . '/css/main.css', $cssContent);

        // Create sample JS
        $jsContent = <<<'JS'
// Theme Template - Main JS File
// Replace this with your custom scripts

document.addEventListener('DOMContentLoaded', function() {
    // Add your scripts here
});
JS;
        File::put($assetsDir . '/js/main.js', $jsContent);

        // Create placeholder README
        File::put($assetsDir . '/README.txt', 'Place your theme assets (CSS, JS, images, fonts) in this directory.');
    }

    /**
     * Create template views
     */
    protected function createTemplateViews(string $viewsDir): void
    {
        // Create master layout template
        $masterContent = <<<'BLADE'
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', config('app.name'))</title>
    
    <!-- Styles -->
    <link href="{{ asset('asset/frontend/my-custom-theme/css/main.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body>
    <div id="app">
        @include('frontend.my-custom-theme.layout.header')
        
        <main>
            @yield('content')
        </main>
        
        @include('frontend.my-custom-theme.layout.footer')
    </div>
    
    <!-- Scripts -->
    <script src="{{ asset('asset/frontend/my-custom-theme/js/main.js') }}"></script>
    @stack('scripts')
</body>
</html>
BLADE;
        File::ensureDirectoryExists($viewsDir . '/layout');
        File::put($viewsDir . '/layout/master.blade.php', $masterContent);

        // Create README for views
        File::put($viewsDir . '/README.txt', 'Place your Blade view files here. Required: layout/master.blade.php');
    }

    /**
     * Generate README content
     */
    protected function generateReadme(string $themeName): string
    {
        return <<<README
# Theme Template

This is a template theme structure for AlgoExpertHub.

## Directory Structure

Your ZIP file should have one of these structures:

**Option 1: Standard Structure (Recommended)**
```
your-zip.zip
├── assets/              # Theme assets
│   ├── css/            # Stylesheets
│   ├── js/             # JavaScript files
│   ├── images/         # Images
│   ├── fonts/          # Custom fonts
│   └── webfonts/       # Web fonts
└── views/              # Blade view templates
    ├── layout/         # Layout templates (master.blade.php required)
    │   └── master.blade.php
    ├── auth/           # Authentication views
    ├── user/           # User dashboard views
    └── widgets/        # Widget components
```

**Option 2: Theme-Named Folder**
```
your-zip.zip
└── my-theme-name/
    ├── assets/         # Theme assets
    └── views/          # Blade view templates
```

## Installation

1. Customize the theme files in this template
2. Rename files/directories as needed
3. Zip the assets/ and views/ directories (or theme folder)
4. Upload via Admin Panel > Manage Theme > Upload Theme ZIP

## Requirements

- **Required**: `views/layout/master.blade.php` - Main layout template
- Assets should be organized in the assets directory
- Follow the existing theme structure for compatibility

## Theme Naming

- Theme name will be automatically detected from directory structure
- Use lowercase, hyphens, or underscores (e.g., `my-theme`, `my_theme`)
- Avoid spaces and special characters

## Testing

- Make sure to test your theme before uploading
- Check that all Blade templates extend the master layout correctly
- Verify asset paths use: `asset('asset/frontend/your-theme-name/...')`

## Notes

- After uploading, you can activate the theme from Manage Theme page
- Backup your theme before making changes
README;
    }

    /**
     * Add directory to ZIP recursively
     */
    protected function addDirectoryToZip(string $dir, ZipArchive $zip, string $basePath = ''): void
    {
        $files = File::allFiles($dir);
        
        foreach ($files as $file) {
            $relativePath = $basePath ? $basePath . '/' . $file->getRelativePathname() : $file->getRelativePathname();
            // Normalize path separators for ZIP
            $relativePath = str_replace('\\', '/', $relativePath);
            $zip->addFile($file->getPathname(), $relativePath);
        }
    }

    /**
     * Ensure ZipArchive extension is installed
     */
    protected function assertZipArchiveAvailable(): void
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('ZipArchive PHP extension is required for theme upload/download.');
        }
    }
}
