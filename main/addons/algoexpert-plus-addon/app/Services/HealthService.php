<?php

namespace Addons\AlgoExpertPlus\App\Services;

class HealthService
{
    /**
     * Check if health service is available
     */
    public function isAvailable(): bool
    {
        return class_exists(\Spatie\Health\HealthServiceProvider::class);
    }

    /**
     * Get health status
     */
    public function getStatus(): array
    {
        if (!$this->isAvailable()) {
            return [
                'available' => false,
                'message' => 'Package not installed. Run: composer install',
            ];
        }

        return [
            'available' => true,
            'message' => 'Health service ready',
        ];
    }
}
