@extends(\App\Helpers\Helper\Helper::theme().'layout.auth')

@section('title', 'Payment - ' . (Config::config()->appname ?? 'AlgoExpertHub'))
@section('page_title', 'Payment Processing')

@section('content')
<div class="tv-card">
    <div class="tv-card-body">
        @include('frontend.default.user.gateway.online')
    </div>
</div>
@endsection

