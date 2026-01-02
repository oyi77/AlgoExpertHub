@extends(Config::theme() . 'layout.auth')

@section('content')
    <div class="row gy-4">
        <div class="col-12">
            <div class="sp_site_card">
                <div class="card-header">
                    <div class="d-flex flex-wrap align-items-center justify-content-between">
                        <h4>{{ __($title) }}</h4>
                        <a href="{{ route('user.srm.dashboard') }}" class="btn btn-sm btn-secondary">
                            <i class="fa fa-arrow-left"></i> {{ __('Back to Dashboard') }}
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <p>{{ __('SRM performance insights and recommendations will be displayed here.') }}</p>
                        <p>{{ __('This section provides recommendations, trends, and risk warnings built from SRM activity in your account.') }}</p>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="border rounded p-3">
                                <h6 class="mb-1">{{ __('Refine Filter Strategies') }}</h6>
                                <p class="mb-2 small text-muted">
                                    {{ __('Adjust your filter rules so SRM works only with signals that best match your trading style.') }}
                                </p>
                                @if(Route::has('user.filter-strategies.index'))
                                    <a href="{{ route('user.filter-strategies.index') }}" class="btn btn-sm btn-outline-primary">
                                        {{ __('Open Filter Strategies') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3">
                                <h6 class="mb-1">{{ __('Calibrate AI Model Profiles') }}</h6>
                                <p class="mb-2 small text-muted">
                                    {{ __('Review AI prompts and analysis modes to improve confirmation quality before execution.') }}
                                </p>
                                @if(Route::has('user.ai-model-profiles.index'))
                                    <a href="{{ route('user.ai-model-profiles.index') }}" class="btn btn-sm btn-outline-primary">
                                        {{ __('Open AI Model Profiles') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="text-center py-5">
                        <p>{{ __('No insights available yet.') }}</p>
                        <p class="text-muted small">{{ __('Insights will appear here as SRM collects data and makes adjustments.') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
