<?php

namespace App\Utility;

use App\Helpers\Helper\Helper;
use App\Models\Configuration;
use Illuminate\Support\Facades\Log;

trait Schema
{

    public function schema($data, $sectionName, $type)
    {

        if ($type == 'element') {
            return $this->build($data, $this->elementFields,  $this->elementClasses, $sectionName);
        }


        if ($this->has_content) {
            return $this->build($data, $this->fields, $this->classes,  $sectionName);
        }
    }


    public function build($data, $fields, $classes, $sectionName)
    {
        $content = '';

        $activeTheme = Helper::config()->theme;
        
        
        if (!isset($fields[$activeTheme])) {
            return ''; // Return empty if theme not configured
        }

        foreach ($fields[$activeTheme] as $key => $value) {
            $elem = [
                'class' => $classes[$activeTheme][$key] ?? '',
                'type' => $value,
                'name' => $key,
                'value' => $data->content->$key ?? '',
                'section' => $sectionName,
            ];

            $section = __NAMESPACE__ . '\\Elements\\' . $value;

            $class = new $section();

            $content .= $class->generate($elem);

        }


        return $content;
    }


    public function generateHtml($data, $section,$type)
    {
        return [
            'html' => $this->schema($data, $section,$type),
            'image_id' => $this->image_upload_ids,
            'has_element' => $this->has_element,
            'has_content' => $this->has_content
        ];
    }


    public function sectionHtml($sectionName)
    {
        try {
            
            $content = Helper::builder($sectionName);


            // Get current theme
            $activeTheme = Helper::config()->theme ?? 'default';
            
            // Check if this section class supports the current theme
            $hasThemeSupport = isset($this->fields[$activeTheme]);
            
            
            // Always pass content as object to avoid null property access errors
            // Extract content data first
            if (!$content) {
                $contentData = [];
            } else {
                $contentData = is_array($content->content ?? null) ? $content->content : (array)($content->content ?? []);
            }
            
            // If section doesn't support current theme AND content is empty, skip rendering
            // This prevents fallback to default theme views with empty content
            if (!$hasThemeSupport && empty($contentData)) {
                return ''; // Skip sections that don't belong to current theme
            }
            
            
            $data['content'] = (object)$contentData;
            
            
            $data['element'] = Helper::builder($sectionName, true);
            
            $viewPath = Helper::themeView('widgets.'.$sectionName);
            
            
            if (!view()->exists($viewPath)) {
                Log::error('Section widget view not found', [
                    'section' => $sectionName,
                    'view_path' => $viewPath
                ]);
                // If view doesn't exist and we don't have theme support, skip instead of error
                if (!$hasThemeSupport) {
                    return '';
                }
                return '<!-- Section widget not found: ' . htmlspecialchars($sectionName) . ' -->';
            }
            
            
            $viewResult = view($viewPath)->with($data);
            
            
            return $viewResult;
        } catch (\Exception $e) {
            
            Log::error('Section HTML error', [
                'section' => $sectionName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return '<!-- Section HTML error: ' . htmlspecialchars($sectionName) . ' - ' . htmlspecialchars($e->getMessage()) . ' -->';
        }
    }
}
