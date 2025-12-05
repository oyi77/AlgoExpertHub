<?php

namespace Addons\PageBuilderAddon\App\Http\Controllers\Api;

use Addons\PageBuilderAddon\App\Models\PageBuilderPage;
use Addons\PageBuilderAddon\App\Services\PageBuilderService;
use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PageBuilderApiController extends Controller
{
    protected $pageBuilderService;

    public function __construct(PageBuilderService $pageBuilderService)
    {
        $this->pageBuilderService = $pageBuilderService;
    }

    /**
     * Save pagebuilder content
     */
    public function saveContent(Request $request, $pageId)
    {
        try {
            $page = Page::findOrFail($pageId);
            $content = $request->input('content');
            $html = $request->input('html');
            $css = $request->input('css');

            DB::beginTransaction();

            // Check if pagebuilder table exists
            if (!PageBuilderPage::tableExists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pagebuilder table not available. Please run migrations.'
                ], 500);
            }

            // Get or create pagebuilder page
            if (!$page->pagebuilder_page_id) {
                $pagebuilderPage = PageBuilderPage::create([
                    'name' => $page->slug,
                    'title' => $page->name,
                    'route' => '/pages/' . $page->slug,
                    'layout' => 'default',
                    'data' => [
                        'content' => $content,
                        'html' => $html,
                        'css' => $css,
                    ],
                ]);
                $page->update(['pagebuilder_page_id' => $pagebuilderPage->id]);
            } else {
                $pagebuilderPage = PageBuilderPage::find($page->pagebuilder_page_id);
                if ($pagebuilderPage) {
                    $pagebuilderPage->update([
                        'data' => [
                            'content' => $content,
                            'html' => $html,
                            'css' => $css,
                        ],
                    ]);
                } else {
                    // Create if doesn't exist
                    $pagebuilderPage = PageBuilderPage::create([
                        'name' => $page->slug,
                        'title' => $page->name,
                        'route' => '/pages/' . $page->slug,
                        'layout' => 'default',
                        'data' => [
                            'content' => $content,
                            'html' => $html,
                            'css' => $css,
                        ],
                    ]);
                    $page->update(['pagebuilder_page_id' => $pagebuilderPage->id]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Content saved successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to save content: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get pagebuilder content
     */
    public function getContent($pageId)
    {
        try {
            $page = Page::findOrFail($pageId);
            
            if ($page->pagebuilder_page_id && PageBuilderPage::tableExists()) {
                $pagebuilderPage = PageBuilderPage::find($page->pagebuilder_page_id);
                if ($pagebuilderPage && $pagebuilderPage->data) {
                    return response()->json([
                        'success' => true,
                        'data' => $pagebuilderPage->data
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'data' => null
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get content: ' . $e->getMessage()
            ], 500);
        }
    }
}
