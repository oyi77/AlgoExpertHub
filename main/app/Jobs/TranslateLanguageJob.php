<?php

namespace App\Jobs;

use App\Models\Language;
use App\Services\TranslationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TranslateLanguageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 600; // 10 minutes timeout

    protected $languageId;
    protected $translationType; // 'content' or 'section'

    /**
     * Create a new job instance.
     */
    public function __construct(int $languageId, string $translationType = 'content')
    {
        $this->languageId = $languageId;
        $this->translationType = $translationType;
    }

    /**
     * Execute the job.
     */
    public function handle(TranslationService $translationService)
    {
        try {
            $language = Language::find($this->languageId);
            
            if (!$language) {
                Log::error('Language not found for translation job', ['language_id' => $this->languageId]);
                return;
            }

            $sourcePath = resource_path('lang/en.json');
            $targetPath = resource_path("lang/{$language->code}.json");
            
            if ($this->translationType === 'section') {
                $sourcePath = resource_path('lang/sections/en.json');
                $targetPath = resource_path("lang/sections/{$language->code}.json");
            }

            Log::info('Starting auto-translation job', [
                'language_id' => $this->languageId,
                'language_code' => $language->code,
                'language_name' => $language->name,
                'type' => $this->translationType,
            ]);

            $result = $translationService->translateFile(
                $sourcePath,
                $targetPath,
                $language->name
            );

            if ($result['type'] === 'success') {
                Log::info('Auto-translation completed', [
                    'language_id' => $this->languageId,
                    'translated_keys' => $result['translated'] ?? 0,
                ]);
            } else {
                Log::error('Auto-translation failed', [
                    'language_id' => $this->languageId,
                    'error' => $result['message'] ?? 'Unknown error',
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Translation job exception', [
                'language_id' => $this->languageId,
                'type' => $this->translationType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception)
    {
        Log::error('Translation job failed permanently', [
            'language_id' => $this->languageId,
            'type' => $this->translationType,
            'error' => $exception->getMessage(),
        ]);
    }
}

