@php
    $onboardingService = app(\App\Services\UserOnboardingService::class);
    $user = auth()->user();
    $nextStep = $onboardingService->getNextIncompleteStep($user);
@endphp

<div class="d-card mb-4 onboarding-checklist-widget">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="las la-rocket me-2"></i> {{ __('Getting Started') }}
        </h5>
        <span class="badge bg-primary">{{ $progress }}%</span>
    </div>
    <div class="card-body">
        <div class="progress mb-3" style="height: 10px;">
            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                 role="progressbar" 
                 style="width: {{ $progress }}%" 
                 aria-valuenow="{{ $progress }}" 
                 aria-valuemin="0" 
                 aria-valuemax="100">
            </div>
        </div>
        
        <ul class="list-unstyled mb-3">
            @foreach($checklist as $item)
                <li class="mb-2 d-flex align-items-center">
                    @if($item['completed'])
                        <i class="las la-check-circle text-success me-2" style="font-size: 1.2rem;"></i>
                        <span class="text-muted text-decoration-line-through">{{ $item['label'] }}</span>
                    @else
                        <i class="las la-circle text-muted me-2" style="font-size: 1.2rem;"></i>
                        <a href="{{ $item['route'] }}" class="text-decoration-none">{{ $item['label'] }}</a>
                    @endif
                </li>
            @endforeach
        </ul>
        
        @if($nextStep)
            <div class="d-flex gap-2">
                <a href="{{ route('user.onboarding.step', ['step' => $nextStep]) }}" class="btn sp_theme_btn btn-sm">
                    <i class="las la-arrow-right me-1"></i> {{ __('Continue Setup') }}
                </a>
                <form action="{{ route('user.onboarding.skip') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary btn-sm" onclick="return confirm('{{ __('Are you sure you want to skip onboarding? You can complete it later from your dashboard.') }}')">
                        <i class="las la-times me-1"></i> {{ __('Skip') }}
                    </button>
                </form>
            </div>
        @else
            <div class="text-center">
                <a href="{{ route('user.onboarding.complete') }}" class="btn sp_theme_btn btn-sm">
                    <i class="las la-check me-1"></i> {{ __('Complete Onboarding') }}
                </a>
            </div>
        @endif
    </div>
</div>

