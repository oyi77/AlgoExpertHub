<?php

namespace App\Services;

use App\Models\GlobalConfiguration;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Intervention\Image\ImageManagerStatic as Image;

class PerformanceOptimizationService
{
    public function applyHttpCaching(Response $response): void
    {
        $perf = GlobalConfiguration::getValue('performance', config('performance'));
        $http = $perf['http'] ?? ['enable' => false];
        if (!($http['enable'] ?? false)) return;

        $path = request()->path();
        foreach (($http['blacklist']['paths'] ?? []) as $p) {
            if (str_starts_with($path, ltrim($p, '/'))) return;
        }

        if (($http['cache_headers']['enabled'] ?? false)) {
            $ttl = (int)($http['cache_headers']['ttl'] ?? 3600);
            $response->headers->set('Cache-Control', 'public, max-age=' . $ttl);
        }

        if (($http['etag']['enabled'] ?? false)) {
            $etag = sha1($response->getContent() ?? '');
            $response->headers->set('ETag', 'W/"' . $etag . '"');
        }
    }

    public function prewarmRoutes(array $routes): array
    {
        $results = [];
        foreach ($routes as $name) {
            try {
                $url = route($name);
            } catch (\Throwable $e) {
                $results[$name] = ['ok' => false, 'error' => 'route not found'];
                continue;
            }
            try {
                $start = microtime(true);
                $content = file_get_contents($url);
                $time = round((microtime(true) - $start) * 1000);
                $results[$name] = ['ok' => $content !== false, 'ms' => $time];
            } catch (\Throwable $e) {
                $results[$name] = ['ok' => false, 'error' => $e->getMessage()];
            }
        }
        return $results;
    }

    public function cleanupDatabase(int $pruneDays = 14): array
    {
        $summary = [];
        try {
            $deadline = now()->subDays($pruneDays);
            $summary['failed_jobs_deleted'] = DB::table('failed_jobs')->where('failed_at', '<', $deadline)->delete();
        } catch (\Throwable $e) {
            $summary['failed_jobs_error'] = $e->getMessage();
        }
        try {
            if (DB::getDriverName() === 'mysql') {
                foreach (DB::select('SHOW TABLES') as $row) {
                    $table = array_values((array)$row)[0];
                    DB::statement('OPTIMIZE TABLE `' . $table . '`');
                }
                $summary['tables_optimized'] = true;
            } elseif (DB::getDriverName() === 'sqlite') {
                DB::statement('VACUUM;');
                $summary['vacuum'] = true;
            }
        } catch (\Throwable $e) {
            $summary['optimize_error'] = $e->getMessage();
        }
        return $summary;
    }

    public function optimizeMedia(string $path): array
    {
        $perf = GlobalConfiguration::getValue('performance', config('performance'));
        $media = $perf['media'] ?? ['enable' => false];
        if (!($media['enable'] ?? false)) return ['ok' => false, 'error' => 'disabled'];

        try {
            $img = Image::make($path);
            $maxW = (int)($media['max_width'] ?? 1920);
            $maxH = (int)($media['max_height'] ?? 1920);
            $img->resize($maxW, $maxH, function ($c) { $c->aspectRatio(); $c->upsize(); });
            $img->save($path, 80);

            $webpPath = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $path);
            if (($media['convert_webp'] ?? false) && $webpPath) {
                $img->encode('webp', 80)->save($webpPath);
            }
            return ['ok' => true, 'path' => $path];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}

