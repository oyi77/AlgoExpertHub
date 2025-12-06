<?php

namespace Addons\AlgoExpertPlus\App\Services;

class SeoService
{
    /**
     * Check if SEO service is available
     */
    public function isAvailable(): bool
    {
        return class_exists(\Artesaos\SEOTools\Providers\SEOToolsServiceProvider::class);
    }

    /**
     * Get SEO status
     */
    public function getStatus(): array
    {
        if (!$this->isAvailable()) {
            return [
                'available' => false,
                'message' => 'SEO tools package not installed',
            ];
        }

        return [
            'available' => true,
            'message' => 'SEO tools ready',
        ];
    }
}
