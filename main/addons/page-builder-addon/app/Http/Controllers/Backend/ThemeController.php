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
     * Show create theme form
     */
    public function create()
    {
        $result = $this->themeIntegrationService->listThemes();
        
        $data['title'] = 'Create New Theme';
        $data['themes'] = $result['data']['frontend'] ?? [];

        return view('page-builder-addon::backend.page-builder.themes.create', $data);
    }

    /**
     * Store new theme
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'display_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'author' => 'nullable|string|max:255',
            'version' => 'nullable|string|max:50',
            'base_theme' => 'nullable|string', // Clone from existing theme
        ]);

        try {
            $themeManager = app(\App\Services\ThemeManager::class);
            
            // Create theme structure
            $themeName = \Illuminate\Support\Str::slug($validated['name']);
            $themesPath = public_path('asset/frontend');
            $viewsPath = resource_path('views/frontend');
            
            // Create directories
            if (!\Illuminate\Support\Facades\File::isDirectory($themesPath . '/' . $themeName)) {
                \Illuminate\Support\Facades\File::makeDirectory($themesPath . '/' . $themeName, 0755, true);
            }
            
            if (!\Illuminate\Support\Facades\File::isDirectory($viewsPath . '/' . $themeName)) {
                \Illuminate\Support\Facades\File::makeDirectory($viewsPath . '/' . $themeName, 0755, true);
            }

            // Create theme.json
            $themeJson = [
                'name' => $themeName,
                'display_name' => $validated['display_name'] ?? $validated['name'],
                'description' => $validated['description'] ?? '',
                'version' => $validated['version'] ?? '1.0.0',
                'author' => $validated['author'] ?? '',
            ];
            
            \Illuminate\Support\Facades\File::put(
                $themesPath . '/' . $themeName . '/theme.json',
                json_encode($themeJson, JSON_PRETTY_PRINT)
            );

            // Clone from base theme if specified
            if (!empty($validated['base_theme'])) {
                $baseThemePath = $viewsPath . '/' . $validated['base_theme'];
                if (\Illuminate\Support\Facades\File::isDirectory($baseThemePath)) {
                    \Illuminate\Support\Facades\File::copyDirectory(
                        $baseThemePath,
                        $viewsPath . '/' . $themeName
                    );
                }
            } else {
                // Create basic layout structure
                $layoutContent = '@extends("frontend.' . $themeName . '.layout.master")

@section("content")
    <div class="container">
        <h1>Welcome to ' . ($validated['display_name'] ?? $validated['name']) . '</h1>
    </div>
@endsection';
                
                \Illuminate\Support\Facades\File::makeDirectory($viewsPath . '/' . $themeName . '/layout', 0755, true);
                \Illuminate\Support\Facades\File::put(
                    $viewsPath . '/' . $themeName . '/layout/master.blade.php',
                    $layoutContent
                );
            }

            return redirect()->route('admin.page-builder.themes.index')
                ->with('success', 'Theme created successfully');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('ThemeController::store failed', ['error' => $e->getMessage()]);
            
            return redirect()->back()
                ->with('error', 'Failed to create theme: ' . $e->getMessage())
                ->withInput();
        }
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
