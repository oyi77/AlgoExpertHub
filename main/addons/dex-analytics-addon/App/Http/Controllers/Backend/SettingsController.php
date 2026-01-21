<?php

declare(strict_types=1);

namespace Addons\DexAnalyticsAddon\App\Http\Controllers\Backend;

use Addons\DexAnalyticsAddon\App\Http\Requests\UpdateSettingsRequest;
use App\Http\Controllers\Controller;
use Illuminate\Support\Arr;

class SettingsController extends Controller
{
    public function index()
    {
        $config = config('dex-analytics');

        return view('dex-analytics-addon::backend.settings.index', compact('config'));
    }

    public function update(UpdateSettingsRequest $request)
    {
        $data = $request->validated();
        $config = array_replace_recursive(config('dex-analytics'), $data);

        return redirect()->route('admin.dex-analytics.settings.index')->with('success', 'Settings updated');
    }

    public function testPlatform(UpdateSettingsRequest $request)
    {
        $platform = Arr::get($request->validated(), 'platform');

        return response()->json([
            'success' => (bool) $platform,
            'platform' => $platform,
        ]);
    }
}
