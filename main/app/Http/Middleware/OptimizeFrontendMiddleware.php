<?php

namespace App\Http\Middleware;

use App\Models\GlobalConfiguration;
use App\Services\PerformanceOptimizationService;
use Closure;
use Illuminate\Http\Request;

class OptimizeFrontendMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        try {
            $perf = GlobalConfiguration::getValue('performance', config('performance'));
            $frontend = $perf['frontend'] ?? ['enable' => false];
            if (!($frontend['enable'] ?? false)) {
                return $response;
            }

            $routeName = optional($request->route())->getName();
            $excludedRoutes = $frontend['exclusions']['routes'] ?? [];
            if ($routeName && $this->matchesAny($routeName, $excludedRoutes)) {
                return $response;
            }

            $contentType = $response->headers->get('Content-Type');
            if (!$contentType || stripos($contentType, 'text/html') === false) {
                return $response;
            }

            $content = $response->getContent();
            if (!is_string($content) || $content === '') {
                return $response;
            }

            if (($frontend['lazy_images'] ?? false)) {
                $content = $this->applyLazyLoadImages($content);
            }

            if (($frontend['defer_scripts'] ?? false) || ($frontend['async_scripts'] ?? false)) {
                $content = $this->applyScriptHints($content, $frontend);
            }

            $preload = $frontend['preload'] ?? [];
            if (!empty($preload)) {
                $content = $this->injectResourceHints($content, $preload);
            }

            $response->setContent($content);
        } catch (\Throwable $e) {
        }

        try {
            app(PerformanceOptimizationService::class)->applyHttpCaching($response);
        } catch (\Throwable $e) {
        }

        return $response;
    }

    protected function matchesAny(string $value, array $patterns): bool
    {
        foreach ($patterns as $p) {
            if ($p === $value) return true;
            if (str_ends_with($p, '*') && str_starts_with($value, rtrim($p, '*'))) return true;
        }
        return false;
    }

    protected function applyLazyLoadImages(string $html): string
    {
        return preg_replace('/<img(?![^>]*loading=)[^>]*>/i', function ($m) {
            $tag = $m[0];
            if (stripos($tag, 'loading=') !== false) return $tag;
            $tag = preg_replace('/<img/i', '<img loading="lazy"', $tag, 1);
            return $tag;
        }, $html);
    }

    protected function applyScriptHints(string $html, array $frontend): string
    {
        $exclude = $frontend['exclusions']['scripts'] ?? [];
        $defer = $frontend['defer_scripts'] ?? false;
        $async = $frontend['async_scripts'] ?? false;

        return preg_replace('/<script(?![^>]*\btype=\"application\/ld\+json\")[^>]*src=\"([^\"]+)\"[^>]*><\/script>/i', function ($m) use ($exclude, $defer, $async) {
            $tag = $m[0];
            $src = $m[1];
            foreach ($exclude as $ex) {
                if (str_contains($src, $ex)) return $tag;
            }
            if (stripos($tag, 'defer') === false && $defer) {
                $tag = str_replace('<script', '<script defer', $tag);
            }
            if (stripos($tag, 'async') === false && $async) {
                $tag = str_replace('<script', '<script async', $tag);
            }
            return $tag;
        }, $html);
    }

    protected function injectResourceHints(string $html, array $preload): string
    {
        $hints = [];
        foreach (($preload['fonts'] ?? []) as $href) {
            $hints[] = '<link rel="preload" as="font" href="' . e($href) . '" crossorigin="anonymous">';
        }
        foreach (($preload['styles'] ?? []) as $href) {
            $hints[] = '<link rel="preload" as="style" href="' . e($href) . '">';
        }
        foreach (($preload['scripts'] ?? []) as $href) {
            $hints[] = '<link rel="preload" as="script" href="' . e($href) . '">';
        }
        foreach (($preload['dns_prefetch'] ?? []) as $host) {
            $hints[] = '<link rel="dns-prefetch" href="//' . e($host) . '">';
        }

        if (empty($hints)) return $html;

        if (preg_match('/<head[^>]*>/i', $html)) {
            return preg_replace('/<head[^>]*>/i', '$0' . implode("\n", $hints), $html, 1);
        }

        return implode("\n", $hints) . $html;
    }
}
