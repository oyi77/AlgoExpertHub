<?php

namespace Addons\PageBuilderAddon\App\Http\Controllers\Backend;

use Addons\PageBuilderAddon\App\Services\WidgetLibraryService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WidgetController extends Controller
{
    protected $widgetLibraryService;

    public function __construct(WidgetLibraryService $widgetLibraryService)
    {
        $this->widgetLibraryService = $widgetLibraryService;
    }

    /**
     * List all widgets
     */
    public function index(Request $request)
    {
        $result = $this->widgetLibraryService->listWidgets([
            'category' => $request->get('category'),
            'active' => true,
        ]);

        $categoriesResult = $this->widgetLibraryService->getCategories();

        $data['title'] = 'Widget Library';
        $data['widgets'] = $result['data'] ?? [];
        $data['categories'] = $categoriesResult['data'] ?? [];
        $data['selectedCategory'] = $request->get('category', 'all');

        return view('page-builder-addon::backend.page-builder.widgets.index', $data);
    }

    /**
     * Show create form
     */
    public function create()
    {
        $data['title'] = 'Create Widget';

        return view('page-builder-addon::backend.page-builder.widgets.create', $data);
    }

    /**
     * Store widget
     */
    public function store(Request $request)
    {
        $result = $this->widgetLibraryService->createWidget($request->all());

        if ($result['type'] === 'success') {
            return redirect()->route('admin.page-builder.widgets.index')
                ->with('success', $result['message']);
        }

        return redirect()->back()
            ->with('error', $result['message'])
            ->withInput();
    }

    /**
     * Show widget details
     */
    public function show($id)
    {
        $widget = \Addons\PageBuilderAddon\App\Models\PageBuilderWidget::findOrFail($id);

        $data['title'] = 'Widget Details';
        $data['widget'] = $widget;

        return view('page-builder-addon::backend.page-builder.widgets.show', $data);
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        $widget = \Addons\PageBuilderAddon\App\Models\PageBuilderWidget::findOrFail($id);

        $data['title'] = 'Edit Widget';
        $data['widget'] = $widget;

        return view('page-builder-addon::backend.page-builder.widgets.edit', $data);
    }

    /**
     * Update widget
     */
    public function update(Request $request, $id)
    {
        $result = $this->widgetLibraryService->updateWidget($id, $request->all());

        if ($result['type'] === 'success') {
            return redirect()->route('admin.page-builder.widgets.index')
                ->with('success', $result['message']);
        }

        return redirect()->back()
            ->with('error', $result['message'])
            ->withInput();
    }

    /**
     * Delete widget
     */
    public function destroy($id)
    {
        $result = $this->widgetLibraryService->deleteWidget($id);

        if ($result['type'] === 'success') {
            return redirect()->back()->with('success', $result['message']);
        }

        return redirect()->back()->with('error', $result['message']);
    }
}
