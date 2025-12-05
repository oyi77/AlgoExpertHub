<?php

namespace Addons\MultiChannelSignalAddon\App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class OcrService
{
    protected string $provider = 'tesseract'; // tesseract, google_vision, aws_textract

    public function __construct()
    {
        $this->provider = config('multi-channel-signal-addon.ocr_provider', 'tesseract');
    }

    public function extractText(array $imageData): ?string
    {
        try {
            switch ($this->provider) {
                case 'tesseract':
                    return $this->extractWithTesseract($imageData);
                case 'google_vision':
                    return $this->extractWithGoogleVision($imageData);
                case 'aws_textract':
                    return $this->extractWithAwsTextract($imageData);
                default:
                    Log::warning("Unknown OCR provider: {$this->provider}");
                    return null;
            }
        } catch (\Exception $e) {
            Log::error("OCR extraction failed: " . $e->getMessage(), [
                'exception' => $e,
                'provider' => $this->provider,
            ]);
            return null;
        }
    }

    protected function extractWithTesseract(array $imageData): ?string
    {
        // Save image to temp file
        $tempPath = $this->saveImageToTemp($imageData);
        if (!$tempPath) {
            return null;
        }

        try {
            // Execute Tesseract OCR
            $command = sprintf(
                'tesseract %s stdout -l eng 2>/dev/null',
                escapeshellarg($tempPath)
            );
            
            $output = shell_exec($command);
            
            // Clean up temp file
            @unlink($tempPath);
            
            return $output ? trim($output) : null;
        } catch (\Exception $e) {
            @unlink($tempPath);
            Log::error("Tesseract OCR error: " . $e->getMessage());
            return null;
        }
    }

    protected function extractWithGoogleVision(array $imageData): ?string
    {
        $apiKey = config('services.google_vision.api_key');
        if (!$apiKey) {
            Log::warning("Google Vision API key not configured");
            return null;
        }

        // Prepare image for API
        $imageContent = $this->getImageContent($imageData);
        if (!$imageContent) {
            return null;
        }

        try {
            $response = Http::post("https://vision.googleapis.com/v1/images:annotate?key={$apiKey}", [
                'requests' => [
                    [
                        'image' => [
                            'content' => base64_encode($imageContent),
                        ],
                        'features' => [
                            [
                                'type' => 'TEXT_DETECTION',
                                'maxResults' => 1,
                            ],
                        ],
                    ],
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $textAnnotations = $data['responses'][0]['textAnnotations'] ?? [];
                
                if (!empty($textAnnotations)) {
                    // First annotation contains all detected text
                    return $textAnnotations[0]['description'] ?? null;
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error("Google Vision API error: " . $e->getMessage());
            return null;
        }
    }

    protected function extractWithAwsTextract(array $imageData): ?string
    {
        // AWS Textract implementation would go here
        // Requires AWS SDK and credentials
        Log::warning("AWS Textract not yet implemented");
        return null;
    }

    protected function saveImageToTemp(array $imageData): ?string
    {
        try {
            $imageContent = $this->getImageContent($imageData);
            if (!$imageContent) {
                return null;
            }

            $tempPath = sys_get_temp_dir() . '/' . uniqid('ocr_') . '.png';
            file_put_contents($tempPath, $imageContent);
            
            return $tempPath;
        } catch (\Exception $e) {
            Log::error("Failed to save image to temp: " . $e->getMessage());
            return null;
        }
    }

    protected function getImageContent(array $imageData): ?string
    {
        if ($imageData['type'] === 'base64') {
            return base64_decode($imageData['data']);
        }

        if ($imageData['type'] === 'url') {
            try {
                $response = Http::timeout(10)->get($imageData['url']);
                if ($response->successful()) {
                    return $response->body();
                }
            } catch (\Exception $e) {
                Log::error("Failed to fetch image from URL: " . $e->getMessage());
            }
        }

        return null;
    }
}
