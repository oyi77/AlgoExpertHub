<?php

namespace App\Http\Controllers\Backend;

use App\Helpers\Helper\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\PagesRequest;
use App\Models\Page;
use App\Services\PageService;
use Illuminate\Http\Request;

class PagesController extends Controller
{
    protected $page;

    function __construct(PageService $page)
    {
        $this->page = $page;
    }

    public function index(Request $request)
    {

        $data['title'] = 'Manage Pages';
        $data['pages'] = Page::when($request->search, function ($query) use ($request) {
            $query->where('name', 'LIKE', '%' . $request->search . '%');
        })->orderBy('id', 'ASC')->paginate(Helper::pagination());

        return view('backend.page.index')->with($data);
    }

    public function pageCreate()
    {
        $title = 'Create Page';

        return view('backend.page.create', compact('title'));
    }

    public function pageInsert(PagesRequest $request)
    {

        $isSuccess = $this->page->create($request);

        if ($isSuccess['type'] === 'success') {
            return redirect()->route('admin.frontend.pages')->with('success', $isSuccess['message']);
        }
    }

    public function pageEdit(Request $request)
    {
        $title = "Edit Page";

        $page = Page::findOrFail($request->id);

        return view('backend.page.edit', compact('title', 'page'));
    }

    public function pageUpdate(PagesRequest $request)
    {
        $isSuccess = $this->page->update($request);
        
        if($isSuccess['type'] === 'error'){
            abort($isSuccess['message']);
        }
    
        return back()->with('success', 'Page Updated Successfully');
    }

    public function pageDelete(Request $request, Page $id)
    {
        if ($id->name == 'home') {

            return back()->with('error', 'At least One page is Required');
        }

        $id->delete();

        return back()->with('success', 'Page Deleted Successfully');
    }

    public function pageBuilder(Request $request, $id = null)
    {
        $data['title'] = 'Page Builder';
        
        if ($id) {
            $page = Page::findOrFail($id);
            $data['page'] = $page;
            $data['sections'] = \App\Helpers\Helper\Helper::sectionConfig();
            return view('backend.page.builder')->with($data);
        } else {
            // Show page selector if no page selected
            $data['pages'] = Page::orderBy('order', 'ASC')->get();
            return view('backend.page.builder-select')->with($data);
        }
    }
}
