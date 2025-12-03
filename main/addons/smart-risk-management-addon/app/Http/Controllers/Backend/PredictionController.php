<?php

namespace Addons\SmartRiskManagement\App\Http\Controllers\Backend;

use Addons\SmartRiskManagement\App\Http\Controllers\Controller;
use Addons\SmartRiskManagement\App\Models\SrmPrediction;
use App\Helpers\Helper\Helper;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PredictionController extends Controller
{
    /**
     * Display a listing of predictions.
     */
    public function index(Request $request): View
    {
        $data['title'] = 'SRM Predictions';

        $query = SrmPrediction::query();

        // Filter by type
        if ($request->type) {
            $query->where('prediction_type', $request->type);
        }

        // Filter by date range
        if ($request->date_from) {
            $query->where('created_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->where('created_at', '<=', $request->date_to);
        }

        // Filter by accuracy range
        if ($request->accuracy_min) {
            $query->where('accuracy', '>=', $request->accuracy_min);
        }
        if ($request->accuracy_max) {
            $query->where('accuracy', '<=', $request->accuracy_max);
        }

        $data['predictions'] = $query->orderBy('created_at', 'desc')
            ->paginate(Helper::pagination());

        // Calculate average accuracy
        $data['avg_accuracy'] = SrmPrediction::whereNotNull('accuracy')
            ->avg('accuracy') ?? 0;

        return view('smart-risk-management::backend.predictions.index', $data);
    }

    /**
     * Display the specified prediction.
     */
    public function show(int $id): View
    {
        $data['title'] = 'Prediction Details';

        $prediction = SrmPrediction::with(['executionLog', 'signal'])->findOrFail($id);
        $data['prediction'] = $prediction;

        return view('smart-risk-management::backend.predictions.show', $data);
    }
}

