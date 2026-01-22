<?php

declare(strict_types=1);

namespace Addons\DexAnalyticsAddon\App\Http\Controllers\Backend;

use Addons\DexAnalyticsAddon\App\Http\Requests\StoreWatchlistRequest;
use Addons\DexAnalyticsAddon\App\Http\Requests\UpdateWatchlistRequest;
use Addons\DexAnalyticsAddon\App\Services\DexThemeService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class WatchlistController extends Controller
{
    public function __construct(private readonly DexThemeService $themeService)
    {
    }

    public function index()
    {
        $watchlist = DB::table('dex_trader_watchlist')
            ->orderByDesc('created_at')
            ->paginate(25);

        if ($this->themeService->getActiveTheme() === 'beta-ui') {
            return inertia('Admin/DexAnalytics/Watchlist', [
                'watchlist' => $watchlist,
            ]);
        }

        return view('dex-analytics-addon::backend.watchlist.index', compact('watchlist'));
    }

    public function create()
    {
        if ($this->themeService->getActiveTheme() === 'beta-ui') {
            return inertia('Admin/DexAnalytics/WatchlistCreate');
        }

        return view('dex-analytics-addon::backend.watchlist.create');
    }

    public function store(StoreWatchlistRequest $request): RedirectResponse
    {
        DB::table('dex_trader_watchlist')->insert([
            'wallet_address' => $request->input('wallet_address'),
            'platform' => $request->input('platform'),
            'status' => $request->input('status', 'active'),
            'notes' => $request->input('notes'),
            'assigned_user_id' => $request->input('assigned_user_id'),
            'created_by_admin_id' => $request->user('admin')?->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.dex-analytics.watchlist.index');
    }

    public function edit(int $id)
    {
        $watchlist = DB::table('dex_trader_watchlist')->where('id', $id)->first();

        if ($this->themeService->getActiveTheme() === 'beta-ui') {
            return inertia('Admin/DexAnalytics/WatchlistEdit', [
                'watchlist' => $watchlist,
            ]);
        }

        return view('dex-analytics-addon::backend.watchlist.edit', compact('watchlist'));
    }

    public function update(UpdateWatchlistRequest $request, int $id): RedirectResponse
    {
        DB::table('dex_trader_watchlist')->where('id', $id)->update([
            'wallet_address' => $request->input('wallet_address'),
            'platform' => $request->input('platform'),
            'status' => $request->input('status', 'active'),
            'notes' => $request->input('notes'),
            'assigned_user_id' => $request->input('assigned_user_id'),
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.dex-analytics.watchlist.index');
    }

    public function destroy(int $id): RedirectResponse
    {
        DB::table('dex_trader_watchlist')->where('id', $id)->delete();

        return redirect()->route('admin.dex-analytics.watchlist.index');
    }

    public function import(StoreWatchlistRequest $request): RedirectResponse
    {
        $addresses = preg_split('/\r\n|\r|\n/', (string) $request->input('wallet_address', ''));
        $platform = $request->input('platform');

        foreach (array_filter($addresses) as $address) {
            DB::table('dex_trader_watchlist')->insert([
                'wallet_address' => trim($address),
                'platform' => $platform,
                'status' => 'active',
                'created_by_admin_id' => $request->user('admin')?->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return redirect()->route('admin.dex-analytics.watchlist.index');
    }

    public function export(): BinaryFileResponse
    {
        $path = storage_path('app/dex-watchlist-export.csv');
        $handle = fopen($path, 'w');
        fputcsv($handle, ['wallet_address', 'platform', 'status']);

        DB::table('dex_trader_watchlist')->orderBy('id')->chunk(500, function ($rows) use ($handle): void {
            foreach ($rows as $row) {
                fputcsv($handle, [$row->wallet_address, $row->platform, $row->status]);
            }
        });

        fclose($handle);

        return response()->download($path)->deleteFileAfterSend(true);
    }
}
