@extends(Config::theme() . 'layout.auth')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="sp_site_card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">{{ __('Onboarding Setup') }}</h4>
                    <span class="badge bg-primary">{{ $currentStepIndex + 1 }}/{{ $totalSteps }}</span>
                </div>
            </div>
            <div class="card-body">
                <!-- Progress Bar -->
                <div class="mb-4">
                    <div class="d-flex justify-content-between mb-2">
                        <small class="text-muted">{{ __('Step :current of :total', ['current' => $currentStepIndex + 1, 'total' => $totalSteps]) }}</small>
                        <small class="text-muted">{{ $progress }}% {{ __('Complete') }}</small>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar" role="progressbar" style="width: {{ $progress }}%" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>

                <!-- Step Content -->
                <div class="mb-4">
                    @if($step === 'profile')
                        @include(Config::theme() . 'user.onboarding.partials._step_profile')
                    @elseif($step === 'plan')
                        @include(Config::theme() . 'user.onboarding.partials._step_plan')
                    @elseif($step === 'signal_source')
                        @include(Config::theme() . 'user.onboarding.partials._step_signal_source')
                    @elseif($step === 'trading_connection')
                        @include(Config::theme() . 'user.onboarding.partials._step_trading_connection')
                    @elseif($step === 'trading_preset')
                        @include(Config::theme() . 'user.onboarding.partials._step_preset')
                    @elseif($step === 'first_deposit')
                        @include(Config::theme() . 'user.onboarding.partials._step_deposit')
                    @endif
                </div>

                <!-- Navigation -->
                <div class="d-flex justify-content-between">
                    <form action="{{ route('user.onboarding.skip') }}" method="POST" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary">
                            <i class="las la-times me-2"></i> {{ __('Skip Onboarding') }}
                        </button>
                    </form>
                    
                    @if($stepData['completed'] ?? false)
                        <form action="{{ route('user.onboarding.step.complete', ['step' => $step]) }}" method="POST" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn sp_theme_btn">
                                {{ __('Continue') }} <i class="las la-arrow-right ms-2"></i>
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

