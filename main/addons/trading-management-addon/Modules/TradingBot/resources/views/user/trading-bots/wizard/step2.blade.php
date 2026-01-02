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

        {{-- Step 2: Trading Preset Selection --}}
        <form action="{{ route('user.trading-management.trading-bots.wizard.step.process', ['step' => $step]) }}" method="POST" id="wizard-step2-form">
            @csrf

            <div class="mb-4">
                <h5 class="mb-3">{{ __('Step 2: Select Trading Preset') }}</h5>
                <p class="text-muted">{{ __('Choose a risk management preset that defines position sizing, stop loss, and take profit settings.') }}</p>
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
                <label for="trading_preset_id" class="form-label required">{{ __('Trading Preset') }}</label>
                <select name="trading_preset_id" id="trading_preset_id" class="form-select @error('trading_preset_id') is-invalid @enderror" required>
                    <option value="">{{ __('-- Select Trading Preset --') }}</option>
                    @foreach($presets as $preset)
                        <option value="{{ $preset->id }}" 
                            {{ old('trading_preset_id', $selectedPreset) == $preset->id ? 'selected' : '' }}
                            data-description="{{ $preset->description ?? '' }}"
                            data-risk-percent="{{ $preset->risk_percent ?? '' }}">
                            {{ $preset->name }}
                            @if($preset->is_admin_owned)
                                <span class="badge bg-info">{{ __('Public') }}</span>
                            @endif
                        </option>
                    @endforeach
                </select>
                @error('trading_preset_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="form-text text-muted">
                    {{ __('Select a trading preset that defines your risk management strategy.') }}
                </small>
            </div>

            {{-- Preset Details (shown when selected) --}}
            <div id="preset-details" class="card mb-4" style="display: none;">
                <div class="card-body">
                    <h6 class="card-title">{{ __('Preset Details') }}</h6>
                    <div id="preset-description" class="text-muted"></div>
                    <div id="preset-risk" class="mt-2"></div>
                </div>
            </div>

            @if($presets->isEmpty())
                <div class="alert alert-warning">
                    <i class="fa fa-exclamation-triangle"></i>
                    <strong>{{ __('No Trading Presets Available') }}</strong>
                    <p class="mb-0 mt-2">{{ __('You need to create a trading preset first or use a public preset.') }}</p>
                </div>
            @endif

            {{-- Navigation Buttons --}}
            <div class="d-flex justify-content-between mt-4">
                <div>
                    <a href="{{ route('user.trading-management.trading-bots.wizard.back', ['step' => $step]) }}" class="btn btn-secondary">
                        <i class="fa fa-arrow-left"></i> {{ __('Back') }}
                    </a>
                </div>
                <div>
                    <button type="submit" class="btn btn-primary" {{ $presets->isEmpty() ? 'disabled' : '' }}>
                        {{ __('Next: Select Filter Strategy') }} <i class="fa fa-arrow-right"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('wizard-step2-form');
    const select = document.getElementById('trading_preset_id');
    const detailsCard = document.getElementById('preset-details');
    const descriptionDiv = document.getElementById('preset-description');
    const riskDiv = document.getElementById('preset-risk');
    
    // Show preset details when selected
    select.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const description = selectedOption.getAttribute('data-description');
        const riskPercent = selectedOption.getAttribute('data-risk-percent');
        
        if (this.value && (description || riskPercent)) {
            if (description) {
                descriptionDiv.innerHTML = '<strong>{{ __("Description") }}:</strong> ' + description;
            }
            if (riskPercent) {
                riskDiv.innerHTML = '<strong>{{ __("Risk per Trade") }}:</strong> ' + riskPercent + '%';
            }
            detailsCard.style.display = 'block';
        } else {
            detailsCard.style.display = 'none';
        }
    });
    
    // Trigger change on load if preset is already selected
    if (select.value) {
        select.dispatchEvent(new Event('change'));
    }
    
    // Form validation
    form.addEventListener('submit', function(e) {
        if (!select.value) {
            e.preventDefault();
            alert('{{ __("Please select a trading preset") }}');
            return false;
        }
    });
});
</script>
@endpush
@endsection
