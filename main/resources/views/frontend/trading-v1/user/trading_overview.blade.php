@extends(\App\Helpers\Helper\Helper::theme().'layout.auth')

@section('title', 'Trading Overview - ' . (Config::config()->appname ?? 'AlgoExpertHub'))
@section('page_title', 'Trading Overview')

@section('content')
<div class="tv-card">
    <div class="tv-card-body">
        @include('frontend.default.user.trading_overview')
    </div>
</div>
@endsection

