@extends(\App\Helpers\Helper\Helper::theme().'layout.auth')

@section('title', 'Trading Configurations - ' . (Config::config()->appname ?? 'AlgoExpertHub'))
@section('page_title', 'Trading Configurations')

@section('content')
<div class="tv-card">
    <div class="tv-card-body">
        @include('frontend.default.user.trading.configurations')
    </div>
</div>
@endsection

