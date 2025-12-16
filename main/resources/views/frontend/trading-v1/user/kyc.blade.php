@extends(\App\Helpers\Helper\Helper::theme().'layout.auth')

@section('title', 'KYC Verification - ' . (Config::config()->appname ?? 'AlgoExpertHub'))
@section('page_title', 'KYC Verification')

@section('content')
<div class="tv-card">
    <div class="tv-card-body">
        @include('frontend.default.user.kyc')
    </div>
</div>
@endsection

