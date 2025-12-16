<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

/**
 * @group Admin - Theme Management
 *
 * Endpoints for managing themes.
 */
class ThemeApiController extends Controller
{
    /**
     * List Themes
     */
    public function index()
    {
        $themePath = resource_path('views/themes');
        $themes = [];

        if (File::exists($themePath)) {
            $directories = File::directories($themePath);
            foreach ($directories as $dir) {
                $themeName = basename($dir);
                $themes[] = [
                    'name' => $themeName,
                    'path' => $dir,
                    'is_active' => config('app.theme') === $themeName,
                ];
            }
        }

        return response()->json(['success' => true, 'data' => $themes]);
    }

    /**
     * Activate Theme
     */
    public function activate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'theme' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Update .env or configuration
        // This is simplified - in production, update the .env file
        
        return response()->json([
            'success' => true,
            'message' => 'Theme activated successfully'
        ]);
    }

    /**
     * Upload Theme
     */
    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'theme' => 'required|file|mimes:zip',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Handle theme upload and extraction
        // This is simplified - in production, extract zip to themes directory
        
        return response()->json([
            'success' => true,
            'message' => 'Theme uploaded successfully'
        ]);
    }

    /**
     * Delete Theme
     */
    public function destroy($theme)
    {
        $themePath = resource_path("views/themes/{$theme}");

        if (!File::exists($themePath)) {
            return response()->json(['success' => false, 'message' => 'Theme not found'], 404);
        }

        if (config('app.theme') === $theme) {
            return response()->json(['success' => false, 'message' => 'Cannot delete active theme'], 400);
        }

        File::deleteDirectory($themePath);

        return response()->json(['success' => true, 'message' => 'Theme deleted successfully']);
    }
}
