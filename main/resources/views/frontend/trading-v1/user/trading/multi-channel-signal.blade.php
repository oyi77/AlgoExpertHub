@extends(\App\Helpers\Helper\Helper::theme().'layout.auth')

@section('title', 'Multi-Channel Signals - ' . (Config::config()->appname ?? 'AlgoExpertHub'))
@section('page_title', 'Multi-Channel Signals')

@section('content')
<div class="tv-card">
    <div class="tv-card-body">
        @include('frontend.default.user.trading.multi-channel-signal')
    </div>
</div>
@endsection

