@extends(\App\Helpers\Helper\Helper::theme().'layout.auth')

@section('title', 'Welcome - ' . (Config::config()->appname ?? 'AlgoExpertHub'))
@section('page_title', 'Welcome')

@section('content')
<div class="tv-card">
    <div class="tv-card-body">
        @include('frontend.default.user.onboarding.welcome')
    </div>
</div>
@endsection

