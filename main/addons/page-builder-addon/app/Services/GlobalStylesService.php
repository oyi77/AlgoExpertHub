<?php

namespace Addons\PageBuilderAddon\App\Services;

use Addons\PageBuilderAddon\App\Models\PageBuilderGlobalStyle;
use Illuminate\Support\Facades\Log;

class GlobalStylesService
{
    /**
     * List all global styles
     */
    public function listStyles(array $filters = []): array
    {
        try {
            $query = PageBuilderGlobalStyle::query();

            if (isset($filters['type']) && $filters['type'] !== 'all') {
                $query->byType($filters['type']);
            }

            if (isset($filters['active']) && $filters['active']) {
                $query->active();
            }

            $styles = $query->orderBy('order')->get();

            return [
                'type' => 'success',
                'data' => $styles
            ];
        } catch (\Exception $e) {
            Log::error('GlobalStylesService::listStyles failed', ['error' => $e->getMessage()]);
            
            return [
                'type' => 'error',
                'message' => 'Failed to list styles: ' . $e->getMessage(),
                'data' => collect()
            ];
        }
    }

    /**
     * Create global style
     */
    public function createStyle(array $data): array
    {
        try {
            $style = PageBuilderGlobalStyle::create([
                'name' => $data['name'],
                'type' => $data['type'] ?? 'css',
                'content' => $data['content'],
                'settings' => $data['settings'] ?? [],
                'is_active' => $data['is_active'] ?? true,
                'order' => $data['order'] ?? 0,
            ]);

            return [
                'type' => 'success',
                'message' => 'Global style created successfully',
                'data' => $style
            ];
        } catch (\Exception $e) {
            Log::error('GlobalStylesService::createStyle failed', ['error' => $e->getMessage()]);
            
            return [
                'type' => 'error',
                'message' => 'Failed to create style: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update global style
     */
    public function updateStyle(int $id, array $data): array
    {
        try {
            $style = PageBuilderGlobalStyle::findOrFail($id);
            $style->update($data);

            return [
                'type' => 'success',
                'message' => 'Global style updated successfully',
                'data' => $style
            ];
        } catch (\Exception $e) {
            Log::error('GlobalStylesService::updateStyle failed', ['error' => $e->getMessage()]);
            
            return [
                'type' => 'error',
                'message' => 'Failed to update style: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete global style
     */
    public function deleteStyle(int $id): array
    {
        try {
            $style = PageBuilderGlobalStyle::findOrFail($id);
            $style->delete();

            return [
                'type' => 'success',
                'message' => 'Global style deleted successfully'
            ];
        } catch (\Exception $e) {
            Log::error('GlobalStylesService::deleteStyle failed', ['error' => $e->getMessage()]);
            
            return [
                'type' => 'error',
                'message' => 'Failed to delete style: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get compiled CSS from all active styles
     */
    public function getCompiledCss(): string
    {
        try {
            $styles = PageBuilderGlobalStyle::active()
                ->orderBy('order')
                ->get();

            $css = '';
            foreach ($styles as $style) {
                $css .= "\n/* {$style->name} */\n";
                $css .= $style->content . "\n";
            }

            return $css;
        } catch (\Exception $e) {
            Log::error('GlobalStylesService::getCompiledCss failed', ['error' => $e->getMessage()]);
            return '';
        }
    }
}
