<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $menu = [];

        if ($user) {
            $menuConfig = app(\App\Services\MenuConfigService::class);
            $menuData = $menuConfig->getMenuForUser($user);

            // Resolve routes to URLs for the frontend
            $menu = collect($menuData)->map(function ($group) {
                $group['items'] = collect($group['items'])->map(function ($item) {
                    // Default to FALSE (Legacy Blade) for all routes
                    $item['is_inertia'] = false;

                    if (isset($item['route'])) {
                        // Map route to beta URL
                        $betaUrl = $this->getBetaUrl($item['route']);
                        if ($betaUrl) {
                            $item['url'] = $betaUrl;
                            $item['is_inertia'] = true;
                        } else {
                            try {
                                $item['url'] = route($item['route']);
                            } catch (\Exception $e) {
                                $item['url'] = '#';
                            }
                        }
                    } else {
                        // Items without route (e.g. parents)
                        $item['url'] = '#';
                    }

                    if (isset($item['children'])) {
                        $item['children'] = collect($item['children'])->map(function ($child) {
                            $child['is_inertia'] = false;

                            if (isset($child['route'])) {
                                $betaUrl = $this->getBetaUrl($child['route']);
                                if ($betaUrl) {
                                    $child['url'] = $betaUrl;
                                    $child['is_inertia'] = true;
                                } else {
                                    try {
                                        $child['url'] = route($child['route']);
                                    } catch (\Exception $e) {
                                        $child['url'] = '#';
                                    }
                                }
                            } else {
                                $child['url'] = '#';
                            }
                            return $child;
                        })->toArray();
                    }

                    if (isset($item['type']) && $item['type'] === 'marketplace' && isset($item['categories'])) {
                        $item['category_urls'] = collect($item['categories'])->mapWithKeys(function ($label, $key) use ($item) {
                            try {
                                return [$key => route($item['route'], ['category' => $key])];
                            } catch (\Exception $e) {
                                return [$key => '#'];
                            }
                        })->toArray();
                    }

                    return $item;
                })->toArray();
                return $group;
            })->toArray();
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
            ],
            'menu' => $menu,
            'general' => [
                'name' => config('app.name', 'AlgoExpertHub'),
                'logo' => \App\Helpers\Helper\Helper::getFile('logo', optional(\App\Helpers\Helper\Helper::config())->logo, true),
            ],
            'routes' => [
                'deposit' => route('user.deposit'),
                'dashboard' => route('user.dashboard'),
                'marketData' => route('user.beta.terminal.market-data'),
            ],
        ];
    }

    /**
     * Map a legacy route name to its beta URL.
     *
     * @param string $routeName
     * @return string|null
     */
    protected function getBetaUrl(string $routeName): ?string
    {
        $betaRoutes = [
            'user.dashboard' => route('user.beta.dashboard'),
            'user.profile' => route('user.beta.profile'),
            'user.change.password' => route('user.beta.change.password'),
            'user.plans' => route('user.beta.plans'),
            'user.subscription' => route('user.beta.subscription'),
            'user.deposit' => route('user.beta.deposit'),
            'user.withdraw' => route('user.beta.withdraw'),
            'user.transfer_money' => route('user.beta.transfer_money'),
            'user.transfer_money.log' => route('user.beta.transfer_money.log'),
            'user.receive_money.log' => route('user.beta.receive_money.log'),
            'user.transaction.log' => route('user.beta.transaction.log'),
            'user.interest.log' => route('user.beta.interest.log'),
            'user.deposit.log' => route('user.beta.deposit.log'),
            'user.refferalLog' => route('user.beta.refferalLog'),
            'user.commision' => route('user.beta.commision'),
            'user.subscription.log' => route('user.beta.subscription.log'),
            'user.invest.all' => route('user.beta.invest.all'),
            'user.invest.pending' => route('user.beta.invest.pending'),
            'user.invest.log' => route('user.beta.invest.log'),
            'user.withdraw.all' => route('user.beta.withdraw.history'),
            'user.withdraw.pending' => route('user.beta.withdraw.pending'),
            'user.withdraw.complete' => route('user.beta.withdraw.completed'),
            'user.ticket.index' => route('user.beta.ticket.index'),
            'user.help.index' => route('user.beta.help.index'),
            'user.trading.multi-channel-signal.index' => route('user.beta.trading.multi-channel-signal.index'),
            'user.trading.operations.index' => route('user.beta.trading.operations.index'),
            'user.trading.execution-log.index' => route('user.beta.trading.execution-log.index'),
            'user.trading.configurations.index' => route('user.beta.trading.configurations.index'),
            'user.trading.backtesting.index' => route('user.beta.trading.backtesting.index'),
            'user.trading.marketplaces.index' => route('user.beta.trading.marketplaces.index'),
            'user.signals.index' => route('user.beta.signals.index'),
            'user.terminal.index' => route('user.beta.terminal.index'),
        ];

        return $betaRoutes[$routeName] ?? null;
    }
}
