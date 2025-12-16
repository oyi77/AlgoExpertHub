@php
    use App\Helpers\Helper\Helper;
@endphp

@extends(\App\Helpers\Helper\Helper::theme().'layout.master')

@section('title', Config::config()->appname ?? 'AlgoExpertHub - Professional Trading Signals')

@section('content')
    <!-- Hero Section -->
    @include(\App\Helpers\Helper\Helper::theme().'widgets.hero')
    
    <!-- Why Choose Us Section -->
    @include(\App\Helpers\Helper\Helper::theme().'widgets.why-choose-us')
    
    <!-- Market Trends Section -->
    @include(\App\Helpers\Helper\Helper::theme().'widgets.market-trends')
    
    <!-- Account Types / Pricing Section -->
    @include(\App\Helpers\Helper\Helper::theme().'widgets.account-types')
    
    <!-- CTA Education Section -->
    @include(\App\Helpers\Helper\Helper::theme().'widgets.cta-education')
@endsection

