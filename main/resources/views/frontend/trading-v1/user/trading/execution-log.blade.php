@extends(\App\Helpers\Helper\Helper::theme().'layout.auth')

@section('title', 'Execution Log - ' . (Config::config()->appname ?? 'AlgoExpertHub'))
@section('page_title', 'Execution Log')

@section('content')
<div class="tv-card">
    <div class="tv-card-body">
        @include('frontend.default.user.trading.execution-log')
    </div>
</div>
@endsection

