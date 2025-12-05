<?php

namespace Addons\PageBuilderAddon\App\Http\Controllers\Backend;

use Addons\PageBuilderAddon\App\Services\MenuManagerService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    protected $menuManagerService;

    public function __construct(MenuManagerService $menuManagerService)
    {
        $this->menuManagerService = $menuManagerService;
    }

    /**
     * List menus
     */
    public function index()
    {
        $result = $this->menuManagerService->getMenuStructure();
        
        $data['title'] = 'Manage Menus';
        $data['menus'] = $result['data']['menus'] ?? [];
        $data['pages'] = $result['data']['pages'] ?? [];

        return view('page-builder-addon::backend.page-builder.menus.index', $data);
    }

    /**
     * Create menu
     */
    public function create()
    {
        $data['title'] = 'Create Menu';

        return view('page-builder-addon::backend.page-builder.menus.create', $data);
    }

    /**
     * Store menu
     */
    public function store(Request $request)
    {
        $result = $this->menuManagerService->createMenu($request->all());

        if ($result['type'] === 'success') {
            return redirect()->route('admin.page-builder.menus.index')
                ->with('success', $result['message']);
        }

        return redirect()->back()
            ->with('error', $result['message'])
            ->withInput();
    }

    /**
     * Update menu structure (drag-and-drop)
     */
    public function update(Request $request, $id)
    {
        $result = $this->menuManagerService->updateMenu($id, $request->all());

        if ($result['type'] === 'success') {
            return redirect()->route('admin.page-builder.menus.index')
                ->with('success', $result['message']);
        }

        return redirect()->back()
            ->with('error', $result['message']);
    }

    /**
     * Delete menu
     */
    public function destroy($id)
    {
        try {
            $menu = \Addons\PageBuilderAddon\App\Models\PageBuilderMenu::findOrFail($id);
            $menu->delete();
            
            return redirect()->back()->with('success', 'Menu deleted successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete menu: ' . $e->getMessage());
        }
    }

    /**
     * Sync menu from pages
     */
    public function sync()
    {
        $result = $this->menuManagerService->syncMenuWithPages();

        if ($result['type'] === 'success') {
            return response()->json([
                'success' => true,
                'message' => $result['message']
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message']
        ], 500);
    }
}
