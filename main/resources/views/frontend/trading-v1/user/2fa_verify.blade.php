@extends(\App\Helpers\Helper\Helper::theme().'layout.auth')

@section('title', '2FA Verification - ' . (Config::config()->appname ?? 'AlgoExpertHub'))
@section('page_title', '2FA Verification')

@section('content')
<div class="tv-card">
    <div class="tv-card-body">
        @include('frontend.default.user.2fa_verify')
    </div>
</div>
@endsection

