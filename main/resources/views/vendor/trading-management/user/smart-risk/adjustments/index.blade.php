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
                        <p>{{ __('SRM adjustments history will be displayed here.') }}</p>
                        <p>{{ __('This shows all trades that have been adjusted by Smart Risk Management (position size, SL buffer, and other protection logic).') }}</p>
                    </div>

                    <div class="text-center py-5">
                        <p>{{ __('No adjustments found.') }}</p>
                        <p class="text-muted small">{{ __('Adjustments will appear here as SRM makes risk management decisions on your trades.') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
