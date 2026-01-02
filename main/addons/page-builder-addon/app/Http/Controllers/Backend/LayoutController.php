<?php

namespace Addons\PageBuilderAddon\App\Http\Controllers\Backend;

use Addons\PageBuilderAddon\App\Services\LayoutManagerService;
use App\Http\Controllers\Controller;
use App\Helpers\NotificationHelper;
use Illuminate\Http\Request;

class LayoutController extends Controller
{
    protected $layoutManagerService;

    public function __construct(LayoutManagerService $layoutManagerService)
    {
        $this->layoutManagerService = $layoutManagerService;
    }

    /**
     * List all layouts
     */
    public function index(Request $request)
    {
        try {
            $result = $this->layoutManagerService->listLayouts([
                'type' => $request->get('type'),
                'active' => true,
            ]);

            // Ensure layouts is always a collection
            $layouts = $result['data'] ?? collect();
            if (!($layouts instanceof \Illuminate\Support\Collection)) {
                $layouts = collect($layouts);
            }

            $data['title'] = 'Manage Layouts';
            $data['layouts'] = $layouts;
            $data['type'] = $request->get('type', 'all');

            return view('page-builder-addon::backend.page-builder.layouts.index', $data);
        } catch (\Exception $e) {
            \Log::error('LayoutController::index failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Return view with empty data on error
            $data['title'] = 'Manage Layouts';
            $data['layouts'] = collect();
            $data['type'] = 'all';
            $data['error'] = 'Failed to load layouts: ' . $e->getMessage();

            return view('page-builder-addon::backend.page-builder.layouts.index', $data);
        }
    }

    /**
     * Show create form
     */
    public function create()
    {
        $data['title'] = 'Create Layout';

        return view('page-builder-addon::backend.page-builder.layouts.create', $data);
    }

    /**
     * Store layout
     */
    public function store(Request $request)
    {
        $result = $this->layoutManagerService->createLayout($request->all());

        if ($result['type'] === 'success') {
            return redirect()->route('admin.page-builder.layouts.index')
                ->with('notify', NotificationHelper::success($result['message'], 'Success'));
        }

        return redirect()->back()
            ->with('notify', NotificationHelper::error($result['message'], 'Error'))
            ->withInput();
    }

    /**
     * Show layout details
     */
    public function show($id)
    {
        $layout = \Addons\PageBuilderAddon\App\Models\PageBuilderLayout::findOrFail($id);

        $data['title'] = 'Layout Details';
        $data['layout'] = $layout;

        return view('page-builder-addon::backend.page-builder.layouts.show', $data);
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        $layout = \Addons\PageBuilderAddon\App\Models\PageBuilderLayout::findOrFail($id);

        $data['title'] = 'Edit Layout';
        $data['layout'] = $layout;

        return view('page-builder-addon::backend.page-builder.layouts.edit', $data);
    }

    /**
     * Update layout
     */
    public function update(Request $request, $id)
    {
        $result = $this->layoutManagerService->updateLayout($id, $request->all());

        if ($result['type'] === 'success') {
            return redirect()->route('admin.page-builder.layouts.index')
                ->with('notify', NotificationHelper::success($result['message'], 'Success'));
        }

        return redirect()->back()
            ->with('notify', NotificationHelper::error($result['message'], 'Error'))
            ->withInput();
    }

    /**
     * Delete layout
     */
    public function destroy($id)
    {
        $result = $this->layoutManagerService->deleteLayout($id);

        if ($result['type'] === 'success') {
            return redirect()->back()->with('notify', NotificationHelper::success($result['message'], 'Success'));
        }

        return redirect()->back()->with('notify', NotificationHelper::error($result['message'], 'Error'));
    }
}
