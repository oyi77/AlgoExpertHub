@extends(\App\Helpers\Helper\Helper::theme().'layout.auth')

@section('title', 'Signals - ' . (Config::config()->appname ?? 'AlgoExpertHub'))
@section('page_title', 'Trading Signals')

@section('content')
<div class="tv-card">
    <div class="tv-card-body">
        @include('frontend.default.user.signals')
    </div>
</div>
@endsection

