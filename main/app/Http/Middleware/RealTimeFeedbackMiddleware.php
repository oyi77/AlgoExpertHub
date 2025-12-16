<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\RealTimeFeedbackService;
use Illuminate\Support\Facades\Session;

class RealTimeFeedbackMiddleware
{
    protected RealTimeFeedbackService $feedbackService;

    public function __construct(RealTimeFeedbackService $feedbackService)
    {
        $this->feedbackService = $feedbackService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Share feedback configuration with views
        view()->share('feedbackConfig', $this->feedbackService->getUserActionFeedbackConfig());
        view()->share('loadingConfig', $this->feedbackService->getLoadingIndicatorsConfig());
        
        // Share current loading states
        view()->share('loadingStates', Session::get('loading_states', []));
        
        // Share notifications
        view()->share('notifications', Session::get('notifications', []));
        
        // Share completion history
        view()->share('completionHistory', Session::get('completion_history', []));

        $response = $next($request);

        // For AJAX requests, add feedback data to response headers
        if ($request->ajax() || $request->wantsJson()) {
            if ($response instanceof \Illuminate\Http\JsonResponse) {
                $responseData = $response->getData(true);
                
                // Add feedback data to JSON response
                if (!isset($responseData['feedback'])) {
                    $responseData['feedback'] = [
                        'loading_states' => Session::get('loading_states', []),
                        'notifications' => Session::get('notifications', []),
                        'config' => $this->feedbackService->getUserActionFeedbackConfig(),
                    ];
                    
                    $response->setData($responseData);
                }
            }
            
            // Add feedback headers
            $response->header('X-Loading-States-Count', count(Session::get('loading_states', [])));
            $response->header('X-Notifications-Count', count(Session::get('notifications', [])));
        }

        return $response;
    }
}