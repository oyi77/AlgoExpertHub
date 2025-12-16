@extends(\App\Helpers\Helper\Helper::theme().'layout.auth')

@section('title', '2FA Settings - ' . (Config::config()->appname ?? 'AlgoExpertHub'))
@section('page_title', 'Two-Factor Authentication')

@section('content')
<div class="tv-card">
    <div class="tv-card-body">
        @include('frontend.default.user.2fa_settings')
    </div>
</div>
@endsection

