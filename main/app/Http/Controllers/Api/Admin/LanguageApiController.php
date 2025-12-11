<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

/**
 * @group Admin - Language Management
 *
 * Endpoints for managing languages and translations.
 */
class LanguageApiController extends Controller
{
    /**
     * List Languages
     */
    public function index()
    {
        $languages = Language::all();
        return response()->json(['success' => true, 'data' => $languages]);
    }

    /**
     * Create Language
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:languages,name',
            'code' => 'required|string|size:2|unique:languages,code',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $language = Language::create($validator->validated());

        return response()->json(['success' => true, 'data' => $language], 201);
    }

    /**
     * Update Language
     */
    public function update(Request $request, $id)
    {
        $language = Language::findOrFail($id);
        $language->update($request->only(['name', 'is_default']));

        return response()->json(['success' => true, 'data' => $language]);
    }

    /**
     * Delete Language
     */
    public function destroy($id)
    {
        Language::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Language deleted']);
    }

    /**
     * Get Translations
     */
    public function getTranslations($code)
    {
        $path = resource_path("lang/{$code}.json");
        
        if (!File::exists($path)) {
            return response()->json(['success' => false, 'message' => 'Translations not found'], 404);
        }

        $translations = json_decode(File::get($path), true);
        return response()->json(['success' => true, 'data' => $translations]);
    }

    /**
     * Update Translation
     */
    public function updateTranslation(Request $request, $code)
    {
        $path = resource_path("lang/{$code}.json");
        
        $translations = File::exists($path) ? json_decode(File::get($path), true) : [];
        $translations = array_merge($translations, $request->all());
        
        File::put($path, json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return response()->json(['success' => true, 'message' => 'Translation updated']);
    }
}
