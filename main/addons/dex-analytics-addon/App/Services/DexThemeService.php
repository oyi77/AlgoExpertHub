<?php

declare(strict_types=1);

namespace Addons\DexAnalyticsAddon\App\Services;

use App\Models\Configuration;

class DexThemeService
{
    private const SUPPORTED_BACKEND_THEMES = ['trading-v1', 'beta-ui'];

    public function backendTheme(): string
    {
        try {
            $config = Configuration::first();
            $backendTheme = $config->backend_theme ?? 'default';

            return $backendTheme === 'default' ? 'default' : $backendTheme;
        } catch (\Throwable $e) {
            return 'default';
        }
    }

    public function resolveView(string $viewName): string
    {
        $theme = $this->backendTheme();

        // Default theme - use standard backend view path
        if ($theme === 'default') {
            return 'backend.' . $viewName;
        }

        // Check if theme is supported for this addon
        if (!in_array($theme, self::SUPPORTED_BACKEND_THEMES, true)) {
            return 'backend.' . $viewName;
        }

        // Check if theme-specific view exists
        $themeView = 'backend.' . $theme . '.' . $viewName;

        if (view()->exists($themeView)) {
            return $themeView;
        }

        // Fallback to default backend view
        return 'backend.' . $viewName;
    }

    public function getThemeLayout(): string
    {
        $theme = $this->backendTheme();

        if ($theme === 'default') {
            return 'backend.layout.master';
        }

        // Check if theme-specific layout exists
        $themeLayout = 'backend.' . $theme . '.layout.master';

        if (view()->exists($themeLayout)) {
            return $themeLayout;
        }

        // Fallback to default layout
        return 'backend.layout.master';
    }

    public function getActiveTheme(): string
    {
        return $this->backendTheme();
    }

    public function supportsTheme(string $theme): bool
    {
        return in_array($theme, self::SUPPORTED_BACKEND_THEMES, true);
    }
}
