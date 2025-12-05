<?php
namespace App\Http\Controllers;

use App\Helpers\Helper\Helper;
use App\Models\Configuration;
use App\Models\Content;
use App\Models\Page;
use App\Models\Subscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class FrontendController extends Controller
{

    public function index()
    {
        $data['page'] = Page::where('name', 'home')->first();

        if (!$data['page']) {
            $data['page'] = (object)[
                'name' => 'Home',
                'seo_description' => Configuration::first()->seo_description ?? 'AlgoExpertHub Trading Signal Platform',
                'seo_keywords' => Configuration::first()->seo_tags ?? []
            ];
        }

        $data['title'] = $data['page']->name;

        return view(Helper::theme() . 'home')->with($data);
    }

    public function page(Request $request)
    {
        $data['page'] = Page::where('slug', $request->pages)->first();

        if (!$data['page']) {
            abort(404);
        }

        $data['title'] = "{$data['page']->name}";

        // Check if page has pagebuilder content
        $pageBuilderContent = null;
        if ($data['page']->pagebuilder_page_id) {
            try {
                $pagebuilderPage = \Addons\PageBuilderAddon\App\Models\PageBuilderPage::find($data['page']->pagebuilder_page_id);
                if ($pagebuilderPage && $pagebuilderPage->data) {
                    $pageBuilderContent = $pagebuilderPage->data;
                }
            } catch (\Exception $e) {
                // Pagebuilder page doesn't exist
            }
        }

        $data['pageBuilderContent'] = $pageBuilderContent;

        return view(Helper::theme() . 'pages')->with($data);
    }

    public function changeLanguage(Request $request)
    {
        App::setLocale($request->lang);

        session()->put('locale', $request->lang);

        return redirect()->back()->with('success', __('Successfully Changed Language'));
    }

    public function blogDetails($id)
    {
        $theme = Configuration::first()->theme;

        $data['title'] = "Recent Blog";
        $data['page'] = null;
        $data['blog'] = Content::where('theme', $theme)->where('name', 'blog')->where('id', $id)->first();

        $data['recentblog'] = Content::where('theme', $theme)->where('name', 'blog')->where('type', 'iteratable')->latest()->limit(6)->paginate(Helper::pagination());

        $data['shareComponent'] = \Share::page(
            url()->current(),
            'Share',
        )
            ->facebook()
            ->twitter()
            ->linkedin()
            ->telegram()
            ->whatsapp()
            ->reddit();

        return view(Helper::theme() . 'pages.blog_details')->with($data);
    }

    public function contactSend(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email',
            'subject' => 'required',
            'message' => 'required'
        ]);

        $data = [
            'subject' => $request->subject,
            'message' => $request->message
        ];

        Helper::commonMail($data);

        return back()->with('success', 'Contact With us successfully');
    }

    public function subscribe(Request $request)
    {

        $request->validate([
            'email' => 'required|email|unique:subscribers',
        ]);

        Subscriber::create([
            'email' => $request->email
        ]);

        return response()->json(['success' => true]);
    }

    public function linksDetails($id)
    {
        $details = Content::findOrFail($id);

        $data['title'] = $details->content->page_title;
        $data['page'] = null;
        $data['details'] = $details;

        return view(Helper::theme(). 'link_details')->with($data);
    }
}
