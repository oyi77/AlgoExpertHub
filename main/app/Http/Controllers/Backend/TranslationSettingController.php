<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\TranslationSetting;
use Addons\AiConnectionAddon\App\Models\AiConnection;
use Illuminate\Http\Request;

class TranslationSettingController extends Controller
{
    /**
     * Show translation settings form
     */
    public function index()
    {
        $settings = TranslationSetting::with(['aiConnection.provider', 'fallbackConnection.provider'])->first();
        $connections = AiConnection::with('provider')->active()->get();

        return view('backend.translation-settings.index', compact('settings', 'connections'));
    }

    /**
     * Update translation settings
     */
    public function update(Request $request)
    {
        $request->validate([
            'ai_connection_id' => 'required|exists:ai_connections,id',
            'fallback_connection_id' => 'nullable|exists:ai_connections,id',
            'batch_size' => 'required|integer|min:1|max:50',
            'delay_between_requests_ms' => 'required|integer|min:50|max:5000',
            'settings' => 'nullable|array',
        ]);

        $settings = TranslationSetting::first();

        if ($settings) {
            $settings->update($request->only([
                'ai_connection_id',
                'fallback_connection_id',
                'batch_size',
                'delay_between_requests_ms',
                'settings',
            ]));
        } else {
            TranslationSetting::create($request->only([
                'ai_connection_id',
                'fallback_connection_id',
                'batch_size',
                'delay_between_requests_ms',
                'settings',
            ]));
        }

        return redirect()->back()->with('success', 'Translation settings updated successfully');
    }

    /**
     * Test translation with current settings
     */
    public function test(Request $request)
    {
        $request->validate([
            'test_text' => 'required|string',
            'target_language' => 'required|string',
        ]);

        $translationService = app(\App\Services\TranslationService::class);
        
        try {
            $result = $translationService->translateWithAi(
                $request->test_text,
                $request->target_language
            );

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Translation test successful',
                    'original' => $request->test_text,
                    'translated' => $result,
                    'target_language' => $request->target_language,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Translation failed - please check your AI connection configuration',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Translation test failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}

