<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Helpers\Helper\Helper;
use App\Helpers\NotificationHelper;
use App\Models\Plan;
use App\Services\UserPlanService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class PlanController extends Controller
{
    protected UserPlanService $planService;

    public function __construct(UserPlanService $planService)
    {
        $this->planService = $planService;
    }

    /**
     * Display the plans page.
     * 
     * @return View
     */
    public function plans(): View
    {
        $data['title'] = 'Plans';
        $data['plans'] = Plan::where('status', true)->paginate(Helper::pagination());

        return view(Helper::themeView('user.plans'))->with($data);
    }

    /**
     * Subscribe to a plan.
     * 
     * @param Request $request
     * @return RedirectResponse
     */
    public function subscribe(Request $request): RedirectResponse
    {
        $result = $this->planService->subscribe($request);

        if ($result['type'] === 'error') {
            return redirect()->back()->with('notify', NotificationHelper::error($result['message'], 'Error'));
        }

        if ($result['type'] === 'redirect') {
            return redirect()->to($result['message']);
        }
        
        return redirect()->back()->with('notify', NotificationHelper::success($result['message'], 'Success'));
    }

    // ========== BETA METHOD ==========

    public function betaPlans()
    {
        $data['title'] = 'Plans';
        $data['plans'] = Plan::where('status', true)->paginate(10);

        return Inertia::render('User/Plans', $data);
    }
}
