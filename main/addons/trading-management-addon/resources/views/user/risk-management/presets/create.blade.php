@extends(Config::theme() . 'layout.auth')

@section('content')
    <div class="row gy-4">
        <div class="col-12">
            <div class="sp_site_card">
                <div class="card-header">
                    <div class="d-flex flex-wrap align-items-center justify-content-between">
                        <h4>{{ __($title) }}</h4>
                        <a href="{{ route('user.trading-presets.index') }}" class="btn btn-sm btn-secondary">
                            <i class="fa fa-arrow-left"></i> {{ __('Go Back') }}
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <p>{{ __('Trading preset creation form will be available soon.') }}</p>
                        <p>{{ __('For now, you can browse the marketplace to find presets that suit your trading style.') }}</p>
                    </div>
                    <a href="{{ route('user.trading-presets.marketplace') }}" class="btn btn-primary">
                        <i class="fa fa-store"></i> {{ __('Browse Marketplace') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
