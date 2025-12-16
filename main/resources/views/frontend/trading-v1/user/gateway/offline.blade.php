@extends(\App\Helpers\Helper\Helper::theme().'layout.auth')

@section('title', 'Manual Payment - ' . (Config::config()->appname ?? 'AlgoExpertHub'))
@section('page_title', 'Manual Payment')

@section('content')
<div class="tv-card">
    <div class="tv-card-body">
        @include('frontend.default.user.gateway.offline')
    </div>
</div>
@endsection

