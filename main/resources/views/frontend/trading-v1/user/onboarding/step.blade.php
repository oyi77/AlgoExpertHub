@extends(\App\Helpers\Helper\Helper::theme().'layout.auth')

@section('title', 'Getting Started - ' . (Config::config()->appname ?? 'AlgoExpertHub'))
@section('page_title', 'Getting Started')

@section('content')
<div class="tv-card">
    <div class="tv-card-body">
        @include('frontend.default.user.onboarding.step')
    </div>
</div>
@endsection

