@extends(Config::theme() . 'layout.auth')

@section('title')
    {{ $title }}
@endsection

@section('content')
<div class="sp_site_card">
    <div class="card-header">
        <div class="d-flex flex-wrap align-items-center justify-content-between">
            <h4>{{ $bot->name }}</h4>
            <div>
                <a href="{{ route('user.trading-bots.edit', $bot->id) }}" class="btn btn-sm btn-secondary">
                    <i class="fa fa-edit"></i> Edit
                </a>
                <a href="{{ route('user.trading-bots.index') }}" class="btn btn-sm btn-secondary">
                    <i class="fa fa-arrow-left"></i> Back
                </a>
            </div>
        </div>
    </div>
    <div class="card-body">
        {{-- Status Badges --}}
        <div class="mb-4">
            @if($bot->is_active)
                <span class="badge bg-success fs-6">Active</span>
            @else
                <span class="badge bg-secondary fs-6">Inactive</span>
            @endif
            @if($bot->is_paper_trading)
                <span class="badge bg-warning fs-6">Paper Trading (Demo)</span>
            @endif
        </div>

        @if($bot->description)
            <p class="text-muted mb-4">{{ $bot->description }}</p>
        @endif

        {{-- Bot Configuration --}}
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fa fa-exchange-alt"></i> Exchange Connection</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Name:</strong> {{ $bot->exchangeConnection->name ?? 'N/A' }}</p>
                        <p><strong>Exchange:</strong> {{ $bot->exchangeConnection->exchange_name ?? 'N/A' }}</p>
                        <p><strong>Type:</strong> {{ strtoupper($bot->exchangeConnection->type ?? 'N/A') }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fa fa-shield-alt"></i> Risk Management</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Preset:</strong> {{ $bot->tradingPreset->name ?? 'N/A' }}</p>
                        <p><strong>Risk Mode:</strong> {{ $bot->tradingPreset->position_size_mode ?? 'N/A' }}</p>
                        @if($bot->tradingPreset)
                            <p><strong>Risk Per Trade:</strong> {{ $bot->tradingPreset->risk_per_trade_pct ?? 'N/A' }}%</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            @if($bot->filterStrategy)
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fa fa-chart-line"></i> Technical Filter</h5>
                        </div>
                        <div class="card-body">
                            <p><strong>Strategy:</strong> {{ $bot->filterStrategy->name }}</p>
                            @if($bot->filterStrategy->description)
                                <p class="text-muted">{{ $bot->filterStrategy->description }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            @if($bot->aiModelProfile)
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fa fa-brain"></i> AI Confirmation</h5>
                        </div>
                        <div class="card-body">
                            <p><strong>Profile:</strong> {{ $bot->aiModelProfile->name }}</p>
                            @if($bot->aiModelProfile->description)
                                <p class="text-muted">{{ $bot->aiModelProfile->description }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Statistics --}}
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fa fa-chart-bar"></i> Performance Statistics</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-3">
                        <h3>{{ $bot->total_executions }}</h3>
                        <p class="text-muted">Total Executions</p>
                    </div>
                    <div class="col-md-3">
                        <h3 class="text-success">{{ $bot->successful_executions }}</h3>
                        <p class="text-muted">Successful</p>
                    </div>
                    <div class="col-md-3">
                        <h3 class="text-danger">{{ $bot->failed_executions }}</h3>
                        <p class="text-muted">Failed</p>
                    </div>
                    <div class="col-md-3">
                        <h3 class="{{ $bot->win_rate >= 50 ? 'text-success' : 'text-danger' }}">
                            {{ number_format($bot->win_rate, 1) }}%
                        </h3>
                        <p class="text-muted">Win Rate</p>
                    </div>
                </div>
                <div class="row text-center mt-3">
                    <div class="col-12">
                        <h2 class="{{ $bot->total_profit >= 0 ? 'text-success' : 'text-danger' }}">
                            ${{ number_format($bot->total_profit, 2) }}
                        </h2>
                        <p class="text-muted">Total Profit</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fa fa-cog"></i> Actions</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('user.trading-bots.toggle-active', $bot->id) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-{{ $bot->is_active ? 'warning' : 'success' }}">
                        <i class="fa fa-{{ $bot->is_active ? 'pause' : 'play' }}"></i>
                        {{ $bot->is_active ? 'Deactivate' : 'Activate' }} Bot
                    </button>
                </form>

                <a href="{{ route('user.trading-management.operations.index') }}?bot_id={{ $bot->id }}" class="btn btn-primary">
                    <i class="fa fa-eye"></i> View Executions
                </a>

                <a href="{{ route('user.trading-management.operations.index') }}?tab=positions&bot_id={{ $bot->id }}" class="btn btn-info">
                    <i class="fa fa-chart-line"></i> View Positions
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
