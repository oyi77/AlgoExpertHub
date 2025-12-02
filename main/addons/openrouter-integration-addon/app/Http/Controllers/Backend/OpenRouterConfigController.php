<?php

namespace Addons\OpenRouterIntegration\App\Http\Controllers\Backend;

use Addons\OpenRouterIntegration\App\Models\OpenRouterConfiguration;
use Addons\OpenRouterIntegration\App\Models\OpenRouterModel;
use Addons\OpenRouterIntegration\App\Services\OpenRouterService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OpenRouterConfigController extends Controller
{
    protected OpenRouterService $service;

    public function __construct(OpenRouterService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of configurations.
     */
    public function index()
    {
        try {
            $configurations = OpenRouterConfiguration::with('model:id,model_id,name')
                ->select('id', 'name', 'model_id', 'enabled', 'priority', 'use_for_parsing', 'use_for_analysis', 'created_at')
                ->latest()
                ->paginate(20);
        } catch (\Exception $e) {
            \Log::error('Error loading OpenRouter configurations', ['error' => $e->getMessage()]);
            $configurations = collect([])->paginate(20);
        }

        $title = 'OpenRouter Configurations';

        return view('openrouter::backend.config.index', compact('configurations', 'title'));
    }

    /**
     * Show the form for creating a new configuration.
     */
    public function create()
    {
        $models = OpenRouterModel::getAvailable();
        $title = 'Create OpenRouter Configuration';

        return view('openrouter::backend.config.create', compact('models', 'title'));
    }

    /**
     * Store a newly created configuration.
     */
    public function store(Request $request)
    {
        $validator = $this->validateConfig($request);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $config = OpenRouterConfiguration::create($request->all());

            return redirect()->route('admin.openrouter.configurations.index')
                ->with('success', 'Configuration created successfully');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to create configuration: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show the form for editing the specified configuration.
     */
    public function edit($id)
    {
        $configuration = OpenRouterConfiguration::findOrFail($id);
        $models = OpenRouterModel::getAvailable();
        $title = 'Edit OpenRouter Configuration';

        return view('openrouter::backend.config.edit', compact('configuration', 'models', 'title'));
    }

    /**
     * Update the specified configuration.
     */
    public function update(Request $request, $id)
    {
        $configuration = OpenRouterConfiguration::findOrFail($id);

        $validator = $this->validateConfig($request, $id);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $configuration->update($request->all());

            return redirect()->route('admin.openrouter.configurations.index')
                ->with('success', 'Configuration updated successfully');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update configuration: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified configuration.
     */
    public function destroy($id)
    {
        try {
            $configuration = OpenRouterConfiguration::findOrFail($id);
            $configuration->delete();

            return redirect()->route('admin.openrouter.configurations.index')
                ->with('success', 'Configuration deleted successfully');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete configuration: ' . $e->getMessage());
        }
    }

    /**
     * Test connection for the specified configuration.
     */
    public function testConnection($id)
    {
        try {
            $configuration = OpenRouterConfiguration::findOrFail($id);

            $success = $this->service->testConnection($configuration);

            if ($success) {
                return response()->json([
                    'type' => 'success',
                    'message' => 'Connection test successful!',
                ]);
            }

            return response()->json([
                'type' => 'error',
                'message' => 'Connection test failed. Please check your API key and model selection.',
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'type' => 'error',
                'message' => 'Connection test failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle configuration status.
     */
    public function toggleStatus($id)
    {
        try {
            $configuration = OpenRouterConfiguration::findOrFail($id);
            $configuration->enabled = !$configuration->enabled;
            $configuration->save();

            return redirect()->back()
                ->with('success', 'Configuration status updated successfully');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update status: ' . $e->getMessage());
        }
    }

    /**
     * Validate configuration data.
     */
    protected function validateConfig(Request $request, $id = null)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'api_key' => $id ? 'nullable|string' : 'required|string',
            'model_id' => 'required|string|exists:openrouter_models,model_id',
            'site_url' => 'nullable|url',
            'site_name' => 'nullable|string|max:255',
            'temperature' => 'required|numeric|min:0|max:2',
            'max_tokens' => 'required|integer|min:10|max:4000',
            'timeout' => 'required|integer|min:5|max:120',
            'priority' => 'required|integer|min:0|max:100',
            'enabled' => 'boolean',
            'use_for_parsing' => 'boolean',
            'use_for_analysis' => 'boolean',
        ];

        return Validator::make($request->all(), $rules);
    }
}

