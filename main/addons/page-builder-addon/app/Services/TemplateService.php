<?php

namespace Addons\PageBuilderAddon\App\Services;

use Addons\PageBuilderAddon\App\Models\PageBuilderTemplate;
use Illuminate\Support\Facades\Log;

class TemplateService
{
    /**
     * Create reusable page template
     */
    public function createTemplate(array $data): array
    {
        try {
            $template = PageBuilderTemplate::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'content' => $data['content'] ?? [],
                'preview_image' => $data['preview_image'] ?? null,
                'category' => $data['category'] ?? 'general',
                'status' => $data['status'] ?? true,
            ]);
            
            return [
                'type' => 'success',
                'message' => 'Template created successfully',
                'data' => $template
            ];
        } catch (\Exception $e) {
            Log::error('TemplateService::createTemplate failed', ['error' => $e->getMessage()]);
            
            return [
                'type' => 'error',
                'message' => 'Failed to create template: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update template
     */
    public function updateTemplate(int $templateId, array $data): array
    {
        try {
            $template = PageBuilderTemplate::findOrFail($templateId);
            
            // Handle content update
            if (isset($data['content'])) {
                $template->update([
                    'content' => $data['content']
                ]);
            } else {
                $template->update($data);
            }
            
            return [
                'type' => 'success',
                'message' => 'Template updated successfully',
                'data' => $template
            ];
        } catch (\Exception $e) {
            Log::error('TemplateService::updateTemplate failed', ['error' => $e->getMessage()]);
            
            return [
                'type' => 'error',
                'message' => 'Failed to update template: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Apply template to page
     */
    public function applyTemplate(int $templateId, int $pageId): array
    {
        try {
            $template = PageBuilderTemplate::findOrFail($templateId);
            $page = \App\Models\Page::findOrFail($pageId);
            
            // Check if pagebuilder table exists
            if (!\Addons\PageBuilderAddon\App\Models\PageBuilderPage::tableExists()) {
                return [
                    'type' => 'error',
                    'message' => 'Pagebuilder table not available. Please run migrations.'
                ];
            }
            
            // Apply template content to pagebuilder page
            if ($page->pagebuilder_page_id) {
                $pagebuilderPage = \Addons\PageBuilderAddon\App\Models\PageBuilderPage::find($page->pagebuilder_page_id);
                if ($pagebuilderPage) {
                    $pagebuilderPage->update([
                        'data' => $template->content
                    ]);
                } else {
                    // Create pagebuilder page if doesn't exist
                    $pagebuilderPage = \Addons\PageBuilderAddon\App\Models\PageBuilderPage::create([
                        'name' => $page->slug,
                        'title' => $page->name,
                        'route' => '/pages/' . $page->slug,
                        'layout' => 'default',
                        'data' => $template->content,
                    ]);
                    $page->update(['pagebuilder_page_id' => $pagebuilderPage->id]);
                }
            } else {
                // Create pagebuilder page if doesn't exist
                $pagebuilderPage = \Addons\PageBuilderAddon\App\Models\PageBuilderPage::create([
                    'name' => $page->slug,
                    'title' => $page->name,
                    'route' => '/pages/' . $page->slug,
                    'layout' => 'default',
                    'data' => $template->content,
                ]);
                $page->update(['pagebuilder_page_id' => $pagebuilderPage->id]);
            }
            
            return [
                'type' => 'success',
                'message' => 'Template applied successfully'
            ];
        } catch (\Exception $e) {
            Log::error('TemplateService::applyTemplate failed', ['error' => $e->getMessage()]);
            
            return [
                'type' => 'error',
                'message' => 'Failed to apply template: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get all templates
     */
    public function listTemplates(): array
    {
        try {
            $templates = PageBuilderTemplate::where('status', true)
                ->orderBy('name')
                ->get();
            
            return [
                'type' => 'success',
                'data' => $templates
            ];
        } catch (\Exception $e) {
            Log::error('TemplateService::listTemplates failed', ['error' => $e->getMessage()]);
            
            return [
                'type' => 'error',
                'message' => 'Failed to list templates: ' . $e->getMessage()
            ];
        }
    }
}
