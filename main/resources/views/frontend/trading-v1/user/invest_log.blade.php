@extends(\App\Helpers\Helper\Helper::theme().'layout.auth')

@section('title', 'Investment Log - ' . (Config::config()->appname ?? 'AlgoExpertHub'))
@section('page_title', 'Investment History')

@section('content')
<div class="tv-card">
    <div class="tv-card-body">
        @include('frontend.default.user.invest_log')
    </div>
</div>
@endsection

