@extends(Config::theme() . 'layout.auth')

@section('content')
    <div class="row gy-4">
        <div class="col-12">
            <div class="sp_site_card">
                <div class="card-header">
                    <h4>{{ __($title) }}</h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h5><i class="fa fa-info-circle"></i> {{ __('Smart Risk Management') }}</h5>
                        <p>{{ __('AI-powered risk management that automatically adjusts position sizes, stop losses, and trading parameters based on signal quality and market conditions.') }}</p>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="sp_site_card text-center">
                                <h6>{{ __('Total Adjustments') }}</h6>
                                <span class="fw-semibold fs-4">0</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="sp_site_card text-center">
                                <h6>{{ __('Avg Performance Score') }}</h6>
                                <span class="fw-semibold fs-4">-</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="sp_site_card text-center">
                                <h6>{{ __('Slippage Reduction') }}</h6>
                                <span class="fw-semibold fs-4">-</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="sp_site_card text-center">
                                <h6>{{ __('Drawdown Reduction') }}</h6>
                                <span class="fw-semibold fs-4">-</span>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <a href="{{ route('user.srm.adjustments.index') }}" class="btn btn-primary">
                                <i class="fa fa-list"></i> {{ __('View Adjustments') }}
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="{{ route('user.srm.insights.index') }}" class="btn btn-info">
                                <i class="fa fa-chart-line"></i> {{ __('View Insights') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
