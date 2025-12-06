<?php

namespace Addons\SmartRiskManagement\App\Http\Controllers\Backend;

use Addons\SmartRiskManagement\App\Http\Controllers\Controller;
use Addons\SmartRiskManagement\App\Models\SrmModelVersion;
use Addons\SmartRiskManagement\App\Services\ModelTrainingService;
use App\Helpers\Helper\Helper;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

class ModelController extends Controller
{
    protected ModelTrainingService $trainingService;

    public function __construct(ModelTrainingService $trainingService)
    {
        $this->trainingService = $trainingService;
    }

    /**
     * Display a listing of model versions.
     */
    public function index(): View
    {
        $data['title'] = 'ML Model Management';

        $data['slippage_models'] = SrmModelVersion::where('model_type', 'slippage_prediction')
            ->orderBy('created_at', 'desc')
            ->get();

        $data['performance_models'] = SrmModelVersion::where('model_type', 'performance_score')
            ->orderBy('created_at', 'desc')
            ->get();

        $data['risk_models'] = SrmModelVersion::where('model_type', 'risk_optimization')
            ->orderBy('created_at', 'desc')
            ->get();

        // Get active models
        $data['active_slippage'] = SrmModelVersion::where('model_type', 'slippage_prediction')
            ->where('status', 'active')
            ->first();

        $data['active_performance'] = SrmModelVersion::where('model_type', 'performance_score')
            ->where('status', 'active')
            ->first();

        $data['active_risk'] = SrmModelVersion::where('model_type', 'risk_optimization')
            ->where('status', 'active')
            ->first();

        return view('smart-risk-management::backend.models.index', $data);
    }

    /**
     * Display the specified model version.
     */
    public function show(int $id): View
    {
        $data['title'] = 'Model Details';

        $model = SrmModelVersion::findOrFail($id);
        $data['model'] = $model;

        return view('smart-risk-management::backend.models.show', $data);
    }

    /**
     * Trigger manual retraining for a model.
     */
    public function retrain(int $id, Request $request): RedirectResponse
    {
        try {
            $model = SrmModelVersion::findOrFail($id);
            
            // Dispatch retraining job
            \Addons\SmartRiskManagement\App\Jobs\RetrainModelsJob::dispatch();
            
            return redirect()->back()->with('success', 'Model retraining job has been queued. Check back in a few minutes.');
        } catch (\Exception $e) {
            Log::error("ModelController: Failed to trigger retraining", [
                'model_id' => $id,
                'error' => $e->getMessage(),
            ]);
            
            return redirect()->back()->with('error', 'Failed to trigger retraining: ' . $e->getMessage());
        }
    }

    /**
     * Deploy model to production.
     */
    public function deploy(int $id, Request $request): RedirectResponse
    {
        try {
            $model = SrmModelVersion::findOrFail($id);
            
            if ($model->status !== 'testing' && $model->status !== 'training') {
                return redirect()->back()->with('error', 'Only testing or training models can be deployed.');
            }
            
            $success = $this->trainingService->deployModel($model->model_type, $model->version);
            
            if ($success) {
                return redirect()->back()->with('success', 'Model deployed successfully.');
            } else {
                return redirect()->back()->with('error', 'Failed to deploy model.');
            }
        } catch (\Exception $e) {
            Log::error("ModelController: Failed to deploy model", [
                'model_id' => $id,
                'error' => $e->getMessage(),
            ]);
            
            return redirect()->back()->with('error', 'Failed to deploy model: ' . $e->getMessage());
        }
    }
}

