<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\Addons\AddonManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class AddonController extends Controller
{
    public function __construct(
        protected AddonManager $addons
    ) {
    }

    public function index(): View
    {
        return view('backend.addons.index', [
            'title' => __('Manage Addons'),
            'addons' => $this->addons->list(),
        ]);
    }

    public function upload(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'package' => ['required', 'file', 'mimes:zip'],
        ]);

        try {
            $result = $this->addons->upload($request->file('package'));

            return redirect()
                ->route('admin.addons.index')
                ->with('success', __('Addon :addon installed successfully.', [
                    'addon' => $result['title'] ?? $result['slug'],
                ]));
        } catch (Throwable $exception) {
            return redirect()
                ->route('admin.addons.index')
                ->with('error', $exception->getMessage());
        }
    }

    public function updateStatus(Request $request, string $addon): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:enable,disable'],
        ]);

        try {
            $this->addons->setAddonStatus($addon, $validated['action'] === 'enable');

            return redirect()
                ->route('admin.addons.index')
                ->with('success', __('Addon status updated successfully.'));
        } catch (Throwable $exception) {
            return redirect()
                ->route('admin.addons.index')
                ->with('error', $exception->getMessage());
        }
    }

    public function modules(string $addon): View
    {
        $addonData = $this->addons->getAddon($addon);

        if (!$addonData) {
            abort(404, __('Addon not found.'));
        }

        return view('backend.addons.modules', [
            'title' => __('Manage Modules: :addon', ['addon' => $addonData['title']]),
            'addon' => $addonData,
        ]);
    }

    public function updateModule(Request $request, string $addon, string $module): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:enable,disable'],
        ]);

        try {
            $this->addons->setModuleStatus($addon, $module, $validated['action'] === 'enable');

            return redirect()
                ->route('admin.addons.modules', $addon)
                ->with('success', __('Module status updated successfully.'));
        } catch (Throwable $exception) {
            return redirect()
                ->route('admin.addons.modules', $addon)
                ->with('error', $exception->getMessage());
        }
    }
}

