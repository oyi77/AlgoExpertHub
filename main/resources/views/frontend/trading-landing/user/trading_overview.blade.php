@extends(Config::themeView('layout.auth'))

@section('content')
    <div class="row gy-4">
        <div class="col-12">
            <h4 class="mb-0">{{ __('Trading Overview') }}</h4>
            <p class="text-muted">{{ __('View and manage all your active trading setups') }}</p>
        </div>

        @if(empty($cards))
            <div class="col-12">
                <div class="sp_site_card">
                    <div class="text-center py-5">
                        <i class="las la-chart-line la-3x text-muted mb-3"></i>
                        <h5 class="mb-2">{{ __('No Trading Setups') }}</h5>
                        <p class="text-muted mb-4">{{ __('You don't have any active trading setups yet.') }}</p>
                        <div class="d-flex gap-2 justify-content-center flex-wrap">
                            @if(Route::has('user.execution-connections.create'))
                                <a href="{{ route('user.execution-connections.create') }}" class="btn btn-primary">
                                    <i class="las la-plus me-1"></i> {{ __('Add Trading Connection') }}
                                </a>
                            @endif
                            @if(Route::has('user.external-signals.index'))
                                <a href="{{ route('user.external-signals.index') }}" class="btn btn-outline-primary">
                                    <i class="las la-signal me-1"></i> {{ __('Configure Signal Sources') }}
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="col-12">
                <div class="row g-4">
                    @foreach($cards as $card)
                        <div class="col-xl-4 col-lg-6 col-md-6">
                            <div class="sp_site_card h-100">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h5 class="mb-1">{{ $card['name'] }}</h5>
                                        <span class="badge bg-{{ $card['type'] === 'execution_connection' ? 'info' : 'success' }}">
                                            {{ $card['type_label'] }}
                                        </span>
                                    </div>
                                    <div>
                                        @if($card['status'] === 'running')
                                            <span class="badge bg-success">
                                                <i class="las la-play-circle"></i> {{ __('Running') }}
                                            </span>
                                        @else
                                            <span class="badge bg-warning">
                                                <i class="las la-pause-circle"></i> {{ __('Paused') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">{{ __('Broker/Account') }}:</span>
                                        <strong>{{ $card['broker'] }}</strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">{{ __('Preset') }}:</span>
                                        <strong>{{ $card['preset_name'] }}</strong>
                                    </div>
                                    @if($card['open_positions'] > 0)
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">{{ __('Open Positions') }}:</span>
                                            <strong class="text-info">{{ $card['open_positions'] }}</strong>
                                        </div>
                                    @endif
                                </div>

                                <div class="border-top pt-3 mb-3">
                                    <div class="row g-2 text-center">
                                        <div class="col-6">
                                            <div class="small text-muted">{{ __('P/L Today') }}</div>
                                            <div class="fw-bold {{ $card['pl_today'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                {{ number_format($card['pl_today'], 2) }}
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="small text-muted">{{ __('P/L This Week') }}</div>
                                            <div class="fw-bold {{ $card['pl_week'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                {{ number_format($card['pl_week'], 2) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex gap-2">
                                    <a href="{{ $card['details_route'] }}" class="btn btn-sm btn-outline-primary flex-fill">
                                        <i class="las la-cog me-1"></i> {{ __('Manage') }}
                                    </a>
                                    <form action="{{ $card['toggle_route'] }}" method="POST" class="flex-fill">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-{{ $card['status'] === 'running' ? 'warning' : 'success' }} w-100">
                                            <i class="las la-{{ $card['status'] === 'running' ? 'pause' : 'play' }} me-1"></i>
                                            {{ $card['status'] === 'running' ? __('Stop') : __('Start') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endsection

