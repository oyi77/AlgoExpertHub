<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ApiVersionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $config = config('app.api_versioning');
        $headerName = $config['header_name'];
        $defaultVersion = $config['default_version'];
        $supportedVersions = $config['supported'];
        $deprecatedVersions = $config['deprecated'];

        // Get version from header or use default
        $requestedVersion = $request->header($headerName, $defaultVersion);
        
        // Validate version
        if (!in_array($requestedVersion, $supportedVersions)) {
            return response()->json([
                'success' => false,
                'message' => 'Unsupported API version',
                'errors' => [
                    'version' => "API version '{$requestedVersion}' is not supported. Supported versions: " . implode(', ', $supportedVersions)
                ]
            ], Response::HTTP_BAD_REQUEST);
        }

        // Set version in request for controllers to use
        $request->attributes->set('api_version', $requestedVersion);

        $response = $next($request);

        // Add version headers to response
        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $response->header('X-API-Version', $requestedVersion);
            $response->header('X-API-Supported-Versions', implode(', ', $supportedVersions));
            
            // Add deprecation warning if needed
            if (in_array($requestedVersion, $deprecatedVersions)) {
                $response->header('X-API-Deprecation-Warning', "API version '{$requestedVersion}' is deprecated");
            }
        }

        return $response;
    }
}