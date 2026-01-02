<?php

namespace Addons\PageBuilderAddon\App\Http\Controllers\Backend;

use Addons\PageBuilderAddon\App\Services\GlobalStylesService;
use App\Http\Controllers\Controller;
use App\Helpers\NotificationHelper;
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
        $data = [
            'title' => 'Global Styles',
            'styles' => collect(),
            'compiledCss' => '',
            'error' => null
        ];

        try {
            // Get all styles (not just active ones) for admin view
            $result = $this->globalStylesService->listStyles([]);

            // Handle service response
            if (isset($result['type']) && $result['type'] === 'error') {
                $data['error'] = $result['message'] ?? 'Failed to load global styles';
                \Log::warning('GlobalStylesController::index service error', ['error' => $data['error']]);
            }

            // Ensure styles is always a collection
            $styles = $result['data'] ?? collect();
            if (!($styles instanceof \Illuminate\Support\Collection)) {
                $styles = collect($styles);
            }
            $data['styles'] = $styles;
            
            // Get compiled CSS (optional, don't fail if this errors)
            try {
                $data['compiledCss'] = $this->globalStylesService->getCompiledCss();
            } catch (\Exception $e) {
                \Log::warning('GlobalStylesController::getCompiledCss failed', ['error' => $e->getMessage()]);
                $data['compiledCss'] = '';
            }

        } catch (\Exception $e) {
            \Log::error('GlobalStylesController::index failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $data['error'] = 'Failed to load global styles: ' . $e->getMessage();
        }

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
                ->with('notify', NotificationHelper::success($result['message'], 'Success'));
        }

        return redirect()->back()
            ->with('notify', NotificationHelper::error($result['message'], 'Error'))
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
                ->with('notify', NotificationHelper::success($result['message'], 'Success'));
        }

        return redirect()->back()
            ->with('notify', NotificationHelper::error($result['message'], 'Error'))
            ->withInput();
    }

    /**
     * Delete global style
     */
    public function destroy($id)
    {
        $result = $this->globalStylesService->deleteStyle($id);

        if ($result['type'] === 'success') {
            return redirect()->back()->with('notify', NotificationHelper::success($result['message'], 'Success'));
        }

        return redirect()->back()->with('notify', NotificationHelper::error($result['message'], 'Error'));
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
