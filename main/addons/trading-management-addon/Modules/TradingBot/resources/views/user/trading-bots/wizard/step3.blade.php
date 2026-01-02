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

        {{-- Step 3: Filter Strategy & AI Profile (Optional) --}}
        <form action="{{ route('user.trading-management.trading-bots.wizard.step.process', ['step' => $step]) }}" method="POST" id="wizard-step3-form">
            @csrf

            <div class="mb-4">
                <h5 class="mb-3">{{ __('Step 3: Select Filter Strategy & AI Profile (Optional)') }}</h5>
                <p class="text-muted">{{ __('Optionally add a filter strategy to only execute trades when technical indicators align, and/or enable AI market analysis confirmation.') }}</p>
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

            {{-- Filter Strategy (Optional) --}}
            <div class="mb-4">
                <label for="filter_strategy_id" class="form-label">{{ __('Filter Strategy') }} <span class="text-muted">({{ __('Optional') }})</span></label>
                <select name="filter_strategy_id" id="filter_strategy_id" class="form-select @error('filter_strategy_id') is-invalid @enderror">
                    <option value="">{{ __('-- No Filter Strategy (Execute All Signals) --') }}</option>
                    @foreach($filterStrategies as $strategy)
                        <option value="{{ $strategy->id }}" 
                            {{ old('filter_strategy_id', $selectedFilterStrategy) == $strategy->id ? 'selected' : '' }}>
                            {{ $strategy->name }}
                            @if($strategy->is_admin_owned)
                                <span class="badge bg-info">{{ __('Public') }}</span>
                            @endif
                        </option>
                    @endforeach
                </select>
                @error('filter_strategy_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="form-text text-muted">
                    {{ __('Filter strategies use technical indicators (EMA, Stochastic, PSAR, etc.) to only execute trades when conditions are met.') }}
                </small>
            </div>

            {{-- AI Model Profile (Optional) --}}
            <div class="mb-4">
                <label for="ai_model_profile_id" class="form-label">{{ __('AI Market Analysis') }} <span class="text-muted">({{ __('Optional') }})</span></label>
                <select name="ai_model_profile_id" id="ai_model_profile_id" class="form-select @error('ai_model_profile_id') is-invalid @enderror">
                    <option value="">{{ __('-- No AI Analysis --') }}</option>
                    @foreach($aiProfiles as $profile)
                        <option value="{{ $profile->id }}" 
                            {{ old('ai_model_profile_id', $selectedAiProfile) == $profile->id ? 'selected' : '' }}>
                            {{ $profile->name }} ({{ $profile->model_name ?? 'N/A' }})
                            @if($profile->is_admin_owned)
                                <span class="badge bg-info">{{ __('Public') }}</span>
                            @endif
                        </option>
                    @endforeach
                </select>
                @error('ai_model_profile_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="form-text text-muted">
                    {{ __('Enable AI market analysis to confirm market conditions before executing trades.') }}
                </small>
            </div>

            <div class="alert alert-info">
                <i class="fa fa-info-circle"></i>
                <strong>{{ __('Note') }}:</strong> {{ __('Both filter strategy and AI profile are optional. You can skip this step and proceed to review.') }}
            </div>

            {{-- Navigation Buttons --}}
            <div class="d-flex justify-content-between mt-4">
                <div>
                    <a href="{{ route('user.trading-management.trading-bots.wizard.back', ['step' => $step]) }}" class="btn btn-secondary">
                        <i class="fa fa-arrow-left"></i> {{ __('Back') }}
                    </a>
                </div>
                <div>
                    <button type="submit" class="btn btn-primary">
                        {{ __('Next: Review & Complete') }} <i class="fa fa-arrow-right"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
