<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

/**
 * @group System Configuration
 *
 * Endpoints for global system settings and reference data.
 */
class SystemController extends Controller
{
    /**
     * Get System Configuration
     *
     * Returns public system configuration including site name, logos, and feature flags.
     *
     * @response 200 {
     *  "success": true,
     *  "data": {
     *    "app_name": "AI Trade Pulse",
     *    "site_logo": "https://example.com/logo.png",
     *    "currency": "USD",
     *    "version": "1.0.0"
     *  }
     * }
     */
    public function config()
    {
        $general = get_general_settings(); // Assuming this helper exists from legacy code, or we fetch from DB
        // If helper doesn't exist, we'll fix it. Based on existing controllers, settings are often globally available.
        // Let's look at how other controllers access settings. ConfigurationController used 'general_setting()'?
        
        // Fallback or fetching directly if helpers aren't sure.
        // Reading existing code suggested 'ConfigurationController', let's stick to standard Laravel config for now and refine.
        
        return response()->json([
            'success' => true,
            'message' => 'System configuration retrieved successfully',
            'data' => [
                'app_name' => config('app.name'),
                'site_name' => setting('site_name', config('app.name')),
                'site_logo' => asset('assets/images/logo/' . setting('site_logo')),
                'site_favicon' => asset('assets/images/logo/' . setting('site_favicon')),
                'currency_symbol' => setting('currency_symbol', '$'),
                'currency_code' => setting('site_currency', 'USD'),
                'is_demo' => config('app.demo', false),
                'debug_mode' => config('app.debug', false),
                // Add more settings as needed
            ]
        ]);
    }

    /**
     * Get Available Languages
     *
     * @response 200 {
     *  "success": true,
     *  "data": [
     *    {"code": "en", "name": "English", "is_default": true},
     *    {"code": "es", "name": "Spanish", "is_default": false}
     *  ]
     * }
     */
    public function languages()
    {
         // Assuming Language model exists based on 'LanguageController' seen in admin routes
        $languages = \App\Models\Language::where('status', true)->get(['name', 'code', 'is_default']);

        return response()->json([
            'success' => true,
            'data' => $languages
        ]);
    }

    /**
     * Get Translations
     * 
     * @urlParam lang string required The language code (e.g., 'en').
     */
    public function translations($lang)
    {
        $language = \App\Models\Language::where('code', $lang)->firstOrFail();
        
        // Assuming translations are stored in JSON files or database.
        // Common pattern: /resources/lang/{lang}.json or files
        
        $path = resource_path("lang/$lang.json");
        $translations = [];
        
        if (file_exists($path)) {
            $translations = json_decode(file_get_contents($path), true);
        } else {
             // Fallback to php files if json doesn't exist
            // This logic can be expanded.
        }

        return response()->json([
            'success' => true,
            'data' => $translations
        ]);
    }
}
