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
        
        // #region agent log
        file_put_contents('/home/algotrad/public_html/.cursor/debug.log', json_encode(['timestamp'=>time()*1000,'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'C','location'=>'Schema.php:build:entry','message'=>'Build method entry','data'=>['section_name'=>$sectionName,'active_theme'=>$activeTheme,'fields_has_theme'=>isset($fields[$activeTheme]),'available_themes'=>array_keys($fields)]], JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND);
        // #endregion
        
        if (!isset($fields[$activeTheme])) {
            // #region agent log
            file_put_contents('/home/algotrad/public_html/.cursor/debug.log', json_encode(['timestamp'=>time()*1000,'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'C','location'=>'Schema.php:build:missing_theme','message'=>'Theme missing in fields','data'=>['section_name'=>$sectionName,'active_theme'=>$activeTheme,'available_themes'=>array_keys($fields)]], JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND);
            // #endregion
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
            // #region agent log
            file_put_contents('/home/algotrad/public_html/.cursor/debug.log', json_encode(['timestamp'=>time()*1000,'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'B','location'=>'Schema.php:sectionHtml:entry','message'=>'sectionHtml entry','data'=>['section_name'=>$sectionName]], JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND);
            // #endregion
            
            $content = Helper::builder($sectionName);

            // #region agent log
            file_put_contents('/home/algotrad/public_html/.cursor/debug.log', json_encode(['timestamp'=>time()*1000,'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'B','location'=>'Schema.php:sectionHtml:after_builder','message'=>'After Helper::builder','data'=>['section_name'=>$sectionName,'content_exists'=>!is_null($content),'content_type'=>$content?get_class($content):null,'content_content'=>$content?($content->content??null):null]], JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND);
            // #endregion

            // Get current theme
            $activeTheme = Helper::config()->theme ?? 'default';
            
            // Check if this section class supports the current theme
            $hasThemeSupport = isset($this->fields[$activeTheme]);
            
            // #region agent log
            file_put_contents('/home/algotrad/public_html/.cursor/debug.log', json_encode(['timestamp'=>time()*1000,'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'C','location'=>'Schema.php:sectionHtml:theme_check','message'=>'Theme support check','data'=>['section_name'=>$sectionName,'active_theme'=>$activeTheme,'has_theme_support'=>$hasThemeSupport]], JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND);
            // #endregion
            
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
                // #region agent log
                @file_put_contents('/home/algotrad/public_html/.cursor/debug.log', json_encode(['timestamp'=>time()*1000,'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'C','location'=>'Schema.php:sectionHtml:skip','message'=>'Skipping section - no theme support and empty content','data'=>['section_name'=>$sectionName,'active_theme'=>$activeTheme,'has_theme_support'=>$hasThemeSupport,'content_exists'=>!is_null($content),'content_data_empty'=>empty($contentData)]], JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND | LOCK_EX);
                // #endregion
                return ''; // Skip sections that don't belong to current theme
            }
            
            // #region agent log
            @file_put_contents('/home/algotrad/public_html/.cursor/debug.log', json_encode(['timestamp'=>time()*1000,'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'C','location'=>'Schema.php:sectionHtml:not_skipping','message'=>'NOT skipping section','data'=>['section_name'=>$sectionName,'active_theme'=>$activeTheme,'has_theme_support'=>$hasThemeSupport,'content_exists'=>!is_null($content),'content_data_empty'=>empty($contentData)]], JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND | LOCK_EX);
            // #endregion
            
            $data['content'] = (object)$contentData;
            
            // #region agent log
            @file_put_contents('/home/algotrad/public_html/.cursor/debug.log', json_encode(['timestamp'=>time()*1000,'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'B','location'=>'Schema.php:sectionHtml:after_content_object','message'=>'After content object creation','data'=>['section_name'=>$sectionName,'content_is_object'=>is_object($data['content']),'content_keys'=>array_keys((array)$data['content'])]], JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND | LOCK_EX);
            // #endregion
            
            $data['element'] = Helper::builder($sectionName, true);
            
            $viewPath = Helper::themeView('widgets.'.$sectionName);
            
            // #region agent log
            @file_put_contents('/home/algotrad/public_html/.cursor/debug.log', json_encode(['timestamp'=>time()*1000,'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'D','location'=>'Schema.php:sectionHtml:before_view_exists','message'=>'Before view exists check','data'=>['section_name'=>$sectionName,'view_path'=>$viewPath,'view_exists'=>view()->exists($viewPath)]], JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND | LOCK_EX);
            // #endregion
            
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
            
            // #region agent log
            file_put_contents('/home/algotrad/public_html/.cursor/debug.log', json_encode(['timestamp'=>time()*1000,'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'D','location'=>'Schema.php:sectionHtml:before_view_render','message'=>'Before view render','data'=>['section_name'=>$sectionName,'view_path'=>$viewPath]], JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND);
            // #endregion
            
            $viewResult = view($viewPath)->with($data);
            
            // #region agent log
            file_put_contents('/home/algotrad/public_html/.cursor/debug.log', json_encode(['timestamp'=>time()*1000,'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'D','location'=>'Schema.php:sectionHtml:after_view_render','message'=>'After view render','data'=>['section_name'=>$sectionName,'view_result_type'=>get_class($viewResult)]], JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND);
            // #endregion
            
            return $viewResult;
        } catch (\Exception $e) {
            // #region agent log
            file_put_contents('/home/algotrad/public_html/.cursor/debug.log', json_encode(['timestamp'=>time()*1000,'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'D','location'=>'Schema.php:sectionHtml:exception','message'=>'Exception in sectionHtml','data'=>['section_name'=>$sectionName,'error'=>$e->getMessage(),'file'=>$e->getFile(),'line'=>$e->getLine(),'trace'=>$e->getTraceAsString()]], JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND);
            // #endregion
            
            Log::error('Section HTML error', [
                'section' => $sectionName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return '<!-- Section HTML error: ' . htmlspecialchars($sectionName) . ' - ' . htmlspecialchars($e->getMessage()) . ' -->';
        }
    }
}
