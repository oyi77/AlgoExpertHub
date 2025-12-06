<?php

namespace Addons\PageBuilderAddon\App\Http\Controllers\Backend;

use Addons\PageBuilderAddon\App\Services\GlobalStylesService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GlobalStylesController extends Controller
{
    protected $globalStylesService;

    public function __construct(GlobalStylesService $globalStylesService)
    {
        $this->globalStylesService = $globalStylesService;
    }

    /**
     * List all global styles
     */
    public function index()
    {
        $result = $this->globalStylesService->listStyles(['active' => true]);

        $data['title'] = 'Global Styles';
        $data['styles'] = $result['data'] ?? [];
        $data['compiledCss'] = $this->globalStylesService->getCompiledCss();

        return view('page-builder-addon::backend.page-builder.global-styles.index', $data);
    }

    /**
     * Show create form
     */
    public function create()
    {
        $data['title'] = 'Create Global Style';

        return view('page-builder-addon::backend.page-builder.global-styles.create', $data);
    }

    /**
     * Store global style
     */
    public function store(Request $request)
    {
        $result = $this->globalStylesService->createStyle($request->all());

        if ($result['type'] === 'success') {
            return redirect()->route('admin.page-builder.global-styles.index')
                ->with('success', $result['message']);
        }

        return redirect()->back()
            ->with('error', $result['message'])
            ->withInput();
    }

    /**
     * Show global style details
     */
    public function show($id)
    {
        $style = \Addons\PageBuilderAddon\App\Models\PageBuilderGlobalStyle::findOrFail($id);

        $data['title'] = 'Global Style Details';
        $data['style'] = $style;

        return view('page-builder-addon::backend.page-builder.global-styles.show', $data);
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        $style = \Addons\PageBuilderAddon\App\Models\PageBuilderGlobalStyle::findOrFail($id);

        $data['title'] = 'Edit Global Style';
        $data['style'] = $style;

        return view('page-builder-addon::backend.page-builder.global-styles.edit', $data);
    }

    /**
     * Update global style
     */
    public function update(Request $request, $id)
    {
        $result = $this->globalStylesService->updateStyle($id, $request->all());

        if ($result['type'] === 'success') {
            return redirect()->route('admin.page-builder.global-styles.index')
                ->with('success', $result['message']);
        }

        return redirect()->back()
            ->with('error', $result['message'])
            ->withInput();
    }

    /**
     * Delete global style
     */
    public function destroy($id)
    {
        $result = $this->globalStylesService->deleteStyle($id);

        if ($result['type'] === 'success') {
            return redirect()->back()->with('success', $result['message']);
        }

        return redirect()->back()->with('error', $result['message']);
    }

    /**
     * Get compiled CSS (API endpoint)
     */
    public function getCompiledCss()
    {
        $css = $this->globalStylesService->getCompiledCss();

        return response($css, 200)
            ->header('Content-Type', 'text/css');
    }
}
