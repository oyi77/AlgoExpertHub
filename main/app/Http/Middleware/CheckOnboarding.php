<?php

namespace App\Http\Middleware;

use App\Services\UserOnboardingService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckOnboarding
{
    protected $onboardingService;

    public function __construct(UserOnboardingService $onboardingService)
    {
        $this->onboardingService = $onboardingService;
    }

    /**
     * Handle an incoming request.
     * Redirect users to onboarding if incomplete
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Only check for authenticated users
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        // Skip onboarding check for these routes
        $skipRoutes = [
            'user.onboarding.*',
            'user.logout',
            'user.profile',
            'user.profileupdate',
            'user.plans',
            'user.deposit',
            'user.deposit.*',
            'user.payment.*',
            'user.paynow.*',
            'user.gateway.*',
        ];

        // Get route name safely
        $route = $request->route();
        if ($route === null) {
            return $next($request);
        }
        
        $routeName = $route->getName();
        
        // If route name is null, allow (might be closure routes)
        if ($routeName === null) {
            return $next($request);
        }

        // Check if current route should be skipped
        foreach ($skipRoutes as $skipPattern) {
            if (str_contains($skipPattern, '*')) {
                $pattern = str_replace('*', '.*', preg_quote($skipPattern, '/'));
                $pattern = str_replace('\.\*', '.*', $pattern);
                if (preg_match('/^' . $pattern . '$/', $routeName)) {
                    return $next($request);
                }
            } elseif ($routeName === $skipPattern) {
                return $next($request);
            }
        }

        // Check if onboarding should be shown
        if ($this->onboardingService->shouldShowOnboarding($user)) {
            // Check if user is already on an onboarding route
            if (!$request->routeIs('user.onboarding.*')) {
                return redirect()->route('user.onboarding.welcome')
                    ->with('info', __('Please complete the onboarding process to continue.'));
            }
        }

        return $next($request);
    }
}
