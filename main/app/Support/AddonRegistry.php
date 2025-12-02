<?php

namespace App\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class AddonRegistry
{
    /**
     * @var array<string, array|null>
     */
    protected static array $manifestCache = [];

    /**
     * Retrieve the manifest for a given addon.
     */
    public static function get(string $addon): ?array
    {
        $addon = self::normalizeSlug($addon);

        if (!array_key_exists($addon, self::$manifestCache)) {
            self::$manifestCache[$addon] = self::loadManifest($addon);
        }

        return self::$manifestCache[$addon];
    }

    /**
     * Determine if an addon is active.
     */
    public static function active(string $addon): bool
    {
        $manifest = self::get($addon);

        if (!$manifest) {
            return false;
        }

        return Arr::get($manifest, 'status', 'inactive') === 'active';
    }

    /**
     * Determine if a module within an addon is enabled.
     */
    public static function moduleEnabled(string $addon, string $moduleKey): bool
    {
        $manifest = self::get($addon);
        if (!$manifest) {
            return false;
        }

        $module = collect($manifest['modules'] ?? [])
            ->firstWhere('key', $moduleKey);

        return (bool) ($module['enabled'] ?? false);
    }

    /**
     * List all modules for an addon.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function modules(string $addon): array
    {
        $manifest = self::get($addon);

        return $manifest['modules'] ?? [];
    }

    /**
     * Return all available addons with manifests.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public static function all(): Collection
    {
        $addonsPath = base_path('addons');

        if (!File::isDirectory($addonsPath)) {
            return collect();
        }

        return collect(File::directories($addonsPath))
            ->map(function (string $directory) use ($addonsPath) {
                $slug = basename($directory);
                $manifest = self::get($slug);

                return [
                    'slug' => $slug,
                    'path' => str_replace($addonsPath . DIRECTORY_SEPARATOR, '', $directory),
                    'manifest' => $manifest,
                ];
            })
            ->filter(fn ($addon) => !is_null($addon['manifest']))
            ->values();
    }

    /**
     * Persist manifest changes.
     */
    public static function write(string $addon, array $manifest): void
    {
        $addon = self::normalizeSlug($addon);
        $path = base_path("addons/{$addon}/addon.json");

        File::put(
            $path,
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL
        );

        self::refresh($addon);
    }

    /**
     * Flush a specific addon from cache or the entire cache.
     */
    public static function refresh(?string $addon = null): void
    {
        if ($addon === null) {
            self::$manifestCache = [];

            return;
        }

        unset(self::$manifestCache[self::normalizeSlug($addon)]);
    }

    /**
     * Load a manifest from disk.
     */
    protected static function loadManifest(string $addon): ?array
    {
        $path = base_path("addons/{$addon}/addon.json");

        if (!File::exists($path)) {
            return null;
        }

        $contents = File::get($path);
        $decoded = json_decode($contents, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Normalize addon slug to prevent directory traversal.
     */
    protected static function normalizeSlug(string $addon): string
    {
        return trim(str_replace(['..', '/', '\\'], '', $addon));
    }
}


