<?php

namespace Addons\MultiChannelSignalAddon\App\Parsers;

use Addons\MultiChannelSignalAddon\App\Contracts\MessageParserInterface;
use Addons\MultiChannelSignalAddon\App\DTOs\ParsedSignalData;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OcrMessageParser implements MessageParserInterface
{
    protected int $priority = 40;
    protected $ocrService;

    public function __construct($ocrService = null)
    {
        $this->ocrService = $ocrService ?? app(\Addons\MultiChannelSignalAddon\App\Services\OcrService::class);
    }

    public function canParse(string $message): bool
    {
        // Check if message contains image URLs or base64 image data
        $hasImageUrl = preg_match('/https?:\/\/[^\s]+\.(jpg|jpeg|png|gif|webp)/i', $message);
        $hasBase64Image = preg_match('/data:image\/(jpeg|jpg|png|gif|webp);base64,/i', $message);
        
        return $hasImageUrl || $hasBase64Image;
    }

    public function parse(string $message): ?ParsedSignalData
    {
        if (!$this->canParse($message)) {
            return null;
        }

        try {
            // Extract image URLs or base64 data
            $imageData = $this->extractImageData($message);
            if (!$imageData) {
                return null;
            }

            // Perform OCR on image
            $extractedText = $this->ocrService->extractText($imageData);
            if (!$extractedText || strlen(trim($extractedText)) < 10) {
                Log::warning("OCR extracted insufficient text", [
                    'text_length' => strlen($extractedText ?? ''),
                ]);
                return null;
            }

            // Parse extracted text using regex parser
            $regexParser = new RegexMessageParser();
            $parsedData = $regexParser->parse($extractedText);

            if ($parsedData) {
                // Lower confidence since OCR may have errors
                $parsedData->confidence = min($parsedData->confidence * 0.8, 85);
                $parsedData->pattern_used = 'OCR Parser';
                return $parsedData;
            }

            return null;

        } catch (\Exception $e) {
            Log::error("OCR parser error: " . $e->getMessage(), [
                'exception' => $e,
                'message_preview' => substr($message, 0, 100),
            ]);
            return null;
        }
    }

    protected function extractImageData(string $message): ?array
    {
        // Try to extract base64 image
        if (preg_match('/data:image\/(\w+);base64,([A-Za-z0-9+\/=]+)/i', $message, $matches)) {
            return [
                'type' => 'base64',
                'format' => $matches[1],
                'data' => $matches[2],
            ];
        }

        // Try to extract image URL
        if (preg_match('/(https?:\/\/[^\s]+\.(jpg|jpeg|png|gif|webp))/i', $message, $matches)) {
            return [
                'type' => 'url',
                'url' => $matches[1],
            ];
        }

        return null;
    }

    public function getName(): string
    {
        return 'OcrMessageParser';
    }

    public function getPriority(): int
    {
        return $this->priority;
    }
}
