@extends(\App\Helpers\Helper\Helper::theme().'layout.auth')

@section('title', 'Setup Complete - ' . (Config::config()->appname ?? 'AlgoExpertHub'))
@section('page_title', 'Setup Complete')

@section('content')
<div class="tv-card">
    <div class="tv-card-body">
        @include('frontend.default.user.onboarding.complete')
    </div>
</div>
@endsection

