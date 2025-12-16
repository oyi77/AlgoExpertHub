@extends(\App\Helpers\Helper\Helper::theme().'layout.auth')

@section('title', 'Marketplace - ' . (Config::config()->appname ?? 'AlgoExpertHub'))
@section('page_title', 'Trading Marketplace')

@section('content')
<div class="tv-card">
    <div class="tv-card-body">
        @include('frontend.default.user.trading.marketplaces')
    </div>
</div>
@endsection

