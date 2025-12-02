<?php

namespace Addons\MultiChannelSignalAddon\App\Http\Controllers\Backend;

use Addons\MultiChannelSignalAddon\App\Models\AiConfiguration;
use Addons\MultiChannelSignalAddon\App\Services\AiProviderFactory;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

class AiConfigurationController
{
    /**
     * Display list of AI configurations.
     */
    public function index(): View
    {
        $title = 'AI Configuration';
        $configurations = AiConfiguration::orderBy('priority', 'desc')->get();
        $availableProviders = AiProviderFactory::getAvailableProviders();

        return view('multi-channel-signal-addon::backend.ai-configuration.index', compact(
            'title',
            'configurations',
            'availableProviders'
        ));
    }

    /**
     * Show form to create new AI configuration.
     */
    public function create(): View
    {
        $title = 'Create AI Configuration';
        $availableProviders = AiProviderFactory::getAvailableProviders();
        $defaultConfigs = $this->getDefaultConfigs();

        return view('multi-channel-signal-addon::backend.ai-configuration.create', compact(
            'title',
            'availableProviders',
            'defaultConfigs'
        ));
    }

    /**
     * Store new AI configuration.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'provider' => 'required|string|in:' . implode(',', AiProviderFactory::getAvailableProviders()),
            'name' => 'required|string|max:255',
            'api_key' => 'required|string',
            'api_url' => 'nullable|string|url',
            'model' => 'nullable|string|max:255',
            'enabled' => 'boolean',
            'priority' => 'integer|min:0|max:100',
            'timeout' => 'integer|min:5|max:300',
            'temperature' => 'numeric|min:0|max:2',
            'max_tokens' => 'integer|min:50|max:4000',
        ]);

        try {
            $config = AiConfiguration::create([
                'provider' => $request->provider,
                'name' => $request->name,
                'api_key' => $request->api_key,
                'api_url' => $request->api_url,
                'model' => $request->model,
                'enabled' => $request->has('enabled'),
                'priority' => $request->priority ?? 50,
                'timeout' => $request->timeout ?? 30,
                'temperature' => $request->temperature ?? 0.3,
                'max_tokens' => $request->max_tokens ?? 500,
            ]);

            return redirect()->route('admin.ai-configuration.index')
                ->with('success', 'AI configuration created successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to create AI configuration', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create AI configuration: ' . $e->getMessage());
        }
    }

    /**
     * Show form to edit AI configuration.
     */
    public function edit(int $id): View
    {
        $title = 'Edit AI Configuration';
        $config = AiConfiguration::findOrFail($id);
        $availableProviders = AiProviderFactory::getAvailableProviders();

        return view('multi-channel-signal-addon::backend.ai-configuration.edit', compact(
            'title',
            'config',
            'availableProviders'
        ));
    }

    /**
     * Update AI configuration.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $config = AiConfiguration::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'api_key' => 'nullable|string', // Allow empty to keep existing
            'api_url' => 'nullable|string|url',
            'model' => 'nullable|string|max:255',
            'enabled' => 'boolean',
            'priority' => 'integer|min:0|max:100',
            'timeout' => 'integer|min:5|max:300',
            'temperature' => 'numeric|min:0|max:2',
            'max_tokens' => 'integer|min:50|max:4000',
        ]);

        try {
            $updateData = [
                'name' => $request->name,
                'api_url' => $request->api_url,
                'model' => $request->model,
                'enabled' => $request->has('enabled'),
                'priority' => $request->priority ?? 50,
                'timeout' => $request->timeout ?? 30,
                'temperature' => $request->temperature ?? 0.3,
                'max_tokens' => $request->max_tokens ?? 500,
            ];

            // Only update API key if provided
            if ($request->filled('api_key')) {
                $updateData['api_key'] = $request->api_key;
            }

            $config->update($updateData);

            return redirect()->route('admin.ai-configuration.index')
                ->with('success', 'AI configuration updated successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to update AI configuration', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update AI configuration: ' . $e->getMessage());
        }
    }

    /**
     * Delete AI configuration.
     */
    public function destroy(int $id): RedirectResponse
    {
        try {
            $config = AiConfiguration::findOrFail($id);
            $config->delete();

            return redirect()->route('admin.ai-configuration.index')
                ->with('success', 'AI configuration deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to delete AI configuration', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->with('error', 'Failed to delete AI configuration: ' . $e->getMessage());
        }
    }

    /**
     * Test AI configuration connection.
     */
    public function testConnection(int $id): \Illuminate\Http\JsonResponse
    {
        try {
            $config = AiConfiguration::findOrFail($id);
            $provider = AiProviderFactory::createFromConfig($config);

            if (!$provider) {
                return response()->json([
                    'success' => false,
                    'message' => 'Provider not found or not supported.',
                ], 400);
            }

            $isConnected = $provider->testConnection($config);

            return response()->json([
                'success' => $isConnected,
                'message' => $isConnected 
                    ? 'Connection successful!' 
                    : 'Connection failed. Please check your API key and settings.',
            ]);
        } catch (\Exception $e) {
            Log::error('AI connection test failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Test failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Fetch available models for a provider.
     */
    public function fetchModels(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $request->validate([
                'provider' => 'required|string',
                'api_key' => 'required|string',
            ]);

            $provider = AiProviderFactory::create($request->provider);

            if (!$provider) {
                return response()->json([
                    'success' => false,
                    'message' => 'Provider not found or not supported.',
                    'models' => [],
                ], 400);
            }

            // Only Gemini provider supports fetching models
            if ($request->provider === 'gemini' && method_exists($provider, 'fetchAvailableModels')) {
                $models = $provider->fetchAvailableModels($request->api_key);
                
                return response()->json([
                    'success' => true,
                    'models' => $models,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Model fetching not supported for this provider.',
                'models' => [],
            ], 400);
        } catch (\Exception $e) {
            Log::error('Fetch models failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch models: ' . $e->getMessage(),
                'models' => [],
            ], 500);
        }
    }

    /**
     * Fetch available models using stored API key from configuration.
     */
    public function fetchModelsFromConfig(int $id): \Illuminate\Http\JsonResponse
    {
        try {
            $config = AiConfiguration::findOrFail($id);
            
            if (!$config->enabled) {
                return response()->json([
                    'success' => false,
                    'message' => 'AI configuration is not enabled.',
                    'models' => [],
                ], 400);
            }

            $apiKey = $config->getDecryptedApiKey();
            
            if (empty($apiKey)) {
                return response()->json([
                    'success' => false,
                    'message' => 'API key not found in configuration. Please enter API key to fetch models.',
                    'models' => [],
                ], 400);
            }

            $provider = AiProviderFactory::createFromConfig($config);

            if (!$provider) {
                return response()->json([
                    'success' => false,
                    'message' => 'Provider not found or not supported.',
                    'models' => [],
                ], 400);
            }

            // Only Gemini provider supports fetching models
            if ($config->provider === 'gemini' && method_exists($provider, 'fetchAvailableModels')) {
                $models = $provider->fetchAvailableModels($apiKey);
                
                return response()->json([
                    'success' => true,
                    'models' => $models,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Model fetching not supported for this provider.',
                'models' => [],
            ], 400);
        } catch (\Exception $e) {
            Log::error('Fetch models from config failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch models: ' . $e->getMessage(),
                'models' => [],
            ], 500);
        }
    }

    /**
     * Test AI parsing with a sample message.
     */
    public function testParse(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        try {
            $request->validate([
                'message' => 'required|string|max:5000',
            ]);

            $config = AiConfiguration::findOrFail($id);
            
            if (!$config->enabled) {
                return response()->json([
                    'success' => false,
                    'message' => 'AI configuration is not enabled.',
                    'parsed' => null,
                ], 400);
            }

            $provider = AiProviderFactory::createFromConfig($config);

            if (!$provider) {
                return response()->json([
                    'success' => false,
                    'message' => 'Provider not found or not supported.',
                    'parsed' => null,
                ], 400);
            }

            $parsedData = $provider->parse($request->message, $config);

            return response()->json([
                'success' => true,
                'parsed' => $parsedData,
                'message' => $parsedData ? 'Message parsed successfully!' : 'Failed to parse message. Check if message contains valid trading signal data.',
            ]);
        } catch (\Exception $e) {
            Log::error('AI parse test failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Parse test failed: ' . $e->getMessage(),
                'parsed' => null,
            ], 500);
        }
    }

    /**
     * Get default configurations for providers.
     */
    protected function getDefaultConfigs(): array
    {
        return [
            'openai' => [
                'api_url' => 'https://api.openai.com/v1/chat/completions',
                'model' => 'gpt-3.5-turbo',
                'timeout' => 30,
                'temperature' => 0.3,
                'max_tokens' => 500,
            ],
            'gemini' => [
                'api_url' => 'https://generativelanguage.googleapis.com/v1/models/gemini-1.5-pro:generateContent',
                'model' => 'gemini-1.5-pro',
                'timeout' => 30,
                'temperature' => 0.3,
                'max_tokens' => 500,
            ],
        ];
    }
}

