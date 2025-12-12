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

            $data['content'] = $content ? optional($content)->content : null;
            
            $data['element'] = Helper::builder($sectionName, true);
            
            $viewPath = Helper::themeView('widgets.'.$sectionName);
            
            if (!view()->exists($viewPath)) {
                Log::error('Section widget view not found', [
                    'section' => $sectionName,
                    'view_path' => $viewPath
                ]);
                return '<!-- Section widget not found: ' . htmlspecialchars($sectionName) . ' -->';
            }
            
            return view($viewPath)->with($data);
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
