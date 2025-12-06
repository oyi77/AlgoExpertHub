<?php

namespace Addons\PageBuilderAddon\App\Http\Controllers\Backend;

use Addons\PageBuilderAddon\App\Services\PageBuilderService;
use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;

class PageBuilderController extends Controller
{
    protected $pageBuilderService;

    public function __construct(PageBuilderService $pageBuilderService)
    {
        $this->pageBuilderService = $pageBuilderService;
    }

    /**
     * List all pages with pagebuilder integration
     */
    public function index(Request $request)
    {
        $data['title'] = 'Page Builder - All Pages';
        $data['pages'] = Page::when($request->search, function ($query) use ($request) {
            $query->where('name', 'LIKE', '%' . $request->search . '%');
        })->orderBy('order')->paginate(20);

        return view('page-builder-addon::backend.page-builder.index', $data);
    }

    /**
     * Show create form
     */
    public function create()
    {
        $data['title'] = 'Create Page';

        return view('page-builder-addon::backend.page-builder.create', $data);
    }

    /**
     * Load pagebuilder editor
     */
    public function edit($id)
    {
        $data['title'] = 'Edit Page in Page Builder';
        $data['page'] = Page::findOrFail($id);
        $data['pagebuilderPage'] = null;

        // Load pagebuilder page if exists
        if ($data['page']->pagebuilder_page_id && \Addons\PageBuilderAddon\App\Models\PageBuilderPage::tableExists()) {
            try {
                $data['pagebuilderPage'] = \Addons\PageBuilderAddon\App\Models\PageBuilderPage::find($data['page']->pagebuilder_page_id);
            } catch (\Exception $e) {
                // Pagebuilder page doesn't exist yet
            }
        }

        return view('page-builder-addon::backend.page-builder.edit', $data);
    }

    /**
     * Load pagebuilder editor from existing Page model (backward compatibility)
     */
    public function editFromPage($pageId)
    {
        $page = Page::findOrFail($pageId);
        
        // Redirect to edit route
        return redirect()->route('admin.page-builder.edit', $page->id);
    }

    /**
     * Store page
     */
    public function store(Request $request)
    {
        $result = $this->pageBuilderService->createPage($request->all());

        if ($result['type'] === 'success') {
            return redirect()->route('admin.page-builder.index')
                ->with('success', $result['message']);
        }

        return redirect()->back()
            ->with('error', $result['message'])
            ->withInput();
    }

    /**
     * Update page
     */
    public function update(Request $request, $id)
    {
        $result = $this->pageBuilderService->updatePage($id, $request->all());

        if ($result['type'] === 'success') {
            return redirect()->route('admin.page-builder.index')
                ->with('success', $result['message']);
        }

        return redirect()->back()
            ->with('error', $result['message'])
            ->withInput();
    }

    /**
     * Delete page
     */
    public function destroy($id)
    {
        $result = $this->pageBuilderService->deletePage($id);

        if ($result['type'] === 'success') {
            return redirect()->route('admin.page-builder.index')
                ->with('success', $result['message']);
        }

        return redirect()->back()
            ->with('error', $result['message']);
    }
}
