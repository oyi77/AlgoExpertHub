@extends('backend.layout.master')

@push('style')
    <style>
        /* Hide default breadcrumb on this page since it's in the header card */
        .page-top {
            display: none;
        }
    </style>
@endpush

@section('element')
    <div class="content-main pt-0">
        <div class="row">
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">{{ $title ?? 'Payment Gateways' }}</h4>
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{route('admin.home')}}">{{ ucfirst(strtolower(__('Home'))) }}</a></li>
                            <li class="breadcrumb-item active"><a href="{{url()->current()}}">{{ __($title ?? 'Payment Gateways') }}</a></li>
                        </ol>
                    </div>
                </div>
                
                @if(isset($gateways) && $gateways->count() > 0)
                    {{-- Fallback: Use legacy rendering if gateways are passed from controller --}}
                    <div class="row">
                        @foreach ($gateways as $gateway)
                            <div class="col-xxl-3 col-lg-4 col-md-6 mb-4">
                                <article class="card payment-card mb-0 h-100">
                                    <div class="card-header align-items-center">
                                        <div class="d-flex align-items-center">
                                            <span class="me-2"><img src="{{ Config::getFile('gateways', $gateway->image, true) }}" alt="{{ $gateway->name }}" style="max-height: 40px;" /></span>
                                            <h5 class="text-dark fw-600 mb-0">
                                                {{ Str::ucfirst(str_replace('_', ' ', $gateway->name)) }}
                                            </h5>
                                        </div>
                                        <div class="toggle-wrapper" style="width: auto;">
                                            <livewire:shared.toggle-switch 
                                                :model="$gateway" 
                                                field="status" 
                                                :key="'toggle-'.$gateway->id"
                                            />
                                        </div>
                                    </div>
                                    <div class="card-body pb-0 d-flex flex-wrap justify-content-between w-50-percent">
                                        <p><span class="fw-600">{{ __('Currency :') }}</span>
                                            <span>
                                                @php
                                                    $currency = null;
                                                    if ($gateway->parameter) {
                                                        if (is_object($gateway->parameter)) {
                                                            $currency = $gateway->parameter->gateway_currency ?? null;
                                                        } elseif (is_array($gateway->parameter)) {
                                                            $currency = $gateway->parameter['gateway_currency'] ?? null;
                                                        } elseif (is_string($gateway->parameter)) {
                                                            $decoded = json_decode($gateway->parameter, true);
                                                            $currency = $decoded['gateway_currency'] ?? null;
                                                        }
                                                    }
                                                    echo $currency ? strtoupper($currency) : ($gateway->currency ?? 'USD');
                                                @endphp
                                            </span>
                                        </p>
                                        <p><span class="fw-600">{{ __('Rate :') }}</span> <span>{{ Config::formatter($gateway->rate) }}</span></p>
                                        <p><span class="fw-600">{{ __('Charge :') }}</span> <span>{{ Config::formatter($gateway->charge) }}</span></p>
                                        <p><span class="fw-600">{{ __('Type :') }}</span> <span>
                                                @if ($gateway->type)
                                                    {{ __('Online') }}
                                                @else
                                                    {{ __('Offline') }}
                                                @endif
                                            </span></p>
                                    </div>
                                    <div class="card-footer justify-content-end align-items-center">
                                        @if ($gateway->type)
                                            <a href="{{ route('admin.payment.view', $gateway->name) }}"
                                                class="d-flex align-items-center"><i class="las la-eye fs-25 mr-2"></i>
                                                {{ __('View integration') }}</a>
                                        @else
                                            <a href="{{ route('admin.payment.offline.edit', $gateway->id) }}"
                                                class="d-flex align-items-center"><i class="las la-eye fs-25 mr-2"></i>
                                                {{ __('View integration') }}</a>
                                        @endif
                                    </div>
                                </article>
                            </div>
                        @endforeach
                    </div>
                @else
                    {{-- Try Livewire component --}}
                    <livewire:admin.gateways.gateway-manager />
                    
                    {{-- Fallback message if both fail --}}
                    <div id="gateway-fallback" style="display: none;">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No payment gateways found. Please configure payment gateways in the system.
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('style')
    <style>
        /* Hide default breadcrumb on this page since it's in the header card */
        .page-top {
            display: none;
        }
        
        .content-main {
            --c-text-primary: #282a32;
            --c-text-secondary: #686b87;
            --c-text-action: #404089;
            --c-accent-primary: #434ce8;
            --c-border-primary: #eff1f6;
            --c-background-primary: #ffffff;
            --c-background-secondary: #fdfcff;
            --c-background-tertiary: #ecf3fe;
            --c-background-quaternary: #e9ecf4;
        }

        .content-main {
            padding-top: 2rem;
            padding-bottom: 6rem;
            flex-grow: 1;
        }

        .card-grid {
            display: grid;
            grid-template-columns: repeat(1, 1fr);
            -moz-column-gap: 1.5rem;
            column-gap: 1.5rem;
            row-gap: 1rem;
        }

        @media (min-width: 600px) {
            .card-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (min-width: 1200px) {
            .card-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        .card {
            background-color: var(--c-background-primary);
            box-shadow: 0 3px 3px 0 rgba(0, 0, 0, 0.05), 0 5px 15px 0 rgba(0, 0, 0, 0.05);
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            margin-bottom: .5rem;
        }

        .card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
        }

        .card-header div {
            display: flex;
            align-items: center;
        }

        .card-header div span {
            height: 40px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .card-header div span img {
            max-height: 100%;
        }

        .card-header div h4 {
            margin-left: 0.75rem;
            font-weight: 500;
        }

        .toggle span {
            display: block;
            width: 40px;
            height: 24px;
            border-radius: 99em;
            background-color: var(--c-background-quaternary);
            box-shadow: inset 1px 1px 1px 0 rgba(0, 0, 0, 0.05);
            position: relative;
            transition: 0.15s ease;
        }

        .toggle span:before {
            content: "";
            display: block;
            position: absolute;
            left: 3px;
            top: 3px;
            height: 18px;
            width: 18px;
            background-color: var(--c-background-primary);
            border-radius: 50%;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.15);
            transition: 0.15s ease;
        }

        .toggle input {
            clip: rect(0 0 0 0);
            -webkit-clip-path: inset(50%);
            clip-path: inset(50%);
            height: 1px;
            overflow: hidden;
            position: absolute;
            white-space: nowrap;
            width: 1px;
        }

        .toggle input:checked+span {
            background-color: var(--c-accent-primary);
        }

        .toggle input:checked+span:before {
            transform: translateX(calc(100% - 2px));
        }

        .toggle input:focus+span {
            box-shadow: 0 0 0 4px var(--c-background-tertiary);
        }

        .card-body {
            padding: 1rem 1.25rem;
            font-size: 0.875rem;
        }

        .card-footer {
            margin-top: auto;
            padding: 1rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            border-top: 1px solid var(--c-border-primary);
        }

        .card-footer a {
            color: var(--c-text-action);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.875rem;
        }

        @media (max-width: 1399px) {
            .payment-card h5 {
                font-size: 14px;
            }

            .payment-card a {
                font-size: 13px;
            }
        }
        
        .w-50-percent p{
            width: 50%;
        }
        .w-50-percent p:nth-child(2),
        .w-50-percent p:nth-child(4){
            text-align: right;
        }
    </style>
@endpush

@push('scripts')
    <script>
        (function() {
            var initGateways = function() {
                if (typeof window.jQuery === 'undefined') return;
                var $ = window.jQuery;
                
                'use strict'
                
                // Status toggle logic
                $('.toggle-status').change(function() {
                    // Implementation of status toggle
                });
            };

            // Use AdminCore loader if available
            if (typeof window.AdminCore !== 'undefined' && typeof window.AdminCore.loader !== 'undefined') {
                window.AdminCore.loader.waitForJQuery(initGateways);
            } else {
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof window.jQuery !== 'undefined') {
                        initGateways();
                    } else {
                        var attempts = 0;
                        var interval = setInterval(function() {
                            if (typeof window.jQuery !== 'undefined') {
                                clearInterval(interval);
                                initGateways();
                            } else if (attempts >= 50) {
                                clearInterval(interval);
                            }
                            attempts++;
                        }, 50);
                    }
                });
            }
        })();
    </script>
@endpush
