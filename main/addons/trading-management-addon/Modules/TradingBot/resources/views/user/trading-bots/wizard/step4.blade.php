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

        {{-- Step 4: Review & Complete --}}
        <form action="{{ route('user.trading-management.trading-bots.wizard.complete') }}" method="POST" id="wizard-step4-form">
            @csrf

            <div class="mb-4">
                <h5 class="mb-3">{{ __('Step 4: Review & Complete') }}</h5>
                <p class="text-muted">{{ __('Review your trading bot configuration and provide a name.') }}</p>
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

            {{-- Review Summary --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">{{ __('Configuration Summary') }}</h6>
                </div>
                <div class="card-body">
                    {{-- Exchange Connection --}}
                    <div class="mb-3">
                        <strong>{{ __('Exchange Connection') }}:</strong>
                        @if($connection)
                            <div class="mt-1">
                                <span class="badge bg-primary">{{ $connection->name }}</span>
                                <span class="text-muted">({{ $connection->exchange_name }})</span>
                                @if(!$connection->is_active)
                                    <span class="badge bg-warning">{{ __('Inactive') }}</span>
                                @endif
                            </div>
                        @else
                            <span class="text-danger">{{ __('Not selected') }}</span>
                        @endif
                    </div>

                    {{-- Trading Preset --}}
                    <div class="mb-3">
                        <strong>{{ __('Trading Preset') }}:</strong>
                        @if($preset)
                            <div class="mt-1">
                                <span class="badge bg-success">{{ $preset->name }}</span>
                                @if($preset->description)
                                    <small class="text-muted d-block mt-1">{{ $preset->description }}</small>
                                @endif
                            </div>
                        @else
                            <span class="text-danger">{{ __('Not selected') }}</span>
                        @endif
                    </div>

                    {{-- Filter Strategy --}}
                    <div class="mb-3">
                        <strong>{{ __('Filter Strategy') }}:</strong>
                        @if($filterStrategy)
                            <div class="mt-1">
                                <span class="badge bg-info">{{ $filterStrategy->name }}</span>
                            </div>
                        @else
                            <span class="text-muted">{{ __('None (Execute all signals)') }}</span>
                        @endif
                    </div>

                    {{-- AI Profile --}}
                    <div class="mb-3">
                        <strong>{{ __('AI Market Analysis') }}:</strong>
                        @if($aiProfile)
                            <div class="mt-1">
                                <span class="badge bg-warning">{{ $aiProfile->name }}</span>
                                <small class="text-muted">({{ $aiProfile->model_name ?? 'N/A' }})</small>
                            </div>
                        @else
                            <span class="text-muted">{{ __('Disabled') }}</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Bot Name & Description --}}
            <div class="mb-4">
                <label for="name" class="form-label">{{ __('Bot Name') }}</label>
                <input type="text" 
                    name="name" 
                    id="name" 
                    class="form-control @error('name') is-invalid @enderror" 
                    value="{{ old('name', $wizardData['name'] ?? 'My Trading Bot') }}"
                    placeholder="{{ __('Enter a name for your trading bot') }}">
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4">
                <label for="description" class="form-label">{{ __('Description') }} <span class="text-muted">({{ __('Optional') }})</span></label>
                <textarea 
                    name="description" 
                    id="description" 
                    class="form-control @error('description') is-invalid @enderror" 
                    rows="3"
                    placeholder="{{ __('Enter a description for your trading bot (optional)') }}">{{ old('description', $wizardData['description'] ?? '') }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Trading Mode --}}
            <div class="mb-4">
                <label for="trading_mode" class="form-label">{{ __('Trading Mode') }}</label>
                <select name="trading_mode" id="trading_mode" class="form-select">
                    <option value="SIGNAL_BASED" {{ old('trading_mode', $wizardData['trading_mode'] ?? 'SIGNAL_BASED') == 'SIGNAL_BASED' ? 'selected' : '' }}>
                        {{ __('Signal Based') }} - {{ __('Execute trades from published signals') }}
                    </option>
                    <option value="MARKET_STREAM_BASED" {{ old('trading_mode', $wizardData['trading_mode'] ?? 'SIGNAL_BASED') == 'MARKET_STREAM_BASED' ? 'selected' : '' }}>
                        {{ __('Market Stream Based') }} - {{ __('Monitor market data and execute based on indicators') }}
                    </option>
                </select>
                <small class="form-text text-muted">
                    {{ __('Signal Based: Executes trades from signals published by admins. Market Stream Based: Monitors live market data and executes based on filter strategies.') }}
                </small>
            </div>

            {{-- Paper Trading --}}
            <div class="mb-4">
                <div class="form-check">
                    <input class="form-check-input" 
                        type="checkbox" 
                        name="is_paper_trading" 
                        id="is_paper_trading" 
                        value="1"
                        {{ old('is_paper_trading', $wizardData['is_paper_trading'] ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_paper_trading">
                        <strong>{{ __('Paper Trading Mode') }}</strong>
                        <small class="text-muted d-block">{{ __('Enable paper trading to test your bot without using real money. Recommended for first-time users.') }}</small>
                    </label>
                </div>
            </div>

            <div class="alert alert-warning" id="paper-trading-alert" style="display: {{ old('is_paper_trading', $wizardData['is_paper_trading'] ?? true) ? 'block' : 'none' }};">
                <i class="fa fa-exclamation-triangle"></i>
                <strong>{{ __('Demo Mode') }}:</strong> {{ __('This bot will run in paper trading mode. No real money will be used.') }}
            </div>

            {{-- Navigation Buttons --}}
            <div class="d-flex justify-content-between mt-4">
                <div>
                    <a href="{{ route('user.trading-management.trading-bots.wizard.back', ['step' => $step]) }}" class="btn btn-secondary">
                        <i class="fa fa-arrow-left"></i> {{ __('Back') }}
                    </a>
                </div>
                <div>
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="fa fa-check"></i> {{ __('Create Trading Bot') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('wizard-step4-form');
    const paperTradingCheckbox = document.getElementById('is_paper_trading');
    const paperTradingAlert = document.getElementById('paper-trading-alert');
    
    // Toggle paper trading alert
    if (paperTradingCheckbox && paperTradingAlert) {
        paperTradingCheckbox.addEventListener('change', function() {
            paperTradingAlert.style.display = this.checked ? 'block' : 'none';
        });
    }
    
    // Form validation
    form.addEventListener('submit', function(e) {
        const name = document.getElementById('name').value.trim();
        if (!name) {
            e.preventDefault();
            alert('{{ __("Please enter a name for your trading bot") }}');
            return false;
        }
    });
});
</script>
@endpush
@endsection
