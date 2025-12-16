@extends(\App\Helpers\Helper\Helper::theme().'layout.auth')

@section('title', 'Plans - ' . (Config::config()->appname ?? 'AlgoExpertHub'))
@section('page_title', 'Subscription Plans')

@section('content')
@include('frontend.default.user.plans')
@endsection

