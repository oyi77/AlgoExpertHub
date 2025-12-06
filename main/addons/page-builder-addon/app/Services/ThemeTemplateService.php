<?php

namespace Addons\PageBuilderAddon\App\Services;

use App\Helpers\Helper\Helper;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ThemeTemplateService
{
    /**
     * Load theme template file
     */
    public function loadThemeTemplate(string $themeName, string $templatePath): array
    {
        try {
            $fullPath = resource_path("views/frontend/{$themeName}/{$templatePath}");
            
            if (!File::exists($fullPath)) {
                return [
                    'type' => 'error',
                    'message' => 'Template file not found'
                ];
            }

            $content = File::get($fullPath);

            return [
                'type' => 'success',
                'data' => [
                    'content' => $content,
                    'path' => $templatePath
                ]
            ];
        } catch (\Exception $e) {
            Log::error('ThemeTemplateService::loadThemeTemplate failed', ['error' => $e->getMessage()]);
            
            return [
                'type' => 'error',
                'message' => 'Failed to load template: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Save theme template
     */
    public function saveThemeTemplate(string $themeName, string $templatePath, string $content): array
    {
        try {
            $fullPath = resource_path("views/frontend/{$themeName}/{$templatePath}");
            $directory = dirname($fullPath);
            
            if (!File::isDirectory($directory)) {
                File::makeDirectory($directory, 0755, true);
            }

            File::put($fullPath, $content);

            return [
                'type' => 'success',
                'message' => 'Template saved successfully'
            ];
        } catch (\Exception $e) {
            Log::error('ThemeTemplateService::saveThemeTemplate failed', ['error' => $e->getMessage()]);
            
            return [
                'type' => 'error',
                'message' => 'Failed to save template: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Convert Blade template to pagebuilder format
     */
    public function convertToPageBuilder(string $bladeContent): string
    {
        // Basic conversion: Remove Blade directives that GrapesJS can't handle
        // Keep basic HTML structure
        $content = $bladeContent;
        
        // Remove @extends, @section, @yield directives (keep content)
        $content = preg_replace('/@extends\([^)]+\)/', '', $content);
        $content = preg_replace('/@section\([^)]+\)/', '', $content);
        $content = preg_replace('/@endsection/', '', $content);
        $content = preg_replace('/@yield\([^)]+\)/', '', $content);
        
        // Keep @if, @foreach, etc. as they can be converted to HTML
        // For now, return as-is - GrapesJS can handle most HTML
        
        return $content;
    }

    /**
     * Convert pagebuilder content to Blade
     */
    public function convertFromPageBuilder(string $pageBuilderContent): string
    {
        // Basic conversion: Wrap in Blade section if needed
        // For now, return as-is - can be enhanced later
        return $pageBuilderContent;
    }
}
