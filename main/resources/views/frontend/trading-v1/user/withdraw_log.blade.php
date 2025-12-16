@extends(\App\Helpers\Helper\Helper::theme().'layout.auth')

@section('title', 'Withdrawal History - ' . (Config::config()->appname ?? 'AlgoExpertHub'))
@section('page_title', 'Withdrawal History')

@section('content')
<div class="tv-card">
    <div class="tv-card-body">
        @include('frontend.default.user.withdraw_log')
    </div>
</div>
@endsection

