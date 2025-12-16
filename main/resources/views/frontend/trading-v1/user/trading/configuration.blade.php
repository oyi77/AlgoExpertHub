@extends(\App\Helpers\Helper\Helper::theme().'layout.auth')

@section('title', 'Trading Configuration - ' . (Config::config()->appname ?? 'AlgoExpertHub'))
@section('page_title', 'Trading Configuration')

@section('content')
<div class="tv-card">
    <div class="tv-card-body">
        @include('frontend.default.user.trading.configuration')
    </div>
</div>
@endsection

