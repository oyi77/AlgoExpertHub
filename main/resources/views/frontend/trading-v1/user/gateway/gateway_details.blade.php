@extends(\App\Helpers\Helper\Helper::theme().'layout.auth')

@section('title', 'Payment Details - ' . (Config::config()->appname ?? 'AlgoExpertHub'))
@section('page_title', 'Payment Details')

@section('content')
<div class="tv-card">
    <div class="tv-card-body">
        @include('frontend.default.user.gateway.gateway_details')
    </div>
</div>
@endsection

