<?php

namespace Addons\AlgoExpertPlus\App\Services;

class I18nService
{
    /**
     * Get current locale
     */
    public function getCurrentLocale(): string
    {
        return app()->getLocale();
    }

    /**
     * Get available locales
     */
    public function getAvailableLocales(): array
    {
        return config('app.available_locales', ['en']);
    }

    /**
     * Set locale
     */
    public function setLocale(string $locale): void
    {
        if (in_array($locale, $this->getAvailableLocales())) {
            app()->setLocale($locale);
        }
    }

    /**
     * Get i18n status
     */
    public function getStatus(): array
    {
        return [
            'available' => true,
            'current_locale' => $this->getCurrentLocale(),
            'available_locales' => $this->getAvailableLocales(),
        ];
    }
}
