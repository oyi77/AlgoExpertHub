<?php

namespace Addons\PageBuilderAddon\App\Services;

use Addons\PageBuilderAddon\App\Models\PageBuilderWidget;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WidgetLibraryService
{
    /**
     * List all widgets
     */
    public function listWidgets(array $filters = []): array
    {
        try {
            $query = PageBuilderWidget::query();

            if (isset($filters['category']) && $filters['category'] !== 'all') {
                $query->byCategory($filters['category']);
            }

            if (isset($filters['active']) && $filters['active']) {
                $query->active();
            }

            if (isset($filters['free_only']) && $filters['free_only']) {
                $query->free();
            }

            $widgets = $query->orderBy('order')->get();

            return [
                'type' => 'success',
                'data' => $widgets
            ];
        } catch (\Exception $e) {
            Log::error('WidgetLibraryService::listWidgets failed', ['error' => $e->getMessage()]);
            
            return [
                'type' => 'error',
                'message' => 'Failed to list widgets: ' . $e->getMessage(),
                'data' => collect()
            ];
        }
    }

    /**
     * Get widget categories
     */
    public function getCategories(): array
    {
        try {
            $categories = PageBuilderWidget::active()
                ->distinct()
                ->pluck('category')
                ->toArray();

            return [
                'type' => 'success',
                'data' => $categories
            ];
        } catch (\Exception $e) {
            Log::error('WidgetLibraryService::getCategories failed', ['error' => $e->getMessage()]);
            
            return [
                'type' => 'error',
                'message' => 'Failed to get categories: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Create widget
     */
    public function createWidget(array $data): array
    {
        try {
            $widget = PageBuilderWidget::create([
                'name' => $data['name'],
                'slug' => $data['slug'] ?? Str::slug($data['name']),
                'title' => $data['title'] ?? $data['name'],
                'description' => $data['description'] ?? null,
                'icon' => $data['icon'] ?? null,
                'category' => $data['category'] ?? 'general',
                'config' => $data['config'] ?? [],
                'html_template' => $data['html_template'] ?? null,
                'css_template' => $data['css_template'] ?? null,
                'js_template' => $data['js_template'] ?? null,
                'default_settings' => $data['default_settings'] ?? [],
                'is_active' => $data['is_active'] ?? true,
                'is_pro' => $data['is_pro'] ?? false,
                'order' => $data['order'] ?? 0,
            ]);

            return [
                'type' => 'success',
                'message' => 'Widget created successfully',
                'data' => $widget
            ];
        } catch (\Exception $e) {
            Log::error('WidgetLibraryService::createWidget failed', ['error' => $e->getMessage()]);
            
            return [
                'type' => 'error',
                'message' => 'Failed to create widget: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update widget
     */
    public function updateWidget(int $id, array $data): array
    {
        try {
            $widget = PageBuilderWidget::findOrFail($id);
            $widget->update($data);

            return [
                'type' => 'success',
                'message' => 'Widget updated successfully',
                'data' => $widget
            ];
        } catch (\Exception $e) {
            Log::error('WidgetLibraryService::updateWidget failed', ['error' => $e->getMessage()]);
            
            return [
                'type' => 'error',
                'message' => 'Failed to update widget: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete widget
     */
    public function deleteWidget(int $id): array
    {
        try {
            $widget = PageBuilderWidget::findOrFail($id);
            $widget->delete();

            return [
                'type' => 'success',
                'message' => 'Widget deleted successfully'
            ];
        } catch (\Exception $e) {
            Log::error('WidgetLibraryService::deleteWidget failed', ['error' => $e->getMessage()]);
            
            return [
                'type' => 'error',
                'message' => 'Failed to delete widget: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get widget by slug
     */
    public function getWidgetBySlug(string $slug): ?PageBuilderWidget
    {
        return PageBuilderWidget::where('slug', $slug)->active()->first();
    }
}
