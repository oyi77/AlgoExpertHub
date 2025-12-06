<?php

namespace Addons\PageBuilderAddon\App\Services;

use Addons\PageBuilderAddon\App\Models\PageBuilderLayout;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LayoutManagerService
{
    /**
     * List all layouts
     */
    public function listLayouts(array $filters = []): array
    {
        try {
            $query = PageBuilderLayout::query();

            if (isset($filters['type']) && $filters['type'] !== 'all') {
                $query->byType($filters['type']);
            }

            if (isset($filters['active']) && $filters['active']) {
                $query->active();
            }

            $layouts = $query->orderBy('order')->get();

            return [
                'type' => 'success',
                'data' => $layouts
            ];
        } catch (\Exception $e) {
            Log::error('LayoutManagerService::listLayouts failed', ['error' => $e->getMessage()]);
            
            return [
                'type' => 'error',
                'message' => 'Failed to list layouts: ' . $e->getMessage(),
                'data' => collect()
            ];
        }
    }

    /**
     * Create layout
     */
    public function createLayout(array $data): array
    {
        try {
            $layout = PageBuilderLayout::create([
                'name' => $data['name'],
                'slug' => $data['slug'] ?? Str::slug($data['name']),
                'title' => $data['title'] ?? $data['name'],
                'description' => $data['description'] ?? null,
                'type' => $data['type'] ?? 'full',
                'structure' => $data['structure'] ?? [],
                'settings' => $data['settings'] ?? [],
                'is_default' => $data['is_default'] ?? false,
                'is_active' => $data['is_active'] ?? true,
                'order' => $data['order'] ?? 0,
            ]);

            // If this is set as default, unset others
            if ($layout->is_default) {
                PageBuilderLayout::where('id', '!=', $layout->id)
                    ->where('type', $layout->type)
                    ->update(['is_default' => false]);
            }

            return [
                'type' => 'success',
                'message' => 'Layout created successfully',
                'data' => $layout
            ];
        } catch (\Exception $e) {
            Log::error('LayoutManagerService::createLayout failed', ['error' => $e->getMessage()]);
            
            return [
                'type' => 'error',
                'message' => 'Failed to create layout: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update layout
     */
    public function updateLayout(int $id, array $data): array
    {
        try {
            $layout = PageBuilderLayout::findOrFail($id);
            $layout->update($data);

            // If this is set as default, unset others
            if (isset($data['is_default']) && $data['is_default']) {
                PageBuilderLayout::where('id', '!=', $layout->id)
                    ->where('type', $layout->type)
                    ->update(['is_default' => false]);
            }

            return [
                'type' => 'success',
                'message' => 'Layout updated successfully',
                'data' => $layout
            ];
        } catch (\Exception $e) {
            Log::error('LayoutManagerService::updateLayout failed', ['error' => $e->getMessage()]);
            
            return [
                'type' => 'error',
                'message' => 'Failed to update layout: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete layout
     */
    public function deleteLayout(int $id): array
    {
        try {
            $layout = PageBuilderLayout::findOrFail($id);
            $layout->delete();

            return [
                'type' => 'success',
                'message' => 'Layout deleted successfully'
            ];
        } catch (\Exception $e) {
            Log::error('LayoutManagerService::deleteLayout failed', ['error' => $e->getMessage()]);
            
            return [
                'type' => 'error',
                'message' => 'Failed to delete layout: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get default layout by type
     */
    public function getDefaultLayout(string $type = 'full'): ?PageBuilderLayout
    {
        return PageBuilderLayout::default()
            ->byType($type)
            ->active()
            ->first();
    }
}
