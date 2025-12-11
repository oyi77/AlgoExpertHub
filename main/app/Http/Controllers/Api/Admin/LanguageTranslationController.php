<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Backend\LanguageController as WebLanguageController;

class LanguageTranslationController extends Controller
{
    protected $webController;

    public function __construct()
    {
        $this->webController = new WebLanguageController();
    }

    /**
     * Get translations for a language
     */
    public function getTranslations($lang): JsonResponse
    {
        try {
            $translations = \DB::table('language_translations')
                ->where('language_code', $lang)
                ->get()
                ->pluck('value', 'key')
                ->toArray();

            return response()->json([
                'success' => true,
                'data' => [
                    'language' => $lang,
                    'translations' => $translations
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch translations: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update translation
     */
    public function updateTranslation(Request $request, $lang): JsonResponse
    {
        $validated = $request->validate([
            'key' => 'required|string',
            'value' => 'required|string'
        ]);

        try {
            \DB::table('language_translations')
                ->where('language_code', $lang)
                ->where('key', $validated['key'])
                ->update([
                    'value' => $validated['value'],
                    'updated_at' => now()
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Translation updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update translation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk update translations
     */
    public function bulkUpdateTranslations(Request $request, $lang): JsonResponse
    {
        $validated = $request->validate([
            'translations' => 'required|array',
            'translations.*.key' => 'required|string',
            'translations.*.value' => 'required|string'
        ]);

        try {
            foreach ($validated['translations'] as $translation) {
                \DB::table('language_translations')
                    ->where('language_code', $lang)
                    ->where('key', $translation['key'])
                    ->update([
                        'value' => $translation['value'],
                        'updated_at' => now()
                    ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Translations updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update translations: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete translation key
     */
    public function deleteKey(Request $request, $lang): JsonResponse
    {
        $validated = $request->validate([
            'key' => 'required|string'
        ]);

        try {
            \DB::table('language_translations')
                ->where('language_code', $lang)
                ->where('key', $validated['key'])
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Translation key deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete translation key: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Auto translate using AI
     */
    public function autoTranslate(Request $request, $lang): JsonResponse
    {
        $validated = $request->validate([
            'target_language' => 'required|string',
            'keys' => 'nullable|array'
        ]);

        try {
            // Get translation settings
            $settings = \DB::table('translation_settings')->first();
            
            if (!$settings || empty($settings->api_key)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Translation API is not configured'
                ], 400);
            }

            // Get keys to translate
            $query = \DB::table('language_translations')
                ->where('language_code', $validated['target_language']);

            if (!empty($validated['keys'])) {
                $query->whereIn('key', $validated['keys']);
            }

            $translations = $query->get();

            $translated = 0;
            foreach ($translations as $translation) {
                // Call AI translation service
                // This is a placeholder - implement actual translation API call
                $translatedValue = $this->translateText($translation->value, $validated['target_language'], $settings);
                
                \DB::table('language_translations')
                    ->where('language_code', $lang)
                    ->where('key', $translation->key)
                    ->update([
                        'value' => $translatedValue,
                        'updated_at' => now()
                    ]);
                
                $translated++;
            }

            return response()->json([
                'success' => true,
                'message' => "Translated {$translated} keys successfully",
                'data' => ['translated' => $translated]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to auto translate: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get translation settings
     */
    public function getSettings(): JsonResponse
    {
        try {
            $settings = \DB::table('translation_settings')->first();

            return response()->json([
                'success' => true,
                'data' => $settings ?? (object)[]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update translation settings
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'provider' => 'sometimes|string',
            'api_key' => 'sometimes|string',
            'api_url' => 'sometimes|url',
            'enabled' => 'sometimes|boolean'
        ]);

        try {
            $existing = \DB::table('translation_settings')->first();
            
            if ($existing) {
                \DB::table('translation_settings')
                    ->where('id', $existing->id)
                    ->update(array_merge($validated, ['updated_at' => now()]));
            } else {
                \DB::table('translation_settings')->insert(
                    array_merge($validated, ['created_at' => now(), 'updated_at' => now()])
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Settings updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test translation API
     */
    public function testApi(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'text' => 'required|string',
            'target_language' => 'required|string'
        ]);

        try {
            $settings = \DB::table('translation_settings')->first();
            
            if (!$settings || empty($settings->api_key)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Translation API is not configured'
                ], 400);
            }

            $translated = $this->translateText($validated['text'], $validated['target_language'], $settings);

            return response()->json([
                'success' => true,
                'data' => [
                    'original' => $validated['text'],
                    'translated' => $translated,
                    'target_language' => $validated['target_language']
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Translation test failed: ' . $e->getMessage()
            ], 500);
        }
    }

    protected function translateText($text, $targetLanguage, $settings)
    {
        // Placeholder for actual translation API implementation
        // This should call OpenAI, Google Translate, or other translation service
        // Based on $settings->provider
        
        return $text; // Placeholder
    }
}

