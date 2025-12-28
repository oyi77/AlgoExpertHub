<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\LanguageTranslationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LanguageTranslationController extends Controller
{
    protected LanguageTranslationService $service;

    public function __construct(LanguageTranslationService $service)
    {
        $this->service = $service;
    }

    /**
     * Get translations for a language
     */
    public function getTranslations($lang): JsonResponse
    {
        try {
            $translations = $this->service->getTranslations($lang);

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
            $this->service->updateTranslation($lang, $validated['key'], $validated['value']);

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
            $updated = $this->service->bulkUpdateTranslations($lang, $validated['translations']);

            return response()->json([
                'success' => true,
                'message' => "Updated {$updated} translations successfully"
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
            $this->service->deleteKey($lang, $validated['key']);

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
            $translated = $this->service->autoTranslate($validated['target_language'], $validated['keys'] ?? null);

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
            $settings = $this->service->getSettings();

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
            $this->service->updateSettings($validated);

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
            $translated = $this->service->testApi($validated['text'], $validated['target_language']);

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
}

