@extends(Config::theme() . 'layout.auth')

@section('title')
    {{ $title }}
@endsection

@section('content')
<div class="sp_site_card">
    <div class="card-header">
        <div class="d-flex flex-wrap align-items-center justify-content-between">
            <h4>{{ __($title) }}</h4>
            <div>
                <a href="{{ route('user.trading-management.trading-bots.wizard.cancel') }}" class="btn btn-sm btn-secondary">
                    <i class="fa fa-times"></i> {{ __('Cancel') }}
                </a>
            </div>
        </div>
    </div>
    <div class="card-body">
        {{-- Progress Indicator --}}
        @include('trading-management::user.trading-bots.wizard.partials.progress', ['step' => $step, 'totalSteps' => $totalSteps])

        {{-- Step 1: Exchange Connection Selection --}}
        <form action="{{ route('user.trading-management.trading-bots.wizard.step.process', ['step' => $step]) }}" method="POST" id="wizard-step1-form">
            @csrf

            <div class="mb-4">
                <h5 class="mb-3">{{ __('Step 1: Select Exchange Connection') }}</h5>
                <p class="text-muted">{{ __('Choose the exchange or broker connection you want to use for this trading bot.') }}</p>
            </div>

            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            <div class="mb-4">
                <label for="exchange_connection_id" class="form-label required">{{ __('Exchange Connection') }}</label>
                <select name="exchange_connection_id" id="exchange_connection_id" class="form-select @error('exchange_connection_id') is-invalid @enderror" required>
                    <option value="">{{ __('-- Select Exchange Connection --') }}</option>
                    @foreach($connections as $connection)
                        <option value="{{ $connection->id }}" 
                            {{ old('exchange_connection_id', $selectedConnection) == $connection->id ? 'selected' : '' }}
                            data-type="{{ $connection->connection_type }}"
                            data-status="{{ $connection->is_active ? 'active' : 'inactive' }}">
                            {{ $connection->name }} 
                            ({{ $connection->exchange_name }})
                            @if(!$connection->is_active)
                                - {{ __('Inactive') }}
                            @endif
                        </option>
                    @endforeach
                </select>
                @error('exchange_connection_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="form-text text-muted">
                    {{ __('Select an exchange connection. If you don\'t have one, create it first from the exchange connections page.') }}
                </small>
            </div>

            @if($connections->isEmpty())
                <div class="alert alert-warning">
                    <i class="fa fa-exclamation-triangle"></i>
                    <strong>{{ __('No Exchange Connections Available') }}</strong>
                    <p class="mb-0 mt-2">{{ __('You need to create an exchange connection first before creating a trading bot.') }}</p>
                    <a href="{{ route('user.trading-management.exchange-connections.create') }}" class="btn btn-sm btn-primary mt-2">
                        <i class="fa fa-plus"></i> {{ __('Create Exchange Connection') }}
                    </a>
                </div>
            @endif

            {{-- Navigation Buttons --}}
            <div class="d-flex justify-content-between mt-4">
                <div>
                    <a href="{{ route('user.trading-management.trading-bots.index') }}" class="btn btn-secondary">
                        <i class="fa fa-arrow-left"></i> {{ __('Back to List') }}
                    </a>
                </div>
                <div>
                    <button type="submit" class="btn btn-primary" {{ $connections->isEmpty() ? 'disabled' : '' }}>
                        {{ __('Next: Select Trading Preset') }} <i class="fa fa-arrow-right"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('wizard-step1-form');
    const select = document.getElementById('exchange_connection_id');
    
    // Warn if inactive connection is selected
    select.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const status = selectedOption.getAttribute('data-status');
        
        // Remove any existing warnings
        const existingWarning = document.querySelector('.connection-warning');
        if (existingWarning) {
            existingWarning.remove();
        }
        
        if (status === 'inactive' && this.value) {
            const warning = document.createElement('div');
            warning.className = 'alert alert-warning connection-warning mt-2';
            warning.innerHTML = '<i class="fa fa-exclamation-triangle"></i> <strong>{{ __("Warning") }}:</strong> {{ __("This connection is inactive. Please activate it before using it in a trading bot.") }}';
            select.parentElement.appendChild(warning);
        }
    });
    
    // Form validation
    form.addEventListener('submit', function(e) {
        if (!select.value) {
            e.preventDefault();
            alert('{{ __("Please select an exchange connection") }}');
            return false;
        }
    });
});
</script>
@endpush
@endsection
