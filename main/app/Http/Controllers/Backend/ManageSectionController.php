<?php

namespace App\Http\Controllers\Backend;

use App\Helpers\Helper\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\SectionElementRequest;
use App\Http\Requests\SectionElementUpdateRequest;
use App\Http\Requests\SectionRequest;
use App\Models\Content;
use App\Models\GeneralSetting;
use App\Models\GlobalConfiguration;
use App\Models\Language;
use App\Models\SectionData;
use App\Services\SectionManagerService;
use App\Utility\Config;
use App\Utility\ElementBuilder;
use App\Utility\FormBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ManageSectionController extends Controller
{

    protected $content;

    function __construct(SectionManagerService $content)
    {
        $this->content = $content;
    }

    public function section(Request $request)
    {
        // Get sections in saved order or default order
        $data['sections'] = $this->getSectionsInOrder();
        $data['elements'] = Content::where('theme', Helper::config()->theme)->where('type', 'iteratable')->where('name', $request->name)->get();
    
        $elementBuilder = FormBuilder::classMap($request->name);
        if ($elementBuilder->has_element) {

            $data['elementsHeader'] = array_filter(FormBuilder::classMap($request->name)->elementFields[Helper::config()->theme], function ($item) {
                return $item == 'Text' || $item == 'Upload';
            });
        }

        $data['title'] = "Manage {$request->name} Section";

      
        return view('backend.frontend.index')->with($data);
    }

    /**
     * Get sections in saved order or default order
     */
    protected function getSectionsInOrder()
    {
        $defaultSections = Config::sections();
        $savedOrder = GlobalConfiguration::getValue('sections_order', null);
        
        if ($savedOrder && is_array($savedOrder)) {
            // Merge saved order with default sections (in case new sections were added)
            $ordered = [];
            foreach ($savedOrder as $section) {
                if (in_array($section, $defaultSections)) {
                    $ordered[] = $section;
                }
            }
            // Add any new sections that weren't in saved order
            foreach ($defaultSections as $section) {
                if (!in_array($section, $ordered)) {
                    $ordered[] = $section;
                }
            }
            return $ordered;
        }
        
        return $defaultSections;
    }

    /**
     * Reorder sections
     */
    public function reorderSections(Request $request)
    {
        $request->validate([
            'sections' => 'required|array',
            'sections.*' => 'string'
        ]);

        $sections = $request->input('sections');
        
        // Validate all sections exist in default sections
        $defaultSections = Config::sections();
        foreach ($sections as $section) {
            if (!in_array($section, $defaultSections)) {
                return response()->json([
                    'type' => 'error',
                    'message' => "Invalid section: {$section}"
                ], 400);
            }
        }

        GlobalConfiguration::setValue('sections_order', $sections, 'Admin section management order');

        return response()->json([
            'type' => 'success',
            'message' => 'Sections reordered successfully'
        ]);
    }

    public function sectionContentUpdate(SectionRequest $request)
    {

        $data = $request->except('_token');

        $isSuccess = $this->content->contentUpdate($request);

        if ($isSuccess['type'] === 'success') {

            return redirect()->back()->with('success', $isSuccess['message']);
        }
    }

    public function sectionElement(Request $request)
    {
        $data['title'] = ucwords($request->name) . " Element";

        return view('backend.frontend.element')->with($data);
    }


    public function sectionElementCreate(SectionElementRequest $request)
    {
        $data = $request->except('_token');

        $isSuccess = $this->content->elementCreate($request);

        if ($isSuccess['type'] === 'success') {

            return redirect()->back()->with('success', $isSuccess['message']);
        }
    }

    public function editElement($name, Content $element)
    {
        $data['title'] = ucwords(request()->name) . " Element";

        $data['element'] = $element;

        return view('backend.frontend.edit')->with($data);
    }

    public function translate($name, Content $element)
    {
        $data['title'] = ucwords(request()->name) . " Element";

        $data['elementId'] = $element->id;

        $data['childs'] = $element->child;

        $data['languages'] = Language::where('status', 1)->get();

        return view('backend.frontend.trans')->with($data);
    }

    public function updateElement(SectionElementUpdateRequest $request)
    {
        $content = Content::where('theme', Helper::config()->theme)->find($request->element);

        $isSuccess = $this->content->elementUpdate($request, $content);

        if ($isSuccess['type'] === 'error') {
            return redirect()->back()->with('error', $isSuccess['message']);
        }

        return redirect()->back()->with('success', $isSuccess['message']);
    }



    public function deleteElement($name, Content $element)
    {

        $isSuccess = $this->content->deleteElement($element);

        if ($isSuccess['type'] === 'error') {
        }

        return redirect()->back()->with('success', "{$name} Deleted Successfully");
    }

    /**
     * Access page builder from Manage Frontend (backward compatibility)
     */
    public function pageBuilder($name)
    {
        // Redirect to section builder edit route
        return redirect()->route('admin.page-builder.sections.edit', ['name' => $name]);
    }
}
