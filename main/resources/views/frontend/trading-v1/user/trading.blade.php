@extends(\App\Helpers\Helper\Helper::theme().'layout.auth')

@section('title', 'Trading Terminal - ' . (Config::config()->appname ?? 'AlgoExpertHub'))
@section('page_title', 'Trading Terminal')

@section('content')
<div class="tv-card">
    <div class="tv-card-body">
        @include('frontend.default.user.trading')
    </div>
</div>
@endsection

