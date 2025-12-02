<?php

namespace App\Services\Addons;

use App\Support\AddonRegistry;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use ZipArchive;

class AddonManager
{
    /**
     * List installed addons with manifest details.
     */
    public function list(): Collection
    {
        $addonsPath = base_path('addons');

        if (!File::isDirectory($addonsPath)) {
            return collect();
        }

        return collect(File::directories($addonsPath))
            ->map(function (string $directory) use ($addonsPath) {
                $slug = basename($directory);
                $manifest = AddonRegistry::get($slug) ?? [];

                $modules = collect($manifest['modules'] ?? [])
                    ->map(function (array $module) {
                        return array_merge([
                            'key' => $module['key'] ?? null,
                            'name' => $module['name'] ?? Str::of($module['key'] ?? '')->replace('_', ' ')->title()->value(),
                            'description' => $module['description'] ?? null,
                            'targets' => $module['targets'] ?? [],
                            'enabled' => (bool) ($module['enabled'] ?? false),
                        ], $module);
                    })
                    ->values()
                    ->all();

                return [
                    'slug' => $slug,
                    'path' => str_replace($addonsPath . DIRECTORY_SEPARATOR, '', $directory),
                    'status' => $manifest['status'] ?? 'inactive',
                    'title' => $manifest['title'] ?? $slug,
                    'description' => $manifest['description'] ?? null,
                    'version' => $manifest['version'] ?? null,
                    'namespace' => $manifest['namespace'] ?? null,
                    'modules' => $modules,
                    'manifest' => $manifest,
                ];
            })
            ->sortBy('title')
            ->values();
    }

    /**
     * Get a single addon by slug.
     */
    public function getAddon(string $addon): ?array
    {
        $addon = $this->normalizeSlug($addon);
        $addonsPath = base_path('addons');
        $directory = $addonsPath . DIRECTORY_SEPARATOR . $addon;

        if (!File::isDirectory($directory)) {
            return null;
        }

        $manifest = AddonRegistry::get($addon) ?? [];

        $modules = collect($manifest['modules'] ?? [])
            ->map(function (array $module) {
                return array_merge([
                    'key' => $module['key'] ?? null,
                    'name' => $module['name'] ?? Str::of($module['key'] ?? '')->replace('_', ' ')->title()->value(),
                    'description' => $module['description'] ?? null,
                    'targets' => $module['targets'] ?? [],
                    'enabled' => (bool) ($module['enabled'] ?? false),
                ], $module);
            })
            ->values()
            ->all();

        return [
            'slug' => $addon,
            'path' => str_replace($addonsPath . DIRECTORY_SEPARATOR, '', $directory),
            'status' => $manifest['status'] ?? 'inactive',
            'title' => $manifest['title'] ?? $addon,
            'description' => $manifest['description'] ?? null,
            'version' => $manifest['version'] ?? null,
            'namespace' => $manifest['namespace'] ?? null,
            'modules' => $modules,
            'manifest' => $manifest,
        ];
    }

    /**
     * Enable or disable an addon.
     */
    public function setAddonStatus(string $addon, bool $enabled): void
    {
        $addon = $this->normalizeSlug($addon);
        $manifest = AddonRegistry::get($addon);

        if (!$manifest) {
            throw new RuntimeException("Addon [{$addon}] not found.");
        }

        $manifest['status'] = $enabled ? 'active' : 'inactive';
        AddonRegistry::write($addon, $manifest);
    }

    /**
     * Enable or disable a module.
     */
    public function setModuleStatus(string $addon, string $moduleKey, bool $enabled): void
    {
        $addon = $this->normalizeSlug($addon);
        $moduleKey = trim($moduleKey);

        $manifest = AddonRegistry::get($addon);

        if (!$manifest) {
            throw new RuntimeException("Addon [{$addon}] not found.");
        }

        $modules = collect($manifest['modules'] ?? []);
        $index = $modules->search(fn ($module) => ($module['key'] ?? null) === $moduleKey);

        if ($index === false) {
            throw new RuntimeException("Module [{$moduleKey}] not found for addon [{$addon}].");
        }

        $module = $modules[$index];
        $module['enabled'] = $enabled;
        $modules[$index] = $module;

        $manifest['modules'] = $modules->values()->all();

        AddonRegistry::write($addon, $manifest);
    }

    /**
     * Upload and install a new addon package.
     *
     * @return array{slug: string, title: string|null}
     */
    public function upload(UploadedFile $package): array
    {
        $this->assertZipArchiveAvailable();

        if ($package->getClientOriginalExtension() !== 'zip') {
            throw new RuntimeException('Addon package must be a ZIP archive.');
        }

        $tmpRoot = storage_path('app/addon_uploads/' . Str::uuid()->toString());
        File::ensureDirectoryExists($tmpRoot);

        $zipPath = $tmpRoot . '/package.zip';
        $package->move($tmpRoot, 'package.zip');

        $extractPath = $tmpRoot . '/extract';
        File::ensureDirectoryExists($extractPath);

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('Unable to open addon archive.');
        }

        if (!$zip->extractTo($extractPath)) {
            $zip->close();
            throw new RuntimeException('Unable to extract addon archive.');
        }
        $zip->close();
        File::delete($zipPath);

        try {
            $addonRoot = $this->findAddonRoot($extractPath);
            if ($addonRoot === null) {
                throw new RuntimeException('Addon archive must contain addon.json at the root directory.');
            }

            $manifestPath = $addonRoot . '/addon.json';
            $manifest = json_decode(File::get($manifestPath), true);
            if (!is_array($manifest)) {
                throw new RuntimeException('Invalid addon manifest.');
            }

            $slug = $this->normalizeSlug($manifest['name'] ?? basename($addonRoot));
            if ($slug === '') {
                $slug = basename($addonRoot);
            }

            $destination = base_path('addons/' . $slug);

            if (File::exists($destination)) {
                throw new RuntimeException("Addon [{$slug}] already exists.");
            }

            File::ensureDirectoryExists(dirname($destination));
            File::moveDirectory($addonRoot, $destination);

            return [
                'slug' => $slug,
                'title' => $manifest['title'] ?? null,
            ];
        } catch (Throwable $exception) {
            throw $exception;
        } finally {
            if (File::isDirectory($tmpRoot)) {
                File::deleteDirectory($tmpRoot);
            }
        }
    }

    /**
     * Locate the addon root within an extracted archive.
     */
    protected function findAddonRoot(string $extractPath): ?string
    {
        if (File::exists($extractPath . '/addon.json')) {
            return $extractPath;
        }

        $directories = File::directories($extractPath);
        foreach ($directories as $directory) {
            if (File::exists($directory . '/addon.json')) {
                return $directory;
            }
        }

        return null;
    }

    /**
     * Ensure ZipArchive extension is installed.
     */
    protected function assertZipArchiveAvailable(): void
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('PHP ZipArchive extension is required to upload addon packages.');
        }
    }

    /**
     * Sanitize addon slug input.
     */
    protected function normalizeSlug(string $addon): string
    {
        return trim(str_replace(['..', '/', '\\'], '', $addon));
    }
}


