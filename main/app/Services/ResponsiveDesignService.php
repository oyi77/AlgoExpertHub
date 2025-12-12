<?php

namespace App\Services;

use App\Services\BaseService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class ResponsiveDesignService extends BaseService
{
    /**
     * Get responsive breakpoints configuration.
     *
     * @return array
     */
    public function getBreakpoints(): array
    {
        return [
            'xs' => 0,      // Extra small devices (portrait phones)
            'sm' => 576,    // Small devices (landscape phones)
            'md' => 768,    // Medium devices (tablets)
            'lg' => 992,    // Large devices (desktops)
            'xl' => 1200,   // Extra large devices (large desktops)
            'xxl' => 1400,  // Extra extra large devices
        ];
    }

    /**
     * Get device-specific CSS classes.
     *
     * @param string $userAgent
     * @return array
     */
    public function getDeviceClasses(string $userAgent): array
    {
        $classes = ['responsive'];
        
        // Mobile detection
        if ($this->isMobile($userAgent)) {
            $classes[] = 'mobile-device';
            $classes[] = 'touch-enabled';
        }
        
        // Tablet detection
        if ($this->isTablet($userAgent)) {
            $classes[] = 'tablet-device';
            $classes[] = 'touch-enabled';
        }
        
        // Desktop detection
        if ($this->isDesktop($userAgent)) {
            $classes[] = 'desktop-device';
        }
        
        // Touch device detection
        if ($this->isTouchDevice($userAgent)) {
            $classes[] = 'touch-device';
        }
        
        return $classes;
    }

    /**
     * Generate responsive image sources.
     *
     * @param string $imagePath
     * @param array $sizes
     * @return array
     */
    public function generateResponsiveImageSources(string $imagePath, array $sizes = []): array
    {
        $defaultSizes = [
            'xs' => 320,
            'sm' => 576,
            'md' => 768,
            'lg' => 992,
            'xl' => 1200,
        ];
        
        $sizes = array_merge($defaultSizes, $sizes);
        $sources = [];
        
        foreach ($sizes as $breakpoint => $width) {
            $sources[$breakpoint] = [
                'src' => $this->generateResponsiveImageUrl($imagePath, $width),
                'width' => $width,
                'media' => $this->getMediaQuery($breakpoint),
            ];
        }
        
        return $sources;
    }

    /**
     * Get optimized viewport meta tag.
     *
     * @return string
     */
    public function getViewportMetaTag(): string
    {
        return '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">';
    }

    /**
     * Get touch-optimized CSS classes for interactive elements.
     *
     * @return array
     */
    public function getTouchOptimizedClasses(): array
    {
        return [
            'button' => 'btn-touch-optimized',
            'link' => 'link-touch-optimized',
            'input' => 'input-touch-optimized',
            'select' => 'select-touch-optimized',
            'checkbox' => 'checkbox-touch-optimized',
            'radio' => 'radio-touch-optimized',
        ];
    }

    /**
     * Generate Progressive Web App manifest.
     *
     * @return array
     */
    public function generatePWAManifest(): array
    {
        return [
            'name' => config('app.name'),
            'short_name' => config('app.name'),
            'description' => 'AlgoExpertHub Trading Signal Platform',
            'start_url' => '/',
            'display' => 'standalone',
            'background_color' => '#ffffff',
            'theme_color' => '#007bff',
            'orientation' => 'portrait-primary',
            'icons' => [
                [
                    'src' => '/icons/icon-72x72.png',
                    'sizes' => '72x72',
                    'type' => 'image/png',
                ],
                [
                    'src' => '/icons/icon-96x96.png',
                    'sizes' => '96x96',
                    'type' => 'image/png',
                ],
                [
                    'src' => '/icons/icon-128x128.png',
                    'sizes' => '128x128',
                    'type' => 'image/png',
                ],
                [
                    'src' => '/icons/icon-144x144.png',
                    'sizes' => '144x144',
                    'type' => 'image/png',
                ],
                [
                    'src' => '/icons/icon-152x152.png',
                    'sizes' => '152x152',
                    'type' => 'image/png',
                ],
                [
                    'src' => '/icons/icon-192x192.png',
                    'sizes' => '192x192',
                    'type' => 'image/png',
                ],
                [
                    'src' => '/icons/icon-384x384.png',
                    'sizes' => '384x384',
                    'type' => 'image/png',
                ],
                [
                    'src' => '/icons/icon-512x512.png',
                    'sizes' => '512x512',
                    'type' => 'image/png',
                ],
            ],
        ];
    }

    /**
     * Get mobile-optimized loading strategies.
     *
     * @return array
     */
    public function getMobileLoadingStrategies(): array
    {
        return [
            'critical_css' => [
                'inline' => true,
                'max_size' => '14kb',
                'above_fold_only' => true,
            ],
            'lazy_loading' => [
                'images' => true,
                'iframes' => true,
                'threshold' => '50px',
            ],
            'resource_hints' => [
                'preload' => ['fonts', 'critical-images'],
                'prefetch' => ['next-page-resources'],
                'preconnect' => ['external-apis'],
            ],
            'compression' => [
                'gzip' => true,
                'brotli' => true,
                'minification' => true,
            ],
        ];
    }

    /**
     * Check if user agent is mobile.
     *
     * @param string $userAgent
     * @return bool
     */
    private function isMobile(string $userAgent): bool
    {
        return preg_match('/Mobile|Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i', $userAgent);
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
     * Generate responsive image URL.
     *
     * @param string $imagePath
     * @param int $width
     * @return string
     */
    private function generateResponsiveImageUrl(string $imagePath, int $width): string
    {
        // In a real implementation, this would integrate with an image processing service
        // For now, return the original image path
        return asset($imagePath);
    }

    /**
     * Get media query for breakpoint.
     *
     * @param string $breakpoint
     * @return string
     */
    private function getMediaQuery(string $breakpoint): string
    {
        $breakpoints = $this->getBreakpoints();
        $width = $breakpoints[$breakpoint] ?? 0;
        
        return $width > 0 ? "(min-width: {$width}px)" : "";
    }
}