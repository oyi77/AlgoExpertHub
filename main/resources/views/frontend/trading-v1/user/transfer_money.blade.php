@extends(\App\Helpers\Helper\Helper::theme().'layout.auth')

@section('title', 'Transfer Money - ' . (Config::config()->appname ?? 'AlgoExpertHub'))
@section('page_title', 'Transfer Money')

@section('content')
<div class="tv-card">
    <div class="tv-card-body">
        @include('frontend.default.user.transfer_money')
    </div>
</div>
@endsection

