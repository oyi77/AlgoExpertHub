<?php

namespace Addons\PageBuilderAddon\App\Http\Controllers\Backend;

use Addons\PageBuilderAddon\App\Services\TemplateService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
    protected $templateService;

    public function __construct(TemplateService $templateService)
    {
        $this->templateService = $templateService;
    }

    /**
     * List templates
     */
    public function index()
    {
        $result = $this->templateService->listTemplates();
        
        $data['title'] = 'Page Templates';
        $data['templates'] = $result['data'] ?? collect([]);

        return view('page-builder-addon::backend.page-builder.templates.index', $data);
    }

    /**
     * Create template
     */
    public function create()
    {
        $data['title'] = 'Create Template';

        return view('page-builder-addon::backend.page-builder.templates.create', $data);
    }

    /**
     * Edit template in pagebuilder
     */
    public function edit($id)
    {
        $data['title'] = 'Edit Template';
        $data['templateId'] = $id;

        return view('page-builder-addon::backend.page-builder.templates.edit', $data);
    }

    /**
     * Store template
     */
    public function store(Request $request)
    {
        $result = $this->templateService->createTemplate($request->all());

        if ($result['type'] === 'success') {
            return redirect()->route('admin.page-builder.templates.index')
                ->with('success', $result['message']);
        }

        return redirect()->back()
            ->with('error', $result['message'])
            ->withInput();
    }

    /**
     * Update template
     */
    public function update(Request $request, $id)
    {
        // Handle JSON requests (from editor)
        if ($request->wantsJson()) {
            $data = [
                'content' => [
                    'html' => $request->input('html'),
                    'css' => $request->input('css'),
                    'content' => $request->input('content'),
                ]
            ];
            $result = $this->templateService->updateTemplate($id, $data);
            
            return response()->json($result);
        }

        // Handle form requests
        $result = $this->templateService->updateTemplate($id, $request->all());

        if ($result['type'] === 'success') {
            return redirect()->route('admin.page-builder.templates.index')
                ->with('success', $result['message']);
        }

        return redirect()->back()
            ->with('error', $result['message'])
            ->withInput();
    }

    /**
     * Delete template
     */
    public function destroy($id)
    {
        try {
            $template = \Addons\PageBuilderAddon\App\Models\PageBuilderTemplate::findOrFail($id);
            $template->delete();
            
            return redirect()->back()->with('success', 'Template deleted successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete template: ' . $e->getMessage());
        }
    }

    /**
     * Apply template to page
     */
    public function apply(Request $request, $id)
    {
        $pageId = $request->input('page_id');
        $result = $this->templateService->applyTemplate($id, $pageId);

        if ($result['type'] === 'success') {
            return redirect()->back()->with('success', $result['message']);
        }

        return redirect()->back()->with('error', $result['message']);
    }
}
