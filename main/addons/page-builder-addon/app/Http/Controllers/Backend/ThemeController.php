<?php

namespace Addons\PageBuilderAddon\App\Http\Controllers\Backend;

use Addons\PageBuilderAddon\App\Services\ThemeIntegrationService;
use Addons\PageBuilderAddon\App\Services\ThemeTemplateService;
use App\Http\Controllers\Controller;
use App\Helpers\Helper\Helper;
use Illuminate\Http\Request;

class ThemeController extends Controller
{
    protected $themeIntegrationService;
    protected $themeTemplateService;

    public function __construct(
        ThemeIntegrationService $themeIntegrationService,
        ThemeTemplateService $themeTemplateService
    ) {
        $this->themeIntegrationService = $themeIntegrationService;
        $this->themeTemplateService = $themeTemplateService;
    }

    /**
     * List themes (wrap ConfigurationController::manageTheme)
     */
    public function index()
    {
        $result = $this->themeIntegrationService->listThemes();
        
        if ($result['type'] === 'error') {
            return redirect()->back()->with('error', $result['message']);
        }

        $data['title'] = 'Theme Builder';
        $data['themes'] = $result['data']['frontend'] ?? [];
        $data['backendThemes'] = $result['data']['backend'] ?? [];

        return view('page-builder-addon::backend.page-builder.themes.index', $data);
    }

    /**
     * Load pagebuilder editor for theme template editing
     */
    public function edit(Request $request)
    {
        $themeName = $request->get('theme', Helper::config()->theme);
        $templatePath = $request->get('template', 'layout/master.blade.php');

        $result = $this->themeTemplateService->loadThemeTemplate($themeName, $templatePath);

        if ($result['type'] === 'error') {
            return redirect()->back()->with('error', $result['message']);
        }

        $data['title'] = 'Edit Theme Template';
        $data['theme'] = $themeName;
        $data['templatePath'] = $templatePath;
        $data['content'] = $result['data']['content'];

        return view('page-builder-addon::backend.page-builder.themes.edit', $data);
    }

    /**
     * Activate theme (wrap ConfigurationController::themeUpdate)
     */
    public function activate(Request $request)
    {
        $result = $this->themeIntegrationService->activateTheme($request->input('name'));

        if ($result['type'] === 'success') {
            return redirect()->back()->with('success', $result['message']);
        }

        return redirect()->back()->with('error', $result['message']);
    }

    /**
     * Upload theme (wrap ConfigurationController::themeUpload)
     */
    public function upload(Request $request)
    {
        $result = $this->themeIntegrationService->uploadTheme($request->file('theme_package'));

        if ($result['type'] === 'success') {
            return redirect()->back()->with('success', $result['message']);
        }

        return redirect()->back()->with('error', $result['message']);
    }

    /**
     * Update theme template using pagebuilder
     */
    public function updateTemplate(Request $request, $themeName)
    {
        // Handle JSON requests (from editor)
        if ($request->wantsJson()) {
            $templatePath = $request->input('template_path');
            $html = $request->input('html');
            $css = $request->input('css');
            
            // Convert pagebuilder HTML/CSS back to Blade format
            $bladeContent = $this->themeTemplateService->convertFromPageBuilder($html);
            
            $result = $this->themeTemplateService->saveThemeTemplate($themeName, $templatePath, $bladeContent);
            
            return response()->json($result);
        }

        // Handle form requests
        $templatePath = $request->input('template_path');
        $content = $request->input('content');

        $result = $this->themeTemplateService->saveThemeTemplate($themeName, $templatePath, $content);

        if ($result['type'] === 'success') {
            return redirect()->back()->with('success', $result['message']);
        }

        return redirect()->back()->with('error', $result['message']);
    }
}
