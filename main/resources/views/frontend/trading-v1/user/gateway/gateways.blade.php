@extends(\App\Helpers\Helper\Helper::theme().'layout.auth')

@section('title', 'Payment Method - ' . (Config::config()->appname ?? 'AlgoExpertHub'))
@section('page_title', 'Select Payment Method')

@section('content')
<div class="tv-card">
    <div class="tv-card-body">
        @include('frontend.default.user.gateway.gateways')
    </div>
</div>
@endsection

