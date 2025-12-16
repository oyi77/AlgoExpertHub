@extends(\App\Helpers\Helper\Helper::theme().'layout.auth')

@section('title', 'Trading Operations - ' . (Config::config()->appname ?? 'AlgoExpertHub'))
@section('page_title', 'Trading Operations')

@section('content')
<div class="tv-card">
    <div class="tv-card-body">
        @include('frontend.default.user.trading.operations')
    </div>
</div>
@endsection

