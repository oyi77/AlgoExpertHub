@extends(\App\Helpers\Helper\Helper::theme().'layout.auth')

@section('title', 'Subscriptions - ' . (Config::config()->appname ?? 'AlgoExpertHub'))
@section('page_title', 'Subscription History')

@section('content')
<div class="tv-card">
    <div class="tv-card-body">
        @include('frontend.default.user.subscription_log')
    </div>
</div>
@endsection

