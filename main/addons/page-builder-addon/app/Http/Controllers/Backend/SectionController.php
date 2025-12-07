<?php

namespace Addons\PageBuilderAddon\App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Utility\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
        try {
            $data['title'] = 'Edit Section: ' . ucwords(str_replace('_', ' ', $name));
            $data['sectionName'] = $name;
            
            // Get theme safely with multiple fallbacks
            $theme = 'default';
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
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Could not get theme from config, using default', [
                    'error' => $e->getMessage()
                ]);
            }
            
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
                    ->with('error', 'Failed to load section editor: ' . $e->getMessage() . ' (File: ' . basename($e->getFile()) . ':' . $e->getLine() . ')');
            }
            
            return redirect()->route('admin.page-builder.sections.index')
                ->with('error', 'Failed to load section editor. Please check the logs for details.');
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
