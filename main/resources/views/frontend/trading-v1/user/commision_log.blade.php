@extends(\App\Helpers\Helper\Helper::theme().'layout.auth')

@section('title', 'Commission Log - ' . (Config::config()->appname ?? 'AlgoExpertHub'))
@section('page_title', 'Referral Commissions')

@section('content')
<div class="tv-card">
    <div class="tv-card-body">
        @include('frontend.default.user.commision_log')
    </div>
</div>
@endsection

