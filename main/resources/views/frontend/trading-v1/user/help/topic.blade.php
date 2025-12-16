@extends(\App\Helpers\Helper\Helper::theme().'layout.auth')

@section('title', 'Help Topic - ' . (Config::config()->appname ?? 'AlgoExpertHub'))
@section('page_title', 'Help Topic')

@section('content')
<div class="tv-card">
    <div class="tv-card-body">
        @include('frontend.default.user.help.topic')
    </div>
</div>
@endsection

