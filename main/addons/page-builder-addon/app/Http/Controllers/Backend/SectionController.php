<?php

namespace Addons\PageBuilderAddon\App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Helpers\NotificationHelper;
use App\Models\Content;
use App\Utility\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SectionController extends Controller
{
    /**
     * List sections
     */
    public function index(Request $request)
    {
        $theme = $request->get('theme');
        $themeManager = app(\App\Services\ThemeManager::class);
        
        // Get all available themes
        $allThemes = $themeManager->list();
        
        // Get active theme if no theme specified
        if (!$theme) {
            try {
                $config = \App\Helpers\Helper\Helper::config();
                $theme = $config && !empty($config->theme) ? $config->theme : 'default';
            } catch (\Exception $e) {
                $theme = 'default';
            }
        }
        
        $data['title'] = 'Section Builder';
        $data['sections'] = Config::sections();
        $data['themes'] = $allThemes;
        $data['selectedTheme'] = $theme;
        
        // Get sections available for selected theme (check if Content records exist)
        $themeSections = [];
        foreach ($data['sections'] as $sectionName) {
            $hasContent = \App\Models\Content::where('name', $sectionName)
                ->where('theme', $theme)
                ->exists();
            
            $themeSections[] = [
                'name' => $sectionName,
                'has_content' => $hasContent,
            ];
        }
        $data['themeSections'] = $themeSections;

        return view('page-builder-addon::backend.page-builder.sections.index', $data);
    }

    /**
     * Edit section in pagebuilder
     */
    public function edit(Request $request, $name)
    {
        try {
            $data['title'] = 'Edit Section: ' . ucwords(str_replace(['_', '-'], ' ', $name));
            $data['sectionName'] = $name;
            
            // Get theme from request parameter or config
            $theme = $request->get('theme');
            if (!$theme) {
                try {
                    $config = \App\Helpers\Helper\Helper::config();
                    if ($config && property_exists($config, 'theme') && !empty($config->theme)) {
                        $theme = $config->theme;
                    } elseif ($config && isset($config->theme)) {
                        $theme = $config->theme ?: 'default';
                    } else {
                        // Try to get from database directly
                        $dbConfig = \App\Models\Configuration::first();
                        if ($dbConfig && !empty($dbConfig->theme)) {
                            $theme = $dbConfig->theme;
                        } else {
                            $theme = 'default';
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('Could not get theme from config, using default', [
                        'error' => $e->getMessage()
                    ]);
                    $theme = 'default';
                }
            }
            
            $data['theme'] = $theme;
            
            // Load section content (empty collection if none found)
            $data['elements'] = Content::where('theme', $theme)
                ->where('type', 'iteratable')
                ->where('name', $name)
                ->get();

            return view('page-builder-addon::backend.page-builder.sections.edit', $data);
        } catch (\Exception $e) {
            Log::error('Page Builder Section Edit Error', [
                'section' => $name,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // If it's a view error, show more details in development
            if (config('app.debug')) {
                return redirect()->route('admin.page-builder.sections.index')
                    ->with('notify', NotificationHelper::error('Failed to load section editor: ' . $e->getMessage() . ' (File: ' . basename($e->getFile()) . ':' . $e->getLine() . ')', 'Error'));
            }
            
            return redirect()->route('admin.page-builder.sections.index')
                ->with('notify', NotificationHelper::error('Failed to load section editor. Please check the logs for details.', 'Error'));
        }
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
                $theme = $request->input('theme', $request->get('theme', 'default'));

                // Convert pagebuilder content to Content model format
                $sectionContent = [
                    'components' => $content,
                    'css' => $css,
                    'html' => $html
                ];

                $contentModel = Content::where('name', $name)
                    ->where('type', 'iteratable')
                    ->where('theme', $theme)
                    ->first();

                if ($contentModel) {
                    $contentModel->content = $sectionContent;
                    $contentModel->save();
                } else {
                    $defaultLang = \App\Models\Language::where('status', 0)->first();
                    $languageId = $defaultLang ? $defaultLang->id : 0;

                    Content::create([
                        'name' => $name,
                        'type' => 'iteratable',
                        'theme' => $theme,
                        'content' => $sectionContent,
                        'language_id' => $languageId
                    ]);
                }
                
                return response()->json([
                    'success' => true,
                    'message' => 'Section updated successfully'
                ]);
            }

            // Handle form requests
            return redirect()->back()->with('notify', NotificationHelper::success('Section updated successfully', 'Success'));
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update section: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->back()->with('notify', NotificationHelper::error('Failed to update section: ' . $e->getMessage(), 'Error'));
        }
    }
}
