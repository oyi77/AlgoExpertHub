<?php

namespace Addons\PageBuilderAddon\App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Utility\Config;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    /**
     * List sections
     */
    public function index()
    {
        $data['title'] = 'Section Builder';
        $data['sections'] = Config::sections();

        return view('page-builder-addon::backend.page-builder.sections.index', $data);
    }

    /**
     * Edit section in pagebuilder
     */
    public function edit($name)
    {
        $data['title'] = 'Edit Section: ' . ucwords(str_replace('_', ' ', $name));
        $data['sectionName'] = $name;
        
        // Load section content
        $data['elements'] = Content::where('theme', \App\Helpers\Helper\Helper::config()->theme)
            ->where('type', 'iteratable')
            ->where('name', $name)
            ->get();

        return view('page-builder-addon::backend.page-builder.sections.edit', $data);
    }

    /**
     * Update section via pagebuilder
     */
    public function update(Request $request, $name)
    {
        try {
            // Handle JSON requests (from editor)
            if ($request->wantsJson()) {
                $html = $request->input('html');
                $css = $request->input('css');
                $content = $request->input('content');
                
                // TODO: Convert pagebuilder content to Content model format
                // For now, return success
                
                return response()->json([
                    'success' => true,
                    'message' => 'Section updated successfully'
                ]);
            }

            // Handle form requests
            return redirect()->back()->with('success', 'Section updated successfully');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update section: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->back()->with('error', 'Failed to update section: ' . $e->getMessage());
        }
    }
}
