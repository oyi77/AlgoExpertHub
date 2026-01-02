<?php

namespace App\Services;

use App\Models\Content;
use App\Models\Language;

class LanguageService
{
    public function create($request)
    {
        // Validate language code to prevent path traversal
        if (!preg_match('/^[a-z]{2}(-[A-Z]{2})?$/', $request->code)) {
            return ['type' => 'error', 'message' => 'Invalid language code format'];
        }

        Language::create([
            'name' => $request->language,
            'code' => $request->code,
            'status' => 1
        ]);

        $path = resource_path('lang/');
        $sectionPath = resource_path('lang/sections/');

        // Ensure directories exist
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        if (!is_dir($sectionPath)) {
            mkdir($sectionPath, 0755, true);
        }

        // Get default language (English) keys
        $defaultLangPath = $path . 'en.json';
        $defaultSectionPath = $sectionPath . 'en.json';
        
        // Initialize with default language keys but empty values
        $defaultKeys = [];
        $defaultSectionKeys = [];
        
        if (file_exists($defaultLangPath)) {
            $content = file_get_contents($defaultLangPath);
            if ($content === false) {
                \Log::error('Failed to read default language file', ['path' => $defaultLangPath]);
            } else {
                $defaultTranslations = json_decode($content, true);
                if (json_last_error() === JSON_ERROR_NONE && $defaultTranslations) {
                    $defaultKeys = array_fill_keys(array_keys($defaultTranslations), '');
                }
            }
        }
        
        if (file_exists($defaultSectionPath)) {
            $content = file_get_contents($defaultSectionPath);
            if ($content === false) {
                \Log::error('Failed to read default section file', ['path' => $defaultSectionPath]);
            } else {
                $defaultSectionTranslations = json_decode($content, true);
                if (json_last_error() === JSON_ERROR_NONE && $defaultSectionTranslations) {
                    $defaultSectionKeys = array_fill_keys(array_keys($defaultSectionTranslations), '');
                }
            }
        }
        
        // Create new language files with default keys
        $newLangPath = $path . "{$request->code}.json";
        $newSectionPath = $sectionPath . "{$request->code}.json";
        
        $result1 = file_put_contents($newLangPath, json_encode($defaultKeys, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $result2 = file_put_contents($newSectionPath, json_encode($defaultSectionKeys, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        if ($result1 === false || $result2 === false) {
            return ['type' => 'error', 'message' => 'Failed to create language files'];
        }

        return ['type' => 'success', 'message' => 'Language Created Successfully'];
    }

    public function update($request)
    {
        $language = Language::find($request->id);

        if (!$language) {
            return ['type' => 'error', 'message' => 'Language Not Found'];
        }

        // Validate language code to prevent path traversal
        if (!preg_match('/^[a-z]{2}(-[A-Z]{2})?$/', $request->code)) {
            return ['type' => 'error', 'message' => 'Invalid language code format'];
        }

        $language->update([
            'name' => $request->language,
            'code' => $request->code
        ]);

        $path = resource_path() . "/lang/{$language->code}.json";
        $sectionPath = resource_path() . "/lang/sections/{$language->code}.json";

        // Handle section path
        if (file_exists($sectionPath)) {
            $content = file_get_contents($sectionPath);
            if ($content !== false) {
                $file_data = json_encode($content);
                unlink($sectionPath);
                file_put_contents($sectionPath, json_decode($file_data));
            }
        } else {
            $newPath = resource_path('lang/sections/') . "{$request->code}.json";
            $handle = fopen($newPath, "w");
            if ($handle) {
                fclose($handle);
                file_put_contents($newPath, '{}');
            }
        }

        // Handle main language path
        if (file_exists($path)) {
            $content = file_get_contents($path);
            if ($content !== false) {
                $file_data = json_encode($content);
                unlink($path);
                file_put_contents($path, json_decode($file_data));
            }
        } else {
            $newPath = resource_path() . "/lang/{$request->code}.json";
            $handle = fopen($newPath, "w");
            if ($handle) {
                fclose($handle);
                file_put_contents($newPath, '{}');
            }
        }

        return ['type' => 'success', 'message' => 'Language Updated Successfully'];
    }

    public function delete($request)
    {
        $language = Language::find($request->id);

        if (!$language) {
            return ['type' => 'error', 'message' => 'Language Not Found'];
        }

        Content::where('language_id', $language->id)->get()->map(function ($item) {
            $item->delete();
        });

        if ($language->is_default) {
            return ['type' => 'error', 'message' => 'Default Language Can not Deleted'];
        }

        $path = resource_path() . "/lang/$language->code.json";

        if (file_exists($path)) {
            unlink($path);
        }


        $sectionPath = resource_path() . "/lang/sections/$language->code.json";

        if (file_exists($sectionPath)) {
            unlink($sectionPath);
        }



        if (session('locale') == $language->code) {

            session()->forget('locale');
        }

        $language->delete();


        return ['type' => 'success', 'message' => 'Language Deleted Successfully'];
    }
}
