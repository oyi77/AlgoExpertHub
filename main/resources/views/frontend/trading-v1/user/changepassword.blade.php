@extends(\App\Helpers\Helper\Helper::theme().'layout.auth')

@section('title', 'Change Password - ' . (Config::config()->appname ?? 'AlgoExpertHub'))
@section('page_title', 'Change Password')

@section('content')
<div class="tv-card">
    <div class="tv-card-body">
        @include('frontend.default.user.changepassword')
    </div>
</div>
@endsection

