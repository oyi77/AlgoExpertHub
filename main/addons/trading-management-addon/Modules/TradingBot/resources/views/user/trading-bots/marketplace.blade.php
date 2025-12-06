@extends(Config::theme() . 'layout.auth')

@section('title')
    {{ $title }}
@endsection

@section('content')
<div class="sp_site_card">
    <div class="card-header">
        <div class="d-flex flex-wrap align-items-center justify-content-between">
            <h4>{{ __($title) }}</h4>
            @if(Route::has('user.trading-management.trading-bots.index'))
            <a href="{{ route('user.trading-management.trading-bots.index') }}" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> {{ __('My Bots') }}
            </a>
            @endif
        </div>
    </div>
    <div class="card-body">
        <div class="alert alert-info mb-4">
            <i class="fa fa-info-circle"></i> <strong>Bot Templates:</strong> Browse prebuilt trading bot templates. Clone a template to create your own bot with your exchange connection.
        </div>

        {{-- Filters --}}
        <form method="GET" class="mb-4">
            <div class="row">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Search templates..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="type" class="form-control">
                        <option value="">All Markets</option>
                        <option value="fx" {{ request('type') == 'fx' ? 'selected' : '' }}>Forex</option>
                        <option value="crypto" {{ request('type') == 'crypto' ? 'selected' : '' }}>Crypto</option>
                        <option value="both" {{ request('type') == 'both' ? 'selected' : '' }}>Both</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    @if(Route::has('user.trading-management.trading-bots.marketplace'))
                    <a href="{{ route('user.trading-management.trading-bots.marketplace') }}" class="btn btn-secondary">Reset</a>
                    @endif
                </div>
            </div>
        </form>

        {{-- Templates Grid --}}
        @if($templates->count() > 0)
            <div class="row">
                @foreach($templates as $template)
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">{{ $template->name }}</h5>
                                @if($template->suggested_connection_type)
                                    <small>
                                        <i class="fa fa-exchange-alt"></i> 
                                        {{ ucfirst($template->suggested_connection_type) }}
                                    </small>
                                @endif
                            </div>
                            <div class="card-body">
                                @if($template->description)
                                    <p class="text-muted small">{{ Str::limit($template->description, 120) }}</p>
                                @endif

                                <div class="mb-3">
                                    <small class="text-muted d-block">Risk Preset:</small>
                                    <strong>{{ $template->tradingPreset->name ?? 'N/A' }}</strong>
                                </div>

                                @if($template->filterStrategy)
                                    <div class="mb-3">
                                        <small class="text-muted d-block">Technical Filter:</small>
                                        <strong class="text-success">{{ $template->filterStrategy->name }}</strong>
                                        @if($template->filterStrategy->config['indicators'] ?? null)
                                            <div class="mt-1">
                                                @foreach($template->filterStrategy->config['indicators'] as $indicator => $config)
                                                    <span class="badge bg-info me-1">
                                                        @if($indicator == 'ema_fast' || $indicator == 'ema10') MA10
                                                        @elseif($indicator == 'ema_slow' || $indicator == 'ema100') MA100
                                                        @elseif($indicator == 'psar' || $indicator == 'parabolic_sar') PSAR
                                                        @else {{ ucfirst($indicator) }}
                                                        @endif
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <div class="mb-3">
                                        <small class="text-muted">Filter:</small>
                                        <span class="badge bg-secondary">No Filter (Executes All Signals)</span>
                                    </div>
                                @endif

                                @if($template->tags && count($template->tags) > 0)
                                    <div class="mb-3">
                                        @foreach($template->tags as $tag)
                                            <span class="badge bg-light text-dark me-1">{{ $tag }}</span>
                                        @endforeach
                                    </div>
                                @endif

                                <div class="alert alert-light border small mb-0">
                                    <i class="fa fa-lightbulb text-warning"></i>
                                    <strong>Perfect for:</strong> Investor demonstrations showcasing automated trading with technical indicators (MA100, MA10, PSAR).
                                </div>
                            </div>
                            <div class="card-footer">
                                <a href="{{ route('user.trading-management.trading-bots.clone', $template->id) }}" class="btn btn-primary w-100">
                                    <i class="fa fa-copy"></i> Clone Template
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="mt-4">
                {{ $templates->links() }}
            </div>
        @else
            <div class="text-center py-5">
                <i class="fa fa-search fa-3x text-muted mb-3"></i>
                <h5>No Templates Found</h5>
                <p class="text-muted">Try adjusting your filters or check back later.</p>
            </div>
        @endif
    </div>
</div>
@endsection
