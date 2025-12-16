@extends(\App\Helpers\Helper\Helper::theme().'layout.auth')

@section('title', 'Backtesting - ' . (Config::config()->appname ?? 'AlgoExpertHub'))
@section('page_title', 'Strategy Backtesting')

@section('content')
<div class="tv-card">
    <div class="tv-card-body">
        @include('frontend.default.user.trading.backtesting')
    </div>
</div>
@endsection

