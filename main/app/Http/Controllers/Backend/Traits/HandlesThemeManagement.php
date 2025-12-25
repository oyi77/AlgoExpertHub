<?php

namespace App\Http\Controllers\Backend\Traits;

use App\Models\Configuration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Throwable;

trait HandlesThemeManagement
{
    public function manageTheme()
    {
        $data['title'] = 'Manage Theme';
        $data['themes'] = $this->themeManager->list();
        $data['backendThemes'] = $this->themeManager->listBackend();
        return view('backend.setting.theme')->with($data);
    }

    public function themeUpdate(Request $request, $name = null)
    {
        $general = Configuration::first();

        // Get theme name from route parameter or request
        $themeName = $name ?? $request->input('name') ?? $request->input('theme');
        
        if (!$themeName) {
            return redirect()->back()->with('error', 'Theme name is required.');
        }

        $general->theme = $themeName;
        $general->color = $request->input('color', '#9c0ac');

        $general->save();

        return redirect()->back()->with('success', 'Template Activated successfully');
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
     * Access page builder from Manage Theme (backward compatibility)
     */
    public function themePageBuilder()
    {
        // Redirect to theme builder edit route
        return redirect()->route('admin.page-builder.themes.edit');
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

    /**
     * Delete theme
     */
    public function themeDelete(Request $request, string $themeName)
    {
        try {
            $result = $this->themeManager->delete($themeName);

            return redirect()
                ->route('admin.manage.theme')
                ->with('success', __('Theme :theme deleted successfully.', [
                    'theme' => $result['display_name'] ?? $result['name'],
                ]));
        } catch (Throwable $exception) {
            return redirect()
                ->route('admin.manage.theme')
                ->with('error', $exception->getMessage());
        }
    }

    /**
     * Update backend theme
     */
    public function backendThemeUpdate(Request $request, $name = null)
    {
        $general = Configuration::first();

        // Get theme name from route parameter or request
        $themeName = $name ?? $request->input('name') ?? $request->input('theme');
        
        if (!$themeName) {
            return redirect()->back()->with('error', 'Theme name is required.');
        }

        $general->backend_theme = $themeName;
        $general->save();

        return redirect()->back()->with('success', 'Backend theme activated successfully');
    }

    /**
     * Deactivate all frontend themes
     */
    public function themeDeactivate()
    {
        try {
            $general = Configuration::first();
            
            if (!$general) {
                return redirect()->back()->with('error', 'Configuration not found.');
            }

            $general->theme = null;
            $general->save();

            return redirect()->back()->with('success', 'All frontend themes have been deactivated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to deactivate themes: ' . $e->getMessage());
        }
    }
}

