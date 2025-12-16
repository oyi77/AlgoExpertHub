@extends(\App\Helpers\Helper\Helper::theme().'layout.auth')

@section('title', 'Profile - ' . (Config::config()->appname ?? 'AlgoExpertHub'))
@section('page_title', 'Profile')

@section('content')
<div class="tv-card">
    <div class="tv-card-body">
        @include('frontend.default.user.profile')
    </div>
</div>
@endsection

