<?php

namespace App\Utility;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SectionBuilder
{

    public static function render($section)
    {
        try {
            // #region agent log
            file_put_contents('/home/algotrad/public_html/.cursor/debug.log', json_encode(['timestamp'=>time()*1000,'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'A,E','location'=>'SectionBuilder.php:render:entry','message'=>'Section render entry','data'=>['raw_section'=>is_string($section)?$section:json_encode($section),'type'=>gettype($section)]], JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND);
            // #endregion
            
            // Handle array input (from PageSection model cast)
            if (is_array($section)) {
                // Extract the section name from array (usually first element or 'name' key)
                $section = is_array($section) && !empty($section) ? (isset($section['name']) ? $section['name'] : (is_string($section[0] ?? null) ? $section[0] : json_encode($section))) : json_encode($section);
            }
            // Handle JSON string input or quoted strings
            if (is_string($section)) {
                // Try to decode as JSON first
                $decoded = json_decode($section, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    // Successfully decoded JSON
                    if (is_array($decoded)) {
                        $section = is_string($decoded[0] ?? null) ? $decoded[0] : (isset($decoded['name']) ? $decoded['name'] : $section);
                    } elseif (is_string($decoded)) {
                        // JSON decoded to a string (e.g., "banner" -> banner)
                        $section = $decoded;
                    }
                } else {
                    // Not valid JSON, try removing quotes
                    $trimmed = trim($section, '"\'');
                    if ($trimmed !== $section) {
                        $section = $trimmed;
                    }
                }
            }
            
            // #region agent log
            file_put_contents('/home/algotrad/public_html/.cursor/debug.log', json_encode(['timestamp'=>time()*1000,'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'A','location'=>'SectionBuilder.php:render:before_classmap','message'=>'Before classMap','data'=>['section_name'=>$section]], JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND);
            // #endregion
            
            $class = self::classMap($section);
            
            // #region agent log
            file_put_contents('/home/algotrad/public_html/.cursor/debug.log', json_encode(['timestamp'=>time()*1000,'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'A','location'=>'SectionBuilder.php:render:after_classmap','message'=>'After classMap','data'=>['section_name'=>$section,'class'=>get_class($class)]], JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND);
            // #endregion
            
            $result = $class->sectionHtml($section);
            
            // #region agent log
            file_put_contents('/home/algotrad/public_html/.cursor/debug.log', json_encode(['timestamp'=>time()*1000,'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'D','location'=>'SectionBuilder.php:render:after_render','message'=>'After sectionHtml','data'=>['section_name'=>$section,'result_type'=>gettype($result),'result_length'=>is_string($result)?strlen($result):null]], JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND);
            // #endregion
            
            return $result;
        } catch (\Exception $e) {
            // #region agent log
            file_put_contents('/home/algotrad/public_html/.cursor/debug.log', json_encode(['timestamp'=>time()*1000,'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'D','location'=>'SectionBuilder.php:render:exception','message'=>'Exception caught','data'=>['section'=>is_string($section)?$section:json_encode($section),'error'=>$e->getMessage(),'file'=>$e->getFile(),'line'=>$e->getLine()]], JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND);
            // #endregion
            Log::error('Section render error', [
                'section' => $section,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return '<!-- Section render error: ' . htmlspecialchars(is_string($section) ? $section : json_encode($section)) . ' -->';
        }
    }

    public static function classMap($request)
    {
        try {
            // #region agent log
            file_put_contents('/home/algotrad/public_html/.cursor/debug.log', json_encode(['timestamp'=>time()*1000,'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'A','location'=>'SectionBuilder.php:classMap:entry','message'=>'classMap entry','data'=>['request'=>$request]], JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND);
            // #endregion
            
            $element = ucfirst(Str::camel($request));
            
            // #region agent log
            file_put_contents('/home/algotrad/public_html/.cursor/debug.log', json_encode(['timestamp'=>time()*1000,'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'A','location'=>'SectionBuilder.php:classMap:after_camel','message'=>'After Str::camel','data'=>['request'=>$request,'camel_case'=>$element]], JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND);
            // #endregion

            $section = __NAMESPACE__ . '\\Sections\\' . $element;
            
            // #region agent log
            file_put_contents('/home/algotrad/public_html/.cursor/debug.log', json_encode(['timestamp'=>time()*1000,'sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'A','location'=>'SectionBuilder.php:classMap:before_exists','message'=>'Before class_exists','data'=>['class_name'=>$section,'exists'=>class_exists($section)]], JSON_UNESCAPED_SLASHES)."\n", FILE_APPEND);
            // #endregion

            if (!class_exists($section)) {
                Log::error('Section class not found', [
                    'section' => $request,
                    'class' => $section
                ]);
                throw new \Exception("Section class not found: {$section}");
            }

            return new $section();
        } catch (\Exception $e) {
            Log::error('Section classMap error', [
                'section' => $request,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
