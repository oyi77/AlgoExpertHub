<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\ResponsiveDesignService;

class ResponsiveDesignMiddleware
{
    protected ResponsiveDesignService $responsiveDesignService;

    public function __construct(ResponsiveDesignService $responsiveDesignService)
    {
        $this->responsiveDesignService = $responsiveDesignService;
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
        $userAgent = $request->header('User-Agent', '');
        
        // Add device information to request
        $request->attributes->set('device_classes', $this->responsiveDesignService->getDeviceClasses($userAgent));
        $request->attributes->set('is_mobile', $this->isMobile($userAgent));
        $request->attributes->set('is_tablet', $this->isTablet($userAgent));
        $request->attributes->set('is_desktop', $this->isDesktop($userAgent));
        $request->attributes->set('is_touch_device', $this->isTouchDevice($userAgent));
        
        // Add responsive design data to view
        view()->share('deviceClasses', $request->attributes->get('device_classes'));
        view()->share('isMobile', $request->attributes->get('is_mobile'));
        view()->share('isTablet', $request->attributes->get('is_tablet'));
        view()->share('isDesktop', $request->attributes->get('is_desktop'));
        view()->share('isTouchDevice', $request->attributes->get('is_touch_device'));
        view()->share('breakpoints', $this->responsiveDesignService->getBreakpoints());
        view()->share('touchOptimizedClasses', $this->responsiveDesignService->getTouchOptimizedClasses());

        $response = $next($request);

        // Add responsive design headers
        if ($response instanceof \Illuminate\Http\Response || $response instanceof \Illuminate\Http\JsonResponse) {
            $response->header('X-Device-Type', $this->getDeviceType($userAgent));
            $response->header('X-Touch-Enabled', $this->isTouchDevice($userAgent) ? 'true' : 'false');
        }

        return $response;
    }

    /**
     * Check if user agent is mobile.
     *
     * @param string $userAgent
     * @return bool
     */
    private function isMobile(string $userAgent): bool
    {
        return preg_match('/Mobile|Android|iPhone|iPod|BlackBerry|IEMobile|Opera Mini/i', $userAgent) && 
               !preg_match('/iPad|Tablet/i', $userAgent);
    }

    /**
     * Check if user agent is tablet.
     *
     * @param string $userAgent
     * @return bool
     */
    private function isTablet(string $userAgent): bool
    {
        return preg_match('/iPad|Android(?!.*Mobile)|Tablet/i', $userAgent);
    }

    /**
     * Check if user agent is desktop.
     *
     * @param string $userAgent
     * @return bool
     */
    private function isDesktop(string $userAgent): bool
    {
        return !$this->isMobile($userAgent) && !$this->isTablet($userAgent);
    }

    /**
     * Check if user agent supports touch.
     *
     * @param string $userAgent
     * @return bool
     */
    private function isTouchDevice(string $userAgent): bool
    {
        return $this->isMobile($userAgent) || $this->isTablet($userAgent);
    }

    /**
     * Get device type string.
     *
     * @param string $userAgent
     * @return string
     */
    private function getDeviceType(string $userAgent): string
    {
        if ($this->isMobile($userAgent)) {
            return 'mobile';
        }
        
        if ($this->isTablet($userAgent)) {
            return 'tablet';
        }
        
        return 'desktop';
    }
}