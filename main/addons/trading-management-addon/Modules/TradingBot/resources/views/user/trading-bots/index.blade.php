@extends(Config::theme() . 'layout.auth')

@section('title')
    {{ $title }}
@endsection

@section('content')
<div class="sp_site_card">
    <div class="card-header">
        <div class="d-flex flex-wrap align-items-center justify-content-between">
            <h4>{{ __($title) }}</h4>
            <div>
                @if(Route::has('user.trading-management.trading-bots.marketplace'))
                <a href="{{ route('user.trading-management.trading-bots.marketplace') }}" class="btn btn-info me-2">
                    <i class="fa fa-store"></i> {{ __('Browse Templates') }}
                </a>
                @endif
                @if(Route::has('user.trading-management.trading-bots.create'))
                <a href="{{ route('user.trading-management.trading-bots.create') }}" class="btn btn-primary">
                    <i class="fa fa-plus"></i> {{ __('Create Trading Bot') }}
                </a>
                @endif
            </div>
        </div>
    </div>
    <div class="card-body">
        {{-- Demo Mode Badge --}}
        <div class="alert alert-info mb-4">
            <i class="fa fa-info-circle"></i> <strong>Demo Mode:</strong> All bots run in paper trading mode. No real trades will be executed.
        </div>

        {{-- Statistics Cards --}}
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Bots</h5>
                        <h2>{{ $stats['total'] }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Active</h5>
                        <h2>{{ $stats['active'] }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h5 class="card-title">Paper Trading</h5>
                        <h2>{{ $stats['paper_trading'] }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Profit</h5>
                        <h2>${{ number_format($stats['total_profit'], 2) }}</h2>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <form method="GET" class="mb-3">
            <div class="row">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Search bots..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="is_active" class="form-control">
                        <option value="">All Status</option>
                        <option value="1" {{ request('is_active') == '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ request('is_active') == '0' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    @if(Route::has('user.trading-management.trading-bots.index'))
                    <a href="{{ route('user.trading-management.trading-bots.index') }}" class="btn btn-secondary">Reset</a>
                    @endif
                </div>
            </div>
        </form>

        {{-- Bots List --}}
        @if($bots->count() > 0)
            <div class="row">
                @foreach($bots as $bot)
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">{{ $bot->name }}</h5>
                                <div>
                                    @if($bot->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                    @if($bot->is_paper_trading)
                                        <span class="badge bg-warning">Demo</span>
                                    @endif
                                </div>
                            </div>
                            <div class="card-body">
                                @if($bot->description)
                                    <p class="text-muted">{{ Str::limit($bot->description, 100) }}</p>
                                @endif

                                <div class="mb-3">
                                    <small class="text-muted">Exchange:</small>
                                    <strong>{{ $bot->exchangeConnection->name ?? 'N/A' }}</strong>
                                </div>

                                <div class="mb-3">
                                    <small class="text-muted">Preset:</small>
                                    <strong>{{ $bot->tradingPreset->name ?? 'N/A' }}</strong>
                                </div>

                                @if($bot->filterStrategy)
                                    <div class="mb-3">
                                        <small class="text-muted">Filter:</small>
                                        <strong>{{ $bot->filterStrategy->name }}</strong>
                                    </div>
                                @endif

                                <div class="row text-center mt-3">
                                    <div class="col-4">
                                        <small class="text-muted d-block">Executions</small>
                                        <strong>{{ $bot->total_executions }}</strong>
                                    </div>
                                    <div class="col-4">
                                        <small class="text-muted d-block">Win Rate</small>
                                        <strong>{{ number_format($bot->win_rate, 1) }}%</strong>
                                    </div>
                                    <div class="col-4">
                                        <small class="text-muted d-block">Profit</small>
                                        <strong class="{{ $bot->total_profit >= 0 ? 'text-success' : 'text-danger' }}">
                                            ${{ number_format($bot->total_profit, 2) }}
                                        </strong>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="btn-group w-100" role="group">
                                    @if(Route::has('user.trading-management.trading-bots.show'))
                                    <a href="{{ route('user.trading-management.trading-bots.show', $bot->id) }}" class="btn btn-sm btn-primary">
                                        <i class="fa fa-eye"></i> View
                                    </a>
                                    @endif
                                    @if(Route::has('user.trading-management.trading-bots.edit'))
                                    <a href="{{ route('user.trading-management.trading-bots.edit', $bot->id) }}" class="btn btn-sm btn-secondary">
                                        <i class="fa fa-edit"></i> Edit
                                    </a>
                                    @endif
                                    @if(Route::has('user.trading-management.trading-bots.toggle-active'))
                                    <form action="{{ route('user.trading-management.trading-bots.toggle-active', $bot->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-{{ $bot->is_active ? 'warning' : 'success' }}">
                                            <i class="fa fa-{{ $bot->is_active ? 'pause' : 'play' }}"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="mt-4">
                {{ $bots->links() }}
            </div>
        @else
            <div class="text-center py-5">
                <i class="fa fa-robot fa-3x text-muted mb-3"></i>
                <h5>No Trading Bots Yet</h5>
                <p class="text-muted">Start by browsing prebuilt templates or create your own bot from scratch!</p>
                <div>
                    @if(Route::has('user.trading-management.trading-bots.marketplace'))
                    <a href="{{ route('user.trading-management.trading-bots.marketplace') }}" class="btn btn-info me-2">
                        <i class="fa fa-store"></i> Browse Templates
                    </a>
                    @endif
                    @if(Route::has('user.trading-management.trading-bots.create'))
                    <a href="{{ route('user.trading-management.trading-bots.create') }}" class="btn btn-primary">
                        <i class="fa fa-plus"></i> Create Your Own Bot
                    </a>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
