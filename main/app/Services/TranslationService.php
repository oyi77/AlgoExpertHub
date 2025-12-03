<?php

namespace App\Services;

use App\Models\TranslationSetting;
use Addons\AiConnectionAddon\App\Services\AiConnectionService;
use Illuminate\Support\Facades\Log;

class TranslationService
{
    protected $aiConnectionService;

    public function __construct(?AiConnectionService $aiConnectionService = null)
    {
        $this->aiConnectionService = $aiConnectionService ?? app(AiConnectionService::class);
    }

    /**
     * Translate text using AI Connection
     *
     * @param string $text The text to translate
     * @param string $targetLanguage Target language name (e.g., 'Spanish', 'French')
     * @return string|null Translated text or null on failure
     */
    public function translateWithAi(string $text, string $targetLanguage): ?string
    {
        try {
            // Get translation settings
            $settings = TranslationSetting::current();
            
            if (!$settings || !$settings->ai_connection_id) {
                Log::error('Translation AI connection not configured');
                return null;
            }

            // Build translation prompt
            $prompt = "Translate the following text to {$targetLanguage}. Return ONLY the translated text, no explanations or additional content:\n\n{$text}";

            // Get effective settings
            $options = $settings->getEffectiveSettings();
            $options['temperature'] = 0.3; // Lower temperature for consistent translations
            $options['max_tokens'] = 1000;

            try {
                // Execute translation using AI Connection Service
                $result = $this->aiConnectionService->execute(
                    connectionId: $settings->ai_connection_id,
                    prompt: $prompt,
                    options: $options,
                    feature: 'translation'
                );

                if ($result['success'] && !empty($result['response'])) {
                    $translatedText = $result['response'];
                    
                    // Remove any surrounding quotes if AI added them
                    $translatedText = trim($translatedText, '"\'');
                    return $translatedText;
                }
            } catch (\Exception $primaryError) {
                Log::warning('Primary translation connection failed, trying fallback', [
                    'error' => $primaryError->getMessage(),
                ]);

                // Try fallback connection if configured
                if ($settings->fallback_connection_id) {
                    try {
                        $result = $this->aiConnectionService->execute(
                            connectionId: $settings->fallback_connection_id,
                            prompt: $prompt,
                            options: $options,
                            feature: 'translation'
                        );

                        if ($result['success'] && !empty($result['response'])) {
                            $translatedText = trim($result['response'], '"\'');
                            return $translatedText;
                        }
                    } catch (\Exception $fallbackError) {
                        Log::error('Fallback translation connection also failed', [
                            'error' => $fallbackError->getMessage(),
                        ]);
                    }
                }

                // Re-throw if no fallback worked
                throw $primaryError;
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Translation failed', [
                'error' => $e->getMessage(),
                'text' => substr($text, 0, 100),
                'target_language' => $targetLanguage,
            ]);
            return null;
        }
    }

    /**
     * Translate all keys in an array
     *
     * @param array $translations Array of key-value pairs to translate
     * @param string $targetLanguage Target language name
     * @return array Array with translated values
     */
    public function translateBatch(array $translations, string $targetLanguage): array
    {
        $settings = TranslationSetting::current();
        $delayMs = $settings->delay_between_requests_ms ?? 100;
        
        $translated = [];
        $total = count($translations);
        $current = 0;

        foreach ($translations as $key => $value) {
            $current++;
            
            // Skip if value is empty or already translated
            if (empty($value)) {
                $translatedValue = $this->translateWithAi($key, $targetLanguage);
                $translated[$key] = $translatedValue ?? $key;
            } else {
                $translatedValue = $this->translateWithAi($value, $targetLanguage);
                $translated[$key] = $translatedValue ?? $value;
            }

            // Add delay to avoid rate limiting (configurable from settings)
            if ($current < $total) {
                usleep($delayMs * 1000); // Convert ms to microseconds
            }
        }

        return $translated;
    }

    /**
     * Translate language file
     *
     * @param string $sourceFile Path to source JSON file (e.g., en.json)
     * @param string $targetFile Path to target JSON file
     * @param string $targetLanguage Target language name
     * @return array ['type' => 'success|error', 'message' => '...', 'translated' => int]
     */
    public function translateFile(string $sourceFile, string $targetFile, string $targetLanguage): array
    {
        try {
            if (!file_exists($sourceFile)) {
                return ['type' => 'error', 'message' => 'Source file not found'];
            }

            // Read source translations
            $sourceTranslations = json_decode(file_get_contents($sourceFile), true);
            if (!$sourceTranslations) {
                return ['type' => 'error', 'message' => 'Invalid source file format'];
            }

            // Read existing target translations (if any)
            $existingTranslations = [];
            if (file_exists($targetFile)) {
                $existingTranslations = json_decode(file_get_contents($targetFile), true) ?? [];
            }

            // Only translate keys that don't exist or are empty
            $keysToTranslate = [];
            foreach ($sourceTranslations as $key => $value) {
                if (!isset($existingTranslations[$key]) || empty($existingTranslations[$key])) {
                    $keysToTranslate[$key] = $value;
                }
            }

            if (empty($keysToTranslate)) {
                return [
                    'type' => 'success',
                    'message' => 'All translations are already complete',
                    'translated' => 0
                ];
            }

            // Translate batch
            $newTranslations = $this->translateBatch($keysToTranslate, $targetLanguage, $apiKey);

            // Merge with existing translations
            $finalTranslations = array_merge($existingTranslations, $newTranslations);

            // Write to target file
            file_put_contents(
                $targetFile,
                json_encode($finalTranslations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );

            return [
                'type' => 'success',
                'message' => 'Translations completed successfully',
                'translated' => count($newTranslations)
            ];
        } catch (\Exception $e) {
            Log::error('Translation file failed', [
                'error' => $e->getMessage(),
                'source' => $sourceFile,
                'target' => $targetFile,
            ]);

            return [
                'type' => 'error',
                'message' => 'Translation failed: ' . $e->getMessage()
            ];
        }
    }
}

