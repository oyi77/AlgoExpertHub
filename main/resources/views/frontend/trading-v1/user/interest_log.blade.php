@extends(\App\Helpers\Helper\Helper::theme().'layout.auth')

@section('title', 'Interest Log - ' . (Config::config()->appname ?? 'AlgoExpertHub'))
@section('page_title', 'Interest History')

@section('content')
<div class="tv-card">
    <div class="tv-card-body">
        @include('frontend.default.user.interest_log')
    </div>
</div>
@endsection

