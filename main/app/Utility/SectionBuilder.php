<?php

namespace App\Utility;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SectionBuilder
{

    public static function render($section)
    {
        try {
            $class = self::classMap($section);
            return $class->sectionHtml($section);
        } catch (\Exception $e) {
            Log::error('Section render error', [
                'section' => $section,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return '<!-- Section render error: ' . htmlspecialchars($section) . ' -->';
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
