<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Backend\ConfigurationController as WebConfigController;

class ThemeManagementController extends Controller
{
    protected $webController;

    public function __construct()
    {
        $this->webController = new WebConfigController();
    }

    /**
     * List all themes
     */
    public function index(): JsonResponse
    {
        try {
            $themes = $this->getAvailableThemes();

            return response()->json([
                'success' => true,
                'data' => $themes
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch themes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update theme
     */
    public function update(Request $request, $name): JsonResponse
    {
        try {
            $request->merge(['name' => $name]);
            $this->webController->themeUpdate($request);

            return response()->json([
                'success' => true,
                'message' => 'Theme updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update theme: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update backend theme
     */
    public function updateBackend(Request $request, $name): JsonResponse
    {
        try {
            $request->merge(['name' => $name]);
            $this->webController->backendThemeUpdate($request);

            return response()->json([
                'success' => true,
                'message' => 'Backend theme updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update backend theme: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Change theme color
     */
    public function changeColor(Request $request, $theme): JsonResponse
    {
        try {
            $request->merge(['theme' => $theme]);
            $this->webController->themeColor($request);

            return response()->json([
                'success' => true,
                'message' => 'Theme color updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update theme color: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload theme
     */
    public function upload(Request $request): JsonResponse
    {
        try {
            $this->webController->themeUpload($request);

            return response()->json([
                'success' => true,
                'message' => 'Theme uploaded successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload theme: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download theme template
     */
    public function downloadTemplate(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        try {
            return $this->webController->themeDownloadTemplate();
        } catch (\Exception $e) {
            abort(500, 'Failed to download template: ' . $e->getMessage());
        }
    }

    /**
     * Delete theme
     */
    public function destroy($theme): JsonResponse
    {
        try {
            $request = Request::create("/admin/theme/delete/{$theme}", 'DELETE');
            $this->webController->themeDelete($request, $theme);

            return response()->json([
                'success' => true,
                'message' => 'Theme deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete theme: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Deactivate all themes
     */
    public function deactivateAll(): JsonResponse
    {
        try {
            $request = new Request();
            $this->webController->themeDeactivate($request);

            return response()->json([
                'success' => true,
                'message' => 'All themes deactivated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to deactivate themes: ' . $e->getMessage()
            ], 500);
        }
    }

    protected function getAvailableThemes(): array
    {
        $themesDir = resource_path('views/frontend');
        $themes = [];

        if (is_dir($themesDir)) {
            $directories = array_filter(glob($themesDir . '/*'), 'is_dir');
            
            foreach ($directories as $dir) {
                $themeName = basename($dir);
                $themes[] = [
                    'name' => $themeName,
                    'path' => $dir,
                    'active' => str_replace(['frontend.', '.'], '', \App\Helpers\Helper\Helper::theme()) === $themeName
                ];
            }
        }

        return $themes;
    }
}

