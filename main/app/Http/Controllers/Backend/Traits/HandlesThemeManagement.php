<?php

namespace App\Http\Controllers\Backend\Traits;

use App\Helpers\NotificationHelper;
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
        $data['landings'] = $this->themeManager->listLandings();
        return view('backend.setting.theme')->with($data);
    }

    public function themeUpdate(Request $request, $name = null)
    {
        $general = Configuration::first();

        // Get theme name from route parameter or request
        $themeName = $name ?? $request->input('name') ?? $request->input('theme');
        
        if (!$themeName) {
            return redirect()->back()->with('notify', NotificationHelper::error('Theme name is required.', 'Error'));
        }

        $general->theme = $themeName;
        $general->color = $request->input('color', '#9c0ac');

        $general->save();

        return redirect()->back()->with('notify', NotificationHelper::success('Template Activated successfully', 'Success'));
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
                ->with('notify', NotificationHelper::success(__('Theme :theme installed successfully.', [
                    'theme' => $result['display_name'] ?? $result['name'],
                ]), 'Theme Installed'));
        } catch (Throwable $exception) {
            return redirect()
                ->route('admin.manage.theme')
                ->with('notify', NotificationHelper::error($exception->getMessage(), 'Error'));
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
                ->with('notify', NotificationHelper::error($exception->getMessage(), 'Error'));
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
                ->with('notify', NotificationHelper::success(__('Theme :theme deleted successfully.', [
                    'theme' => $result['display_name'] ?? $result['name'],
                ]), 'Theme Deleted'));
        } catch (Throwable $exception) {
            return redirect()
                ->route('admin.manage.theme')
                ->with('notify', NotificationHelper::error($exception->getMessage(), 'Error'));
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
            return redirect()->back()->with('notify', NotificationHelper::error('Theme name is required.', 'Error'));
        }

        $general->backend_theme = $themeName;
        $general->save();

        return redirect()->back()->with('notify', NotificationHelper::success('Backend theme activated successfully', 'Success'));
    }

    /**
     * Deactivate all frontend themes
     */
    public function themeDeactivate()
    {
        try {
            $general = Configuration::first();
            
            if (!$general) {
                return redirect()->back()->with('notify', NotificationHelper::error('Configuration not found.', 'Error'));
            }

            $general->theme = null;
            $general->save();

            return redirect()->back()->with('notify', NotificationHelper::success('All frontend themes have been deactivated successfully.', 'Success'));
        } catch (\Exception $e) {
            return redirect()->back()->with('notify', NotificationHelper::error('Failed to deactivate themes: ' . $e->getMessage(), 'Error'));
        }
    }

    /**
     * Update active landing page
     */
    public function landingPageUpdate(Request $request)
    {
        try {
            $general = Configuration::first();
            $landingName = $request->input('landing_page');

            if ($landingName === 'default') {
                $general->landing_page = null;
            } else {
                $general->landing_page = $landingName;
            }

            $general->save();

            return redirect()->back()->with('notify', NotificationHelper::success('Landing Page updated successfully', 'Success'));
        } catch (\Exception $e) {
            return redirect()->back()->with('notify', NotificationHelper::error('Failed to update landing page: ' . $e->getMessage(), 'Error'));
        }
    }
}

