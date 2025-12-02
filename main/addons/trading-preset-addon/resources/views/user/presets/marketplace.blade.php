@extends(Config::theme() . 'layout.auth')

@section('content')
    <div class="row gy-4">
        <div class="col-12">
            <div class="sp_site_card">
                <div class="card-header">
            <div class="d-flex flex-wrap align-items-center justify-content-between">
                <h4>{{ __($title) }}</h4>
                <a href="{{ route('user.trading-presets.index') }}" class="btn btn-sm btn-secondary">
                    <i class="fa fa-arrow-left"></i> {{ __('My Presets') }}
                </a>
                </div>
            </div>
            <div class="card-body">
                        {{-- Search and Filter --}}
                        <form action="{{ route('user.trading-presets.marketplace') }}" method="get" class="mb-4">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="input-group">
                                        <input type="text" 
                                               class="form-control" 
                                               name="search" 
                                               placeholder="{{ __('Search presets...') }}" 
                                               value="{{ request('search') }}">
                                        <div class="input-group-append">
                                            <button class="btn btn-primary" type="submit">
                                                <i class="fa fa-search"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <select name="sort" class="form-control" onchange="this.form.submit()">
                                        <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>{{ __('Newest First') }}</option>
                                        <option value="name" {{ request('sort') == 'name' ? 'selected' : '' }}>{{ __('Name A-Z') }}</option>
                                        <option value="popular" {{ request('sort') == 'popular' ? 'selected' : '' }}>{{ __('Most Popular') }}</option>
                                    </select>
                                </div>
                            </div>
                        </form>

                        {{-- Presets Grid --}}
                        @if($presets->count() > 0)
                            <div class="row">
                                @foreach($presets as $preset)
                                    <div class="col-md-6 col-lg-4 mb-4">
                                        <div class="card h-100 preset-card">
                                            <div class="card-header">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h5 class="mb-1">{{ $preset->name }}</h5>
                                                        @if($preset->is_default_template)
                                                            <span class="badge badge-info badge-sm">{{ __('Default Template') }}</span>
                                                        @else
                                                            <span class="badge badge-primary badge-sm">{{ __('Public') }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <p class="text-muted small mb-2">
                                                    {{ Str::limit($preset->description ?? __('No description'), 100) }}
                                                </p>
                                                
                                                @if($preset->tags)
                                                    <div class="mb-2">
                                                        @foreach(array_slice($preset->tags, 0, 3) as $tag)
                                                            <span class="badge badge-secondary badge-sm">{{ $tag }}</span>
                                                        @endforeach
                                                    </div>
                                                @endif

                                                <div class="preset-metrics mb-3">
                                                    <div class="row text-center">
                                                        <div class="col-4">
                                                            <small class="text-muted d-block">{{ __('Risk') }}</small>
                                                            <strong>
                                                                @if($preset->position_size_mode === 'FIXED')
                                                                    {{ $preset->fixed_lot }}L
                                                                @else
                                                                    {{ $preset->risk_per_trade_pct }}%
                                                                @endif
                                                            </strong>
                                                        </div>
                                                        <div class="col-4">
                                                            <small class="text-muted d-block">{{ __('SL Mode') }}</small>
                                                            <strong>{{ $preset->sl_mode }}</strong>
                                                        </div>
                                                        <div class="col-4">
                                                            <small class="text-muted d-block">{{ __('TP Mode') }}</small>
                                                            <strong>{{ $preset->tp_mode }}</strong>
                                                        </div>
                                                    </div>
                                                </div>

                                                @if($preset->symbol)
                                                    <p class="mb-2">
                                                        <i class="fa fa-chart-line"></i> 
                                                        <strong>{{ __('Symbol:') }}</strong> 
                                                        <span class="badge badge-info">{{ $preset->symbol }}</span>
                                                    </p>
                                                @endif

                                                @if($preset->timeframe)
                                                    <p class="mb-2">
                                                        <i class="fa fa-clock"></i> 
                                                        <strong>{{ __('Timeframe:') }}</strong> {{ $preset->timeframe }}
                                                    </p>
                                                @endif

                                                @if($preset->creator)
                                                    <p class="mb-0 text-muted small">
                                                        <i class="fa fa-user"></i> 
                                                        {{ __('By') }} {{ $preset->creator->username ?? $preset->creator->email }}
                                                    </p>
                                                @endif
                                            </div>
                                            <div class="mt-3">
                                                <form action="{{ route('user.trading-presets.clone', $preset) }}" 
                                                      method="POST" 
                                                      class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-primary btn-sm btn-block">
                                                        <i class="fa fa-copy"></i> {{ __('Clone Preset') }}
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            @if ($presets->hasPages())
                                <div class="mt-4">
                                    {{ $presets->links() }}
                                </div>
                            @endif
                        @else
                            <div class="text-center py-5">
                                <i class="fa fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted">{{ __('No presets found in marketplace.') }}</p>
                                <a href="{{ route('user.trading-presets.create') }}" class="btn btn-primary">
                                    <i class="fa fa-plus"></i> {{ __('Create Your Own Preset') }}
                                </a>
                            </div>
                        @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('style')
    <style>
        .preset-card {
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .preset-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .preset-metrics {
            border-top: 1px solid #eee;
            border-bottom: 1px solid #eee;
            padding: 10px 0;
        }
    </style>
@endpush

