@extends(\App\Helpers\Helper\Helper::theme().'layout.auth')

@section('title', 'Transactions - ' . (Config::config()->appname ?? 'AlgoExpertHub'))
@section('page_title', 'Transaction History')

@section('content')
<div class="tv-card">
    <div class="tv-card-body">
        @include('frontend.default.user.transaction')
    </div>
</div>
@endsection

