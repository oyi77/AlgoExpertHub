<?php

namespace App\Http\Middleware;

use App\Models\Configuration;
use Closure;
use Illuminate\Http\Request;

class InjectPageVariable
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
        // Share default page variable with all views
        view()->share('page', (object)[
            'name' => 'Page',
            'seo_description' => optional(Configuration::first())->seo_description ?? 'AlgoExpertHub Trading Signal Platform',
            'seo_keywords' => optional(Configuration::first())->seo_tags ?? []
        ]);

        return $next($request);
    }
}

