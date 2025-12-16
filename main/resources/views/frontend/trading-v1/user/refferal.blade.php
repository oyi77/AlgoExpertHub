@extends(\App\Helpers\Helper\Helper::theme().'layout.auth')

@section('title', 'Referrals - ' . (Config::config()->appname ?? 'AlgoExpertHub'))
@section('page_title', 'Referral Program')

@section('content')
<div class="tv-card">
    <div class="tv-card-body">
        @include('frontend.default.user.refferal')
    </div>
</div>
@endsection

