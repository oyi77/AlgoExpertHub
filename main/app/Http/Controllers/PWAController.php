<?php

namespace App\Http\Controllers;

use App\Services\ResponsiveDesignService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PWAController extends Controller
{
    protected ResponsiveDesignService $responsiveDesignService;

    public function __construct(ResponsiveDesignService $responsiveDesignService)
    {
        $this->responsiveDesignService = $responsiveDesignService;
    }

    /**
     * Generate PWA manifest.json file.
     *
     * @return JsonResponse
     */
    public function manifest(): JsonResponse
    {
        $manifest = $this->responsiveDesignService->generatePWAManifest();
        
        return response()->json($manifest)
            ->header('Content-Type', 'application/manifest+json')
            ->header('Cache-Control', 'public, max-age=86400'); // Cache for 24 hours
    }

    /**
     * Generate service worker JavaScript file.
     *
     * @return \Illuminate\Http\Response
     */
    public function serviceWorker()
    {
        $serviceWorkerContent = $this->generateServiceWorkerContent();
        
        return response($serviceWorkerContent)
            ->header('Content-Type', 'application/javascript')
            ->header('Cache-Control', 'public, max-age=3600'); // Cache for 1 hour
    }

    /**
     * Handle offline page.
     *
     * @return \Illuminate\View\View
     */
    public function offline()
    {
        return view('pwa.offline');
    }

    /**
     * Install PWA page.
     *
     * @return \Illuminate\View\View
     */
    public function install()
    {
        return view('pwa.install');
    }

    /**
     * Generate service worker content.
     *
     * @return string
     */
    private function generateServiceWorkerContent(): string
    {
        $version = config('app.version', '1.0.0');
        $cacheName = config('app.name') . '-v' . $version;
        
        return "
const CACHE_NAME = '{$cacheName}';
const urlsToCache = [
    '/',
    '/css/app.css',
    '/js/app.js',
    '/offline',
    '/icons/icon-192x192.png',
    '/icons/icon-512x512.png'
];

// Install event
self.addEventListener('install', function(event) {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(function(cache) {
                console.log('Opened cache');
                return cache.addAll(urlsToCache);
            })
    );
});

// Fetch event
self.addEventListener('fetch', function(event) {
    event.respondWith(
        caches.match(event.request)
            .then(function(response) {
                // Cache hit - return response
                if (response) {
                    return response;
                }

                return fetch(event.request).then(
                    function(response) {
                        // Check if we received a valid response
                        if(!response || response.status !== 200 || response.type !== 'basic') {
                            return response;
                        }

                        // Clone the response
                        var responseToCache = response.clone();

                        caches.open(CACHE_NAME)
                            .then(function(cache) {
                                cache.put(event.request, responseToCache);
                            });

                        return response;
                    }
                ).catch(function() {
                    // Return offline page for navigation requests
                    if (event.request.destination === 'document') {
                        return caches.match('/offline');
                    }
                });
            })
    );
});

// Activate event
self.addEventListener('activate', function(event) {
    event.waitUntil(
        caches.keys().then(function(cacheNames) {
            return Promise.all(
                cacheNames.map(function(cacheName) {
                    if (cacheName !== CACHE_NAME) {
                        console.log('Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});

// Background sync
self.addEventListener('sync', function(event) {
    if (event.tag === 'background-sync') {
        event.waitUntil(doBackgroundSync());
    }
});

function doBackgroundSync() {
    // Implement background sync logic here
    console.log('Background sync triggered');
}

// Push notifications
self.addEventListener('push', function(event) {
    const options = {
        body: event.data ? event.data.text() : 'New notification',
        icon: '/icons/icon-192x192.png',
        badge: '/icons/icon-72x72.png',
        vibrate: [100, 50, 100],
        data: {
            dateOfArrival: Date.now(),
            primaryKey: 1
        },
        actions: [
            {
                action: 'explore',
                title: 'View',
                icon: '/icons/checkmark.png'
            },
            {
                action: 'close',
                title: 'Close',
                icon: '/icons/xmark.png'
            }
        ]
    };

    event.waitUntil(
        self.registration.showNotification('" . config('app.name') . "', options)
    );
});

// Notification click
self.addEventListener('notificationclick', function(event) {
    event.notification.close();

    if (event.action === 'explore') {
        event.waitUntil(
            clients.openWindow('/')
        );
    }
});
";
    }
}