<?php

namespace App\Utility;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SectionBuilder
{

    public static function render($section)
    {
        try {
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
            $class = self::classMap($section);
            return $class->sectionHtml($section);
        } catch (\Exception $e) {
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
            $element = ucfirst(Str::camel($request));

            $section = __NAMESPACE__ . '\\Sections\\' . $element;

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
