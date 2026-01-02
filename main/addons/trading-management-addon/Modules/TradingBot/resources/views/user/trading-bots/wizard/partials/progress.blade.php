{{-- Progress Indicator --}}
<div class="wizard-progress mb-4">
    <div class="progress mb-2" style="height: 30px;">
        <div class="progress-bar progress-bar-striped progress-bar-animated" 
             role="progressbar" 
             style="width: {{ ($step / $totalSteps) * 100 }}%"
             aria-valuenow="{{ $step }}" 
             aria-valuemin="0" 
             aria-valuemax="{{ $totalSteps }}">
            {{ __('Step') }} {{ $step }} {{ __('of') }} {{ $totalSteps }}
        </div>
    </div>
    <div class="d-flex justify-content-between">
        <div class="step-indicator {{ $step >= 1 ? 'active' : '' }}">
            <i class="fa {{ $step > 1 ? 'fa-check-circle' : ($step == 1 ? 'fa-circle' : 'fa-circle-o') }}"></i>
            <small class="d-block mt-1">{{ __('Connection') }}</small>
        </div>
        <div class="step-indicator {{ $step >= 2 ? 'active' : '' }}">
            <i class="fa {{ $step > 2 ? 'fa-check-circle' : ($step == 2 ? 'fa-circle' : 'fa-circle-o') }}"></i>
            <small class="d-block mt-1">{{ __('Preset') }}</small>
        </div>
        <div class="step-indicator {{ $step >= 3 ? 'active' : '' }}">
            <i class="fa {{ $step > 3 ? 'fa-check-circle' : ($step == 3 ? 'fa-circle' : 'fa-circle-o') }}"></i>
            <small class="d-block mt-1">{{ __('Filter') }}</small>
        </div>
        <div class="step-indicator {{ $step >= 4 ? 'active' : '' }}">
            <i class="fa {{ $step > 4 ? 'fa-check-circle' : ($step == 4 ? 'fa-circle' : 'fa-circle-o') }}"></i>
            <small class="d-block mt-1">{{ __('Review') }}</small>
        </div>
    </div>
</div>

<style>
.wizard-progress .step-indicator {
    text-align: center;
    flex: 1;
    color: #6c757d;
}
.wizard-progress .step-indicator.active {
    color: #0d6efd;
}
.wizard-progress .step-indicator i {
    font-size: 1.5rem;
}
</style>
