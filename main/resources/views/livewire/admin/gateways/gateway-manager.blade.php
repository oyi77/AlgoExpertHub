<div class="row">
    @foreach ($gateways as $gateway)
    <div class="col-xxl-3 col-lg-4 col-md-6 mb-4" wire:key="gateway-{{ $gateway->id }}">
        <article class="card payment-card mb-0 h-100">
            <div class="card-header align-items-center">
                <div>
                    <span><img src="{{ Config::getFile('gateways', $gateway->image, true) }}" /></span>
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
                    <span>{{ optional($gateway->parameter)->gateway_currency }}</span>
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

            <div class="card-footer justify-content-between align-items-center">
                @if ($gateway->type)
                    <h5 class="text-dark fw-600">
                        {{ Str::ucfirst(str_replace('_', ' ', $gateway->name)) }}</h5>
                    <a href="{{ route('admin.payment.gateway', $gateway->name) }}"
                        class="d-flex align-items-center"><i class="las la-eye fs-25 mr-2"></i>
                        {{ __('View integration') }}</a>
                @else
                    <h5 class="text-dark fw-600">
                        {{ Str::ucfirst(str_replace('_', ' ', $gateway->name)) }}</h5>
                    <a href="{{ route('admin.payment.offline.edit', $gateway->id) }}"
                        class="d-flex align-items-center"><i class="las la-eye fs-25 mr-2"></i>
                        {{ __('View integration') }}</a>
                @endif
            </div>
        </article>
    </div>
    @endforeach

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
