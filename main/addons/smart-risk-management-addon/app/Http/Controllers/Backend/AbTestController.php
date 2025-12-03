<?php

namespace Addons\SmartRiskManagement\App\Http\Controllers\Backend;

use Addons\SmartRiskManagement\App\Http\Controllers\Controller;
use Addons\SmartRiskManagement\App\Models\AbTest;
use Addons\SmartRiskManagement\App\Services\AbTestingService;
use App\Helpers\Helper\Helper;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

class AbTestController extends Controller
{
    protected AbTestingService $abTestingService;

    public function __construct(AbTestingService $abTestingService)
    {
        $this->abTestingService = $abTestingService;
    }

    /**
     * Display a listing of A/B tests.
     */
    public function index(): View
    {
        $data['title'] = 'A/B Testing';

        $data['tests'] = AbTest::orderBy('created_at', 'desc')
            ->paginate(Helper::pagination());

        return view('smart-risk-management::backend.ab-tests.index', $data);
    }

    /**
     * Show the form for creating a new A/B test.
     */
    public function create(): View
    {
        $data['title'] = 'Create A/B Test';

        return view('smart-risk-management::backend.ab-tests.create', $data);
    }

    /**
     * Store a newly created A/B test.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'pilot_group_percentage' => 'required|numeric|min:1|max:50',
            'test_duration_days' => 'required|integer|min:1|max:90',
            'pilot_logic' => 'required|json',
        ]);

        try {
            $test = $this->abTestingService->createTest(
                $request->name,
                json_decode($request->pilot_logic, true),
                (float) $request->pilot_group_percentage
            );

            $test->update([
                'description' => $request->description,
                'test_duration_days' => $request->test_duration_days,
                'created_by_admin_id' => auth()->guard('admin')->id(),
            ]);

            return redirect()->route('admin.srm.ab-tests.index')
                ->with('success', 'A/B test created successfully.');
        } catch (\Exception $e) {
            Log::error("AbTestController: Failed to create A/B test", [
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create A/B test: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified A/B test.
     */
    public function show(int $id): View
    {
        $data['title'] = 'A/B Test Details';

        $test = AbTest::with(['assignments', 'createdBy'])->findOrFail($id);
        $data['test'] = $test;

        return view('smart-risk-management::backend.ab-tests.show', $data);
    }

    /**
     * Start an A/B test.
     */
    public function start(int $id): RedirectResponse
    {
        try {
            $test = AbTest::findOrFail($id);

            if ($test->status !== 'draft') {
                return redirect()->back()->with('error', 'Only draft tests can be started.');
            }

            $test->update([
                'status' => 'running',
                'start_date' => now(),
                'end_date' => now()->addDays($test->test_duration_days),
            ]);

            // Assign users to groups
            // This would be done by AbTestingService

            return redirect()->back()->with('success', 'A/B test started successfully.');
        } catch (\Exception $e) {
            Log::error("AbTestController: Failed to start A/B test", [
                'test_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Failed to start A/B test: ' . $e->getMessage());
        }
    }

    /**
     * Stop an A/B test.
     */
    public function stop(int $id): RedirectResponse
    {
        try {
            $test = AbTest::findOrFail($id);

            if ($test->status !== 'running') {
                return redirect()->back()->with('error', 'Only running tests can be stopped.');
            }

            $test->update([
                'status' => 'completed',
                'end_date' => now(),
            ]);

            // Calculate results
            $results = $this->abTestingService->compareResults($id);
            $test->update($results);

            return redirect()->back()->with('success', 'A/B test stopped and results calculated.');
        } catch (\Exception $e) {
            Log::error("AbTestController: Failed to stop A/B test", [
                'test_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Failed to stop A/B test: ' . $e->getMessage());
        }
    }

    /**
     * Show A/B test results.
     */
    public function results(int $id): View
    {
        $data['title'] = 'A/B Test Results';

        $test = AbTest::findOrFail($id);
        $data['test'] = $test;

        $data['results'] = $this->abTestingService->compareResults($id);
        $data['p_value'] = $this->abTestingService->calculateStatisticalSignificance($id);

        return view('smart-risk-management::backend.ab-tests.results', $data);
    }
}

