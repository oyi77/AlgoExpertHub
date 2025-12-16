@extends(\App\Helpers\Helper\Helper::theme().'layout.auth')

@section('title', 'External Signals - ' . (Config::config()->appname ?? 'AlgoExpertHub'))
@section('page_title', 'External Signals')

@section('content')
<div class="tv-card">
    <div class="tv-card-body">
        @include('frontend.default.user.external_signals')
    </div>
</div>
@endsection

