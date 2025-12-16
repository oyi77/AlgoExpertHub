@extends(\App\Helpers\Helper\Helper::theme().'layout.auth')

@section('title', 'Help Center - ' . (Config::config()->appname ?? 'AlgoExpertHub'))
@section('page_title', 'Help Center')

@section('content')
<div class="tv-card">
    <div class="tv-card-body">
        @include('frontend.default.user.help.index')
    </div>
</div>
@endsection

