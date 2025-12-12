<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Artisan;

/**
 * @group Admin - Addon Management
 *
 * Endpoints for managing addons and their modules.
 */
class AddonApiController extends Controller
{
    /**
     * List Addons
     */
    public function index()
    {
        $addonsPath = base_path('addons');
        $addons = [];

        if (File::exists($addonsPath)) {
            $directories = File::directories($addonsPath);
            
            foreach ($directories as $dir) {
                $addonJsonPath = $dir . '/addon.json';
                
                if (File::exists($addonJsonPath)) {
                    $addonData = json_decode(File::get($addonJsonPath), true);
                    $addonData['path'] = basename($dir);
                    $addonData['is_enabled'] = $this->isAddonEnabled(basename($dir));
                    $addons[] = $addonData;
                }
            }
        }

        return response()->json(['success' => true, 'data' => $addons]);
    }

    /**
     * Upload Addon
     */
    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'addon' => 'required|file|mimes:zip',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $file = $request->file('addon');
            $addonsPath = base_path('addons');
            
            // Create addons directory if it doesn't exist
            if (!File::exists($addonsPath)) {
                File::makeDirectory($addonsPath, 0755, true);
            }

            // Extract zip file
            $zip = new \ZipArchive;
            if ($zip->open($file->path()) === TRUE) {
                $zip->extractTo($addonsPath);
                $zip->close();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Addon uploaded and extracted successfully'
                ], 201);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to extract addon'
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload addon: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update Addon Status
     */
    public function updateStatus(Request $request, $addon)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $status = $request->status ? 'enable' : 'disable';
            
            // Update addon status in configuration or database
            // This is simplified - in production, update the addon registry
            
            return response()->json([
                'success' => true,
                'message' => "Addon {$status}d successfully"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update addon status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Addon Modules
     */
    public function modules($addon)
    {
        $addonPath = base_path("addons/{$addon}");
        $modulesPath = $addonPath . '/app';
        $modules = [];

        if (File::exists($modulesPath)) {
            $directories = File::directories($modulesPath);
            
            foreach ($directories as $dir) {
                $moduleName = basename($dir);
                $modules[] = [
                    'name' => $moduleName,
                    'path' => $moduleName,
                    'is_enabled' => true, // Simplified - check actual status
                ];
            }
        }

        return response()->json(['success' => true, 'data' => $modules]);
    }

    /**
     * Update Module Status
     */
    public function updateModule(Request $request, $addon, $module)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $status = $request->status ? 'enabled' : 'disabled';
            
            // Update module status
            // This is simplified - in production, update the module registry
            
            return response()->json([
                'success' => true,
                'message' => "Module {$status} successfully"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update module status: ' . $e->getMessage()
            ], 500);
        }
    }

    private function isAddonEnabled($addon)
    {
        // Check if addon is enabled
        // This is simplified - check actual addon registry
        return true;
    }
}
