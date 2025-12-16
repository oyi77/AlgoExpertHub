@extends(\App\Helpers\Helper\Helper::theme().'layout.auth')

@section('title', 'Signal Details - ' . (Config::config()->appname ?? 'AlgoExpertHub'))
@section('page_title', 'Signal Details')

@section('content')
<div class="tv-card">
    <div class="tv-card-body">
        @include('frontend.default.user.signal_details')
    </div>
</div>
@endsection

