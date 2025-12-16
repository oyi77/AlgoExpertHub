@extends(\App\Helpers\Helper\Helper::theme().'layout.auth')

@section('title', 'Transfer History - ' . (Config::config()->appname ?? 'AlgoExpertHub'))
@section('page_title', 'Transfer History')

@section('content')
<div class="tv-card">
    <div class="tv-card-body">
        @include('frontend.default.user.transfermoney_log')
    </div>
</div>
@endsection

