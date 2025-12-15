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
     * Extracts HTML content from Blade template for visual editing
     * Returns array with 'html' and 'placeholders' keys
     */
    public function convertToPageBuilder(string $bladeContent): array
    {
        $content = $bladeContent;
        
        // First, replace Blade echo syntax with placeholders to prevent browser from treating them as URLs
        // Store replacements in a way that can be restored
        $placeholders = [];
        $counter = 0;
        
        // Replace {!! !!} (unescaped) with placeholder - use # prefix to prevent URL interpretation
        $content = preg_replace_callback('/\{!!\s*(.+?)\s*!!\}/s', function($matches) use (&$placeholders, &$counter) {
            $placeholder = '#blade-unescaped-' . $counter;
            $placeholders[$placeholder] = '{!! ' . $matches[1] . ' !!}';
            $counter++;
            return $placeholder;
        }, $content);
        
        // Replace {{ }} (escaped) with placeholder - use # prefix to prevent URL interpretation
        $content = preg_replace_callback('/\{\{\s*(.+?)\s*\}\}/s', function($matches) use (&$placeholders, &$counter) {
            $placeholder = '#blade-echo-' . $counter;
            $placeholders[$placeholder] = '{{ ' . $matches[1] . ' }}';
            $counter++;
            return $placeholder;
        }, $content);
        
        // Replace @php ... @endphp blocks with placeholder
        $content = preg_replace_callback('/@php.*?@endphp/s', function($matches) use (&$placeholders, &$counter) {
            $placeholder = '#blade-php-block-' . $counter;
            $placeholders[$placeholder] = $matches[0];
            $counter++;
            return ''; // Hide entirely from visual editor
        }, $content);

        /**
         * Replace <?php ... ?> blocks with placeholder
         */
        $content = preg_replace_callback('/<\?php.*?\?>/s', function($matches) use (&$placeholders, &$counter) {
            $placeholder = '#blade-raw-php-' . $counter;
            $placeholders[$placeholder] = $matches[0];
            $counter++;
            return '';
        }, $content);

        // Replace Blade directives that GrapesJS can't handle visually
        $content = preg_replace('/@extends\([^)]+\)\s*/', '', $content);
        $content = preg_replace('/@section\([^)]+\)\s*/', '', $content);
        $content = preg_replace('/@endsection\s*/', '', $content);
        $content = preg_replace('/@yield\([^)]+\)/', '', $content);
        $content = preg_replace('/@include\([^)]+\)/', '', $content);
        $content = preg_replace('/@stack\([^)]+\)/', '', $content);
        $content = preg_replace('/@push\([^)]+\)\s*/', '', $content);
        $content = preg_replace('/@endpush\s*/', '', $content);
        $content = preg_replace('/@if\s*\([^)]+\)\s*/', '', $content);
        $content = preg_replace('/@endif\s*/', '', $content);
        $content = preg_replace('/@foreach\s*\([^)]+\)\s*/', '', $content);
        $content = preg_replace('/@endforeach\s*/', '', $content);
        $content = preg_replace('/@elseif\s*\([^)]+\)\s*/', '', $content);
        $content = preg_replace('/@else\s*/', '', $content);
        
        // Clean up extra whitespace
        $content = preg_replace('/\n\s*\n/', "\n", $content);
        $content = trim($content);
        
        // Return both HTML and placeholders separately
        return [
            'html' => $content,
            'placeholders' => $placeholders
        ];
    }

    /**
     * Convert pagebuilder content back to Blade format
     * Wraps HTML content in Blade section structure
     */
    public function convertFromPageBuilder(string $pageBuilderContent, ?string $originalBladeContent = null, ?array $placeholders = null): string
    {
        // Restore Blade echo syntax from placeholders if provided
        if ($placeholders) {
            foreach ($placeholders as $placeholder => $original) {
                $pageBuilderContent = str_replace($placeholder, $original, $pageBuilderContent);
            }
        }
        
        // If we have original Blade content, try to preserve the structure
        if ($originalBladeContent) {
            // Extract Blade directives from original
            preg_match('/@extends\([^)]+\)/', $originalBladeContent, $extends);
            preg_match('/@section\([^)]+\)/', $originalBladeContent, $section);
            
            $bladeContent = '';
            
            // Add @extends if it existed
            if (!empty($extends[0])) {
                $bladeContent .= $extends[0] . "\n\n";
            }
            
            // Add @section if it existed, otherwise create default
            if (!empty($section[0])) {
                $bladeContent .= $section[0] . "\n";
            } else {
                $bladeContent .= "@section('content')\n";
            }
            
            // Add the pagebuilder content (with restored Blade syntax)
            $bladeContent .= trim($pageBuilderContent) . "\n";
            
            // Add @endsection
            $bladeContent .= "@endsection\n";
            
            // Preserve @stack, @push directives from original
            if (preg_match_all('/@(stack|push|endpush)\([^)]+\)/', $originalBladeContent, $stacks)) {
                foreach ($stacks[0] as $stack) {
                    $bladeContent .= $stack . "\n";
                }
            }
            
            return $bladeContent;
        }
        
        // Fallback: wrap in basic Blade structure
        return "@section('content')\n" . trim($pageBuilderContent) . "\n@endsection";
    }
}
