@extends(\App\Helpers\Helper\Helper::theme().'layout.auth')

@section('title', 'Ticket Details - ' . (Config::config()->appname ?? 'AlgoExpertHub'))
@section('page_title', 'Ticket Details')

@section('content')
<div class="tv-card">
    <div class="tv-card-body">
        @include('frontend.default.user.ticket.show')
    </div>
</div>
@endsection

