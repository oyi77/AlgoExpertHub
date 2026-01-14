<div class="row">
    @forelse ($gateways as $gateway)
    <div class="col-xxl-3 col-lg-4 col-md-6 mb-4" wire:key="gateway-{{ $gateway->id }}">
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
                <p><span class="fw-600">{{ __('Charge :') }}</span> <span>{{ Config::formatter($gateway->charge) }}</span>
                </p>
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
    @empty
    <div class="col-12">
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> {{ __('No payment gateways found. Please configure payment gateways in the system.') }}
        </div>
    </div>
    @endforelse

    @if (request()->routeIs('admin.payment.offline'))
    <div class="col-xxl-3 col-lg-4 col-md-6 mb-4">
        <article class="card payment-card mb-0 h-100">
            <div class="card-header">
                <div>
                    <h4 class="text-dark">{{ __('Create Offline Gateway') }}</h4>
                </div>
            </div>
            <div class="card-body d-flex flex-wrap justify-content-center align-items-center text-center">
                <a href="{{ route('admin.payment.offline.create') }}">
                    <i class="las la-plus-circle fs-50 text-muted mb-3"></i>
                    <br>
                    {{ __('Create Offline Gateway') }}
                </a>
            </div>
        </article>
    </div>
    @endif
</div>
