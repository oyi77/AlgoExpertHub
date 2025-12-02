<?php

namespace App\Http\Controllers\Backend;

use App\Helpers\Helper\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\ConfigurationRequest;
use App\Models\Configuration;
use App\Services\ConfigurationService;
use App\Services\ThemeManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Response;
use Throwable;

class ConfigurationController extends Controller
{
    protected $config;
    protected $themeManager;

    public function __construct(ConfigurationService $config, ThemeManager $themeManager)
    {
        $this->config = $config;
        $this->themeManager = $themeManager;
    }

    public function index()
    {
        $data['title'] = 'Application Settings';

        $data['general'] = Configuration::first();

        $data['timezone'] = json_decode(file_get_contents(resource_path('views/backend/setting/timezone.json')));
        return view('backend.setting.index')->with($data);
    }

    public function ConfigurationUpdate(ConfigurationRequest $request)
    {

        $isSuccess = $this->config->general($request);

        if ($isSuccess['type'] == 'success')
            return back()->with('success', $isSuccess['message']);
    }


    public function cacheClear()
    {

        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('optimize:clear');

        return back()->with('success', 'Caches cleared successfully!');
    }

    public function manageTheme()
    {
        $data['title'] = 'Manage Theme';
        $data['themes'] = $this->themeManager->list();
        return view('backend.setting.theme')->with($data);
    }

    public function themeUpdate(Request $request)
    {

        $general = Configuration::first();

        $general->theme =$request->name;
        $general->color = $request->color;

        $general->save();

        return redirect()->back()->with('success', 'Template Actived successfully');
    }

    public function themeColor(Request $request)
    {
        
        $general = Configuration::first();

        $general->theme = $request->theme;
        $general->color = $request->color;

        $general->save();


        return response()->json(['success' => true]);
    }

    /**
     * Upload theme ZIP file
     */
    public function themeUpload(Request $request)
    {
        $validated = $request->validate([
            'theme_package' => ['required', 'file', 'mimes:zip', 'max:10240'], // Max 10MB
        ]);

        try {
            $result = $this->themeManager->upload($request->file('theme_package'));

            return redirect()
                ->route('admin.manage.theme')
                ->with('success', __('Theme :theme installed successfully.', [
                    'theme' => $result['display_name'] ?? $result['name'],
                ]));
        } catch (Throwable $exception) {
            return redirect()
                ->route('admin.manage.theme')
                ->with('error', $exception->getMessage());
        }
    }

    /**
     * Download theme template
     */
    public function themeDownloadTemplate()
    {
        try {
            $zipPath = $this->themeManager->downloadTemplate();
            $filename = 'theme-template-' . date('Y-m-d') . '.zip';

            return Response::download($zipPath, $filename, [
                'Content-Type' => 'application/zip',
            ])->deleteFileAfterSend(true);
        } catch (Throwable $exception) {
            return redirect()
                ->route('admin.manage.theme')
                ->with('error', $exception->getMessage());
        }
    }
}
