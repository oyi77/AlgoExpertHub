<?php

namespace Addons\PageBuilderAddon\App\Services;

use App\Http\Controllers\Backend\ConfigurationController;
use App\Services\ThemeManager;
use Illuminate\Support\Facades\Log;

class ThemeIntegrationService
{
    protected $themeManager;

    public function __construct(ThemeManager $themeManager)
    {
        $this->themeManager = $themeManager;
    }

    /**
     * Get available themes
     */
    public function listThemes(): array
    {
        try {
            $themes = $this->themeManager->list();
            $backendThemes = $this->themeManager->listBackend();

            return [
                'type' => 'success',
                'data' => [
                    'frontend' => $themes,
                    'backend' => $backendThemes
                ]
            ];
        } catch (\Exception $e) {
            Log::error('ThemeIntegrationService::listThemes failed', ['error' => $e->getMessage()]);
            
            return [
                'type' => 'error',
                'message' => 'Failed to list themes: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Activate theme
     */
    public function activateTheme(string $themeName): array
    {
        try {
            $configController = app(ConfigurationController::class);
            $request = new \Illuminate\Http\Request(['name' => $themeName]);
            
            // Use existing theme update logic
            $configController->themeUpdate($request, $themeName);

            return [
                'type' => 'success',
                'message' => 'Theme activated successfully'
            ];
        } catch (\Exception $e) {
            Log::error('ThemeIntegrationService::activateTheme failed', ['error' => $e->getMessage()]);
            
            return [
                'type' => 'error',
                'message' => 'Failed to activate theme: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Upload theme
     */
    public function uploadTheme($file): array
    {
        try {
            $configController = app(ConfigurationController::class);
            $request = new \Illuminate\Http\Request();
            $request->files->set('theme_package', $file);
            
            // Use existing theme upload logic
            $result = $configController->themeUpload($request);

            return [
                'type' => 'success',
                'message' => 'Theme uploaded successfully'
            ];
        } catch (\Exception $e) {
            Log::error('ThemeIntegrationService::uploadTheme failed', ['error' => $e->getMessage()]);
            
            return [
                'type' => 'error',
                'message' => 'Failed to upload theme: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get theme configuration
     */
    public function getThemeConfig(string $themeName): array
    {
        try {
            $themePath = $this->themeManager->getThemePath($themeName);
            
            return [
                'type' => 'success',
                'data' => [
                    'name' => $themeName,
                    'path' => $themePath
                ]
            ];
        } catch (\Exception $e) {
            Log::error('ThemeIntegrationService::getThemeConfig failed', ['error' => $e->getMessage()]);
            
            return [
                'type' => 'error',
                'message' => 'Failed to get theme config: ' . $e->getMessage()
            ];
        }
    }
}
