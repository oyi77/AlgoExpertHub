<?php

namespace Addons\OpenRouterIntegration\App\Http\Controllers\Backend;

use Addons\OpenRouterIntegration\App\Models\OpenRouterModel;
use Addons\OpenRouterIntegration\App\Services\OpenRouterService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OpenRouterModelController extends Controller
{
    protected OpenRouterService $service;

    public function __construct(OpenRouterService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of models.
     */
    public function index(Request $request)
    {
        $query = OpenRouterModel::query();

        // Filter by provider
        if ($request->filled('provider')) {
            $query->where('provider', $request->provider);
        }

        // Filter by availability
        if ($request->filled('available')) {
            $query->where('is_available', $request->available);
        }

        // Search by name or model_id
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('model_id', 'like', "%{$search}%");
            });
        }

        try {
            $models = $query->select('id', 'model_id', 'name', 'provider', 'context_length', 'pricing', 'is_available', 'last_synced_at')
                ->orderBy('provider')
                ->orderBy('name')
                ->paginate(50);

            $providers = OpenRouterModel::distinct()->pluck('provider')->sort();
        } catch (\Exception $e) {
            \Log::error('Error loading OpenRouter models', ['error' => $e->getMessage()]);
            $models = collect([])->paginate(50);
            $providers = collect([]);
        }

        $title = 'OpenRouter Models';

        return view('openrouter::backend.models.index', compact('models', 'providers', 'title'));
    }

    /**
     * Sync models from OpenRouter API.
     */
    public function sync()
    {
        try {
            $models = $this->service->fetchAvailableModels();

            if ($models->isEmpty()) {
                return redirect()->back()
                    ->with('warning', 'No models were synced. Please check your API configuration.');
            }

            $this->service->clearModelsCache();

            return redirect()->back()
                ->with('success', "Successfully synced {$models->count()} models from OpenRouter");

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to sync models: ' . $e->getMessage());
        }
    }

    /**
     * Get model details (AJAX).
     */
    public function show($id)
    {
        try {
            $model = OpenRouterModel::findOrFail($id);

            return response()->json([
                'type' => 'success',
                'data' => [
                    'id' => $model->id,
                    'model_id' => $model->model_id,
                    'name' => $model->name,
                    'provider' => $model->provider,
                    'context_length' => $model->context_length,
                    'pricing' => $model->pricing,
                    'pricing_string' => $model->pricing_string,
                    'modalities' => $model->modalities,
                    'is_available' => $model->is_available,
                    'last_synced_at' => $model->last_synced_at?->diffForHumans(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'type' => 'error',
                'message' => 'Model not found',
            ], 404);
        }
    }
}

