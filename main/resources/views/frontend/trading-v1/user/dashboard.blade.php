@extends(\App\Helpers\Helper\Helper::theme().'layout.auth')

@section('title', 'Dashboard - ' . (Config::config()->appname ?? 'AlgoExpertHub'))
@section('page_title', 'Dashboard')

@section('content')
    <!-- Stats Grid -->
    <div class="tv-stats-grid">
        <!-- Balance -->
        <div class="tv-stat-card" @if(Route::has('user.deposit')) style="cursor: pointer;" onclick="window.location.href='{{ route('user.deposit') }}'" @endif>
            <div class="tv-stat-label">
                <i class="las la-wallet"></i> Balance
                <span class="badge bg-secondary ms-2" style="font-size: 0.7rem;">Platform</span>
            </div>
            <div class="tv-stat-value">${{ number_format(auth()->user()->balance ?? 0, 2) }}</div>
            <div class="tv-stat-hint">
                <small class="text-muted">Click to deposit funds</small>
            </div>
        </div>
        
        <!-- Active Plan -->
        <div class="tv-stat-card" @if(Route::has('user.plans')) style="cursor: pointer;" onclick="window.location.href='{{ route('user.plans') }}'" @endif>
            <div class="tv-stat-label">
                <i class="las la-crown"></i> Active Plan
            </div>
            <div class="tv-stat-value">
                @php
                    $currentPlanSubscription = auth()->user()->currentplan()->first();
                    $currentPlan = $currentPlanSubscription ? $currentPlanSubscription->plan : null;
                    $subscription = $currentPlanSubscription;
                @endphp
                @if($currentPlan)
                    {{ $currentPlan->name ?? 'N/A' }}
                    @if($subscription && $subscription->end_date)
                        @php
                            $daysLeft = now()->diffInDays($subscription->end_date, false);
                        @endphp
                        @if($daysLeft > 0)
                            <div class="tv-stat-hint">
                                <small class="text-{{ $daysLeft <= 7 ? 'danger' : ($daysLeft <= 30 ? 'warning' : 'muted') }}">
                                    {{ $daysLeft }} {{ __('days left') }}
                                </small>
                            </div>
                        @else
                            <div class="tv-stat-hint">
                                <small class="text-danger">{{ __('Expired') }}</small>
                            </div>
                        @endif
                    @endif
                @else
                    No Plan
                    <div class="tv-stat-hint">
                        <small class="text-muted" style="font-size: 0.75rem;">Subscribe to access signals</small>
                    </div>
                @endif
            </div>
        </div>
        
        <!-- Total Signals -->
        <div class="tv-stat-card" style="cursor: pointer;" onclick="window.location.href='{{ route('user.trading.multi-channel-signal.index') ?? route('user.signal.all') }}'">
            <div class="tv-stat-label">
                <i class="las la-bullhorn"></i> Signals This Month
            </div>
            <div class="tv-stat-value">
                @php
                    $currentPlanSubscription = auth()->user()->currentplan()->first();
                    $currentPlan = $currentPlanSubscription ? $currentPlanSubscription->plan : null;
                    
                    $signalsCount = $currentPlan 
                        ? $currentPlan->signals()->where('is_published', 1)
                            ->whereMonth('published_date', now()->month)
                            ->whereYear('published_date', now()->year)
                            ->count()
                        : 0;
                    $lastMonthCount = $currentPlan
                        ? $currentPlan->signals()->where('is_published', 1)
                            ->whereMonth('published_date', now()->subMonth()->month)
                            ->whereYear('published_date', now()->subMonth()->year)
                            ->count()
                        : 0;
                    $trend = $signalsCount - $lastMonthCount;
                @endphp
                {{ $signalsCount }}
                @if($trend != 0)
                    <div class="tv-stat-hint">
                        <small class="text-{{ $trend > 0 ? 'success' : 'danger' }}">
                            <i class="las la-arrow-{{ $trend > 0 ? 'up' : 'down' }}"></i>
                            {{ abs($trend) }} {{ __('from last month') }}
                        </small>
                    </div>
                @endif
            </div>
        </div>
        
        <!-- Referrals -->
        <div class="tv-stat-card" @if(Route::has('user.referral.index')) style="cursor: pointer;" onclick="window.location.href='{{ route('user.referral.index') }}'" @endif>
            <div class="tv-stat-label">
                <i class="las la-users"></i> Referrals
            </div>
            <div class="tv-stat-value">{{ auth()->user()->refferals()->count() ?? 0 }}</div>
            <div class="tv-stat-hint">
                <small class="text-muted">Earn rewards for referrals</small>
            </div>
        </div>
    </div>
    
    <!-- Recent Signals -->
    @if(Route::has('user.trading.multi-channel-signal.index') || Route::has('user.signal.all'))
        <div class="tv-card">
            <div class="tv-card-header">
                <h3 class="tv-card-title">Recent Signals</h3>
                @if(Route::has('user.trading.multi-channel-signal.index'))
                    <a href="{{ route('user.trading.multi-channel-signal.index') }}" class="tv-btn tv-btn-outline">View All</a>
                @elseif(Route::has('user.signal.all'))
                    <a href="{{ route('user.signal.all') }}" class="tv-btn tv-btn-outline">View All</a>
                @endif
            </div>
            <div class="tv-card-body">
                @php
                    $currentPlanSubscription = auth()->user()->currentplan()->first();
                    $currentPlan = $currentPlanSubscription ? $currentPlanSubscription->plan : null;
                    $recentSignals = $currentPlan 
                        ? $currentPlan->signals()->where('is_published', 1)->latest()->take(5)->get()
                        : collect();
                @endphp
                
                @if($recentSignals->count() > 0)
                    <div class="table-responsive">
                        <table class="tv-table">
                            <thead>
                                <tr>
                                    <th>Pair</th>
                                    <th>Direction</th>
                                    <th>Entry</th>
                                    <th>SL</th>
                                    <th>TP</th>
                                    <th>R/R</th>
                                    <th>Status</th>
                                    <th>Source</th>
                                    <th>Date</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentSignals as $signal)
                                    @php
                                        // Get pair and market info for formatting
                                        $pairName = $signal->pair->name ?? 'N/A';
                                        $marketName = $signal->market->name ?? '';
                                        
                                        // Calculate R/R Ratio
                                        $rrRatio = null;
                                        if ($signal->open_price && $signal->sl && $signal->tp) {
                                            $entry = (float) $signal->open_price;
                                            $sl = (float) $signal->sl;
                                            $tp = (float) $signal->tp;
                                            if ($signal->direction == 'buy' || $signal->direction == 'long') {
                                                $risk = $entry - $sl;
                                                $reward = $tp - $entry;
                                            } else {
                                                $risk = $sl - $entry;
                                                $reward = $entry - $tp;
                                            }
                                            if ($risk > 0) {
                                                $rrRatio = round($reward / $risk, 2);
                                            }
                                        }
                                        
                                        // Determine signal status
                                        $status = $signal->outcome ?? 'active';
                                        $statusHtml = \App\Helpers\Helper\Helper::formatSignalOutcome($status);
                                        
                                        if ($status == 'active' && $signal->published_date && $signal->published_date->lt(now()->subDays(7))) {
                                            $status = 'expired';
                                            $statusHtml = \App\Helpers\Helper\Helper::formatSignalOutcome($status);
                                        }
                                        
                                        // Get source
                                        $source = 'Manual';
                                        $sourceIcon = 'user';
                                        if ($signal->auto_created && $signal->channelSource) {
                                            $source = $signal->channelSource->name ?? 'Channel';
                                            $sourceIcon = 'paper-plane';
                                        }
                                    @endphp
                                    <tr style="cursor: pointer;" onclick="window.location.href='{{ route('user.signal.details', ['id' => $signal->id, 'slug' => Str::slug($signal->title)]) }}'">
                                        <td><strong>{{ $pairName }}</strong></td>
                                        <td>
                                            <span class="tv-badge tv-badge-{{ in_array(strtolower($signal->direction), ['buy', 'long']) ? 'success' : 'danger' }}">
                                                <i class="las la-arrow-{{ in_array(strtolower($signal->direction), ['buy', 'long']) ? 'up' : 'down' }}"></i>
                                                {{ strtoupper($signal->direction) }}
                                            </span>
                                        </td>
                                        <td>{{ \App\Helpers\Helper\Helper::formatSignalPrice($signal->open_price, $pairName, $marketName) }}</td>
                                        <td>{{ \App\Helpers\Helper\Helper::formatSignalPrice($signal->sl, $pairName, $marketName) }}</td>
                                        <td>{{ \App\Helpers\Helper\Helper::formatSignalPrice($signal->tp, $pairName, $marketName) }}</td>
                                        <td>
                                            @if($rrRatio !== null)
                                                <span class="badge bg-{{ $rrRatio >= 2 ? 'success' : ($rrRatio >= 1 ? 'warning' : 'danger') }}">
                                                    {{ number_format($rrRatio, 2, '.', '') }}:1
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            {!! $statusHtml !!}
                                        </td>
                                        <td>
                                            <small>
                                                <i class="las la-{{ $sourceIcon }}"></i> {{ $source }}
                                            </small>
                                        </td>
                                        <td>
                                            @if($signal->published_date)
                                                <div>{{ $signal->published_date->format('M d, Y') }}</div>
                                                <small class="text-muted">{{ $signal->published_date->format('H:i') }}</small>
                                                <div><small class="text-muted" title="{{ $signal->published_date }}">{{ $signal->published_date->diffForHumans() }}</small></div>
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td class="text-end" onclick="event.stopPropagation();">
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('user.signal.details', ['id' => $signal->id, 'slug' => Str::slug($signal->title)]) }}" 
                                                   class="btn btn-outline-info btn-sm" 
                                                   title="{{ __('View Details') }}">
                                                    <i class="las la-eye"></i>
                                                </a>
                                                @if(Route::has('user.trading.terminal.index'))
                                                    <a href="{{ route('user.trading.terminal.index', ['pair' => $pairName, 'direction' => $signal->direction, 'entry' => $signal->open_price, 'sl' => $signal->sl, 'tp' => $signal->tp]) }}" 
                                                       class="btn btn-outline-primary btn-sm" 
                                                       title="{{ __('Execute Trade') }}">
                                                        <i class="las la-chart-line"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="las la-bullhorn la-3x text-muted mb-3"></i>
                        <p class="text-muted mb-3">No signals available yet.</p>
                        <a href="{{ route('user.plans') }}" class="btn btn-primary">
                            <i class="las la-crown"></i> {{ __('Subscribe to a Plan') }}
                        </a>
                        @if(Route::has('user.trading.multi-channel-signal.index'))
                            <a href="{{ route('user.trading.multi-channel-signal.index') }}" class="btn btn-outline-primary ms-2">
                                <i class="las la-plus-circle"></i> {{ __('Add Signal Source') }}
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    @endif
@endsection

