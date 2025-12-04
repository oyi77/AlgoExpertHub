<?php

namespace App\Repositories;

use App\Models\Configuration;

class ConfigurationRepository
{
    public static function get(): ?Configuration
    {
        return cache()->remember('app_configuration', 300, function () {
            return Configuration::first();
        });
    }
}

