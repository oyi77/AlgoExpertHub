<?php

namespace Addons\PageBuilderAddon\App\Services;

use Addons\PageBuilderAddon\App\Models\PageBuilderMenu;
use App\Models\Page;
use Illuminate\Support\Facades\Log;

class MenuManagerService
{
    /**
     * Create menu structure
     */
    public function createMenu(array $data): array
    {
        try {
            $menu = PageBuilderMenu::create([
                'name' => $data['name'] ?? 'Main Menu',
                'structure' => $data['structure'] ?? [],
                'status' => $data['status'] ?? true,
            ]);
            
            return [
                'type' => 'success',
                'message' => 'Menu created successfully',
                'data' => $menu
            ];
        } catch (\Exception $e) {
            Log::error('MenuManagerService::createMenu failed', ['error' => $e->getMessage()]);
            
            return [
                'type' => 'error',
                'message' => 'Failed to create menu: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update menu
     */
    public function updateMenu(int $menuId, array $data): array
    {
        try {
            $menu = PageBuilderMenu::findOrFail($menuId);
            $menu->update([
                'name' => $data['name'] ?? $menu->name,
                'structure' => $data['structure'] ?? $menu->structure,
                'status' => $data['status'] ?? $menu->status,
            ]);
            
            return [
                'type' => 'success',
                'message' => 'Menu updated successfully',
                'data' => $menu
            ];
        } catch (\Exception $e) {
            Log::error('MenuManagerService::updateMenu failed', ['error' => $e->getMessage()]);
            
            return [
                'type' => 'error',
                'message' => 'Failed to update menu: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get hierarchical menu structure
     */
    public function getMenuStructure(): array
    {
        try {
            // Get all menus
            $menus = PageBuilderMenu::where('status', true)->get();
            
            // Also get pages for menu building
            $pages = Page::where('status', 1)
                ->orderBy('order')
                ->get();

            return [
                'type' => 'success',
                'data' => [
                    'menus' => $menus,
                    'pages' => $pages
                ]
            ];
        } catch (\Exception $e) {
            Log::error('MenuManagerService::getMenuStructure failed', ['error' => $e->getMessage()]);
            
            return [
                'type' => 'error',
                'message' => 'Failed to get menu structure: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Auto-sync menu from pages
     */
    public function syncMenuWithPages(): array
    {
        try {
            $pages = Page::where('status', 1)
                ->orderBy('order')
                ->get();

            $structure = [];
            foreach ($pages as $page) {
                $structure[] = [
                    'id' => $page->id,
                    'name' => $page->name,
                    'slug' => $page->slug,
                    'url' => '/pages/' . $page->slug,
                    'order' => $page->order,
                ];
            }

            // Update or create default menu
            $menu = PageBuilderMenu::firstOrCreate(
                ['name' => 'Main Menu'],
                ['structure' => $structure, 'status' => true]
            );

            if (!$menu->wasRecentlyCreated) {
                $menu->update(['structure' => $structure]);
            }
            
            return [
                'type' => 'success',
                'message' => 'Menu synced successfully',
                'data' => ['synced' => count($structure)]
            ];
        } catch (\Exception $e) {
            Log::error('MenuManagerService::syncMenuWithPages failed', ['error' => $e->getMessage()]);
            
            return [
                'type' => 'error',
                'message' => 'Failed to sync menu: ' . $e->getMessage()
            ];
        }
    }
}
