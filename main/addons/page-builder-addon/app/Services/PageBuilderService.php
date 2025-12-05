<?php

namespace Addons\PageBuilderAddon\App\Services;

use Addons\PageBuilderAddon\App\Models\PageBuilderPage;
use App\Models\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PageBuilderService
{
    /**
     * Create page with pagebuilder integration
     */
    public function createPage(array $data): array
    {
        try {
            DB::beginTransaction();

            // Create page in existing system
            $page = Page::create([
                'name' => $data['name'],
                'slug' => $data['slug'] ?? \Illuminate\Support\Str::slug($data['name']),
                'order' => $data['order'] ?? 0,
                'is_dropdown' => $data['is_dropdown'] ?? false,
                'seo_keywords' => $data['seo_keywords'] ?? [],
                'seo_description' => $data['seo_description'] ?? null,
                'status' => $data['status'] ?? true,
            ]);

            // Create pagebuilder page and link
            try {
                if (PageBuilderPage::tableExists()) {
                    $pagebuilderPage = PageBuilderPage::create([
                        'name' => $page->slug,
                        'title' => $page->name,
                        'route' => '/pages/' . $page->slug,
                        'layout' => 'default',
                        'data' => [],
                    ]);

                    $page->update(['pagebuilder_page_id' => $pagebuilderPage->id]);
                }
            } catch (\Exception $e) {
                // Pagebuilder table may not exist yet, continue without it
                Log::warning('Could not create pagebuilder page', ['error' => $e->getMessage()]);
            }

            DB::commit();

            return [
                'type' => 'success',
                'message' => 'Page created successfully',
                'data' => $page
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PageBuilderService::createPage failed', ['error' => $e->getMessage()]);
            
            return [
                'type' => 'error',
                'message' => 'Failed to create page: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update page and sync with pagebuilder
     */
    public function updatePage(int $pageId, array $data): array
    {
        try {
            DB::beginTransaction();

            $page = Page::findOrFail($pageId);
            $page->update($data);

            // Sync with pagebuilder
            if ($page->pagebuilder_page_id && PageBuilderPage::tableExists()) {
                try {
                    $pagebuilderPage = PageBuilderPage::find($page->pagebuilder_page_id);
                    if ($pagebuilderPage) {
                        $pagebuilderPage->update([
                            'name' => $page->slug,
                            'title' => $page->name,
                            'route' => '/pages/' . $page->slug,
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::warning('Could not sync pagebuilder page', ['error' => $e->getMessage()]);
                }
            }

            DB::commit();

            return [
                'type' => 'success',
                'message' => 'Page updated successfully',
                'data' => $page
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PageBuilderService::updatePage failed', ['error' => $e->getMessage()]);
            
            return [
                'type' => 'error',
                'message' => 'Failed to update page: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete page and pagebuilder data
     */
    public function deletePage(int $pageId): array
    {
        try {
            DB::beginTransaction();

            $page = Page::findOrFail($pageId);
            
            // Prevent deleting home page
            if ($page->slug === 'home') {
                return [
                    'type' => 'error',
                    'message' => 'Cannot delete home page'
                ];
            }

            // Delete pagebuilder data
            if ($page->pagebuilder_page_id && PageBuilderPage::tableExists()) {
                try {
                    PageBuilderPage::where('id', $page->pagebuilder_page_id)->delete();
                } catch (\Exception $e) {
                    Log::warning('Could not delete pagebuilder page', ['error' => $e->getMessage()]);
                }
            }

            $page->delete();

            DB::commit();

            return [
                'type' => 'success',
                'message' => 'Page deleted successfully'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PageBuilderService::deletePage failed', ['error' => $e->getMessage()]);
            
            return [
                'type' => 'error',
                'message' => 'Failed to delete page: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get pagebuilder content for rendering
     */
    public function getPageContent(int $pageId): ?string
    {
        try {
            $page = Page::findOrFail($pageId);
            
            if ($page->pagebuilder_page_id && PageBuilderPage::tableExists()) {
                $pagebuilderPage = PageBuilderPage::find($page->pagebuilder_page_id);
                if ($pagebuilderPage && $pagebuilderPage->data) {
                    // Return rendered HTML from pagebuilder data
                    return $this->renderPageBuilderContent($pagebuilderPage->data);
                }
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error('PageBuilderService::getPageContent failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Render pagebuilder content to HTML
     */
    protected function renderPageBuilderContent(array $data): string
    {
        // Return HTML from pagebuilder data
        $html = $data['html'] ?? '';
        $css = $data['css'] ?? '';
        
        if ($css) {
            $html = '<style>' . $css . '</style>' . $html;
        }
        
        return $html ?: '<div class="pagebuilder-content">No content available</div>';
    }

    /**
     * Migrate existing pages to pagebuilder
     */
    public function migrateExistingPages(): array
    {
        try {
            $pages = Page::all();
            $migrated = 0;

            foreach ($pages as $page) {
                // Skip if already has pagebuilder page
                if ($page->pagebuilder_page_id) {
                    continue;
                }

                try {
                    if (PageBuilderPage::tableExists()) {
                        $pagebuilderPage = PageBuilderPage::create([
                            'name' => $page->slug,
                            'title' => $page->name,
                            'route' => '/pages/' . $page->slug,
                            'layout' => 'default',
                            'data' => [],
                        ]);

                        $page->update(['pagebuilder_page_id' => $pagebuilderPage->id]);
                        $migrated++;
                    }
                } catch (\Exception $e) {
                    Log::warning('Could not migrate page', ['page_id' => $page->id, 'error' => $e->getMessage()]);
                }
            }

            return [
                'type' => 'success',
                'message' => "Migrated {$migrated} pages to pagebuilder",
                'data' => ['migrated' => $migrated]
            ];
        } catch (\Exception $e) {
            Log::error('PageBuilderService::migrateExistingPages failed', ['error' => $e->getMessage()]);
            
            return [
                'type' => 'error',
                'message' => 'Migration failed: ' . $e->getMessage()
            ];
        }
    }
}
