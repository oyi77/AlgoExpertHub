<?php

namespace App\Services;

use App\Models\Content;
use App\Models\Language;

class LanguageService
{
    public function create($request)
    {
        Language::create([
            'name' => $request->language,
            'code' => $request->code,
            'status' => 1
        ]);

        $path = resource_path('lang/');
        $sectionPath = resource_path('lang/sections/');

        // Get default language (English) keys
        $defaultLangPath = $path . 'en.json';
        $defaultSectionPath = $sectionPath . 'en.json';
        
        // Initialize with default language keys but empty values
        $defaultKeys = [];
        $defaultSectionKeys = [];
        
        if (file_exists($defaultLangPath)) {
            $defaultTranslations = json_decode(file_get_contents($defaultLangPath), true);
            if ($defaultTranslations) {
                // Create array with same keys but empty values
                $defaultKeys = array_fill_keys(array_keys($defaultTranslations), '');
            }
        }
        
        if (file_exists($defaultSectionPath)) {
            $defaultSectionTranslations = json_decode(file_get_contents($defaultSectionPath), true);
            if ($defaultSectionTranslations) {
                // Create array with same keys but empty values
                $defaultSectionKeys = array_fill_keys(array_keys($defaultSectionTranslations), '');
            }
        }
        
        // Create new language files with default keys
        file_put_contents($path . "$request->code.json", json_encode($defaultKeys, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        file_put_contents($sectionPath . "$request->code.json", json_encode($defaultSectionKeys, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return ['type' => 'success', 'message' => 'Language Created Successfully'];
    }

    public function update($request)
    {
        $language = Language::find($request->id);

        if (!$language) {
            return ['type' => 'error', 'message' => 'Language Not Found'];
        }


        $language->update([
            'name' => $request->language,
            'code' => $request->code
        ]);

        $path = resource_path() . "/lang/$language->code.json";

        $sectionPath = resource_path() . "/lang/sections/$language->code.json";



        if (file_exists($sectionPath)) {

            $file_data = json_encode(file_get_contents($sectionPath));

            unlink($sectionPath);

            file_put_contents($sectionPath, json_decode($file_data));
        } else {

            fopen(resource_path('lang/sections/') . "$request->code.json", "w");

            file_put_contents(resource_path() . "/lang/sections/$request->code.json", '{}');
        }


        if (file_exists($path)) {

            $file_data = json_encode(file_get_contents($path));

            unlink($path);

            file_put_contents($path, json_decode($file_data));
        } else {

            fopen(resource_path() . "/lang/$request->code.json", "w");

            file_put_contents(resource_path() . "/lang/$request->code.json", '{}');
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
