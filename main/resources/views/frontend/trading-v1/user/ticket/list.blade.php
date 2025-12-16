@extends(\App\Helpers\Helper\Helper::theme().'layout.auth')

@section('title', 'Support Tickets - ' . (Config::config()->appname ?? 'AlgoExpertHub'))
@section('page_title', 'Support Tickets')

@section('content')
<div class="tv-card">
    <div class="tv-card-body">
        @include('frontend.default.user.ticket.list')
    </div>
</div>
@endsection

