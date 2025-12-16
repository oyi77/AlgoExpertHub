@extends(\App\Helpers\Helper\Helper::theme().'layout.auth')

@section('title', 'Deposit History - ' . (Config::config()->appname ?? 'AlgoExpertHub'))
@section('page_title', 'Deposit History')

@section('content')
<div class="tv-card">
    <div class="tv-card-body">
        @include('frontend.default.user.deposit_log')
    </div>
</div>
@endsection

