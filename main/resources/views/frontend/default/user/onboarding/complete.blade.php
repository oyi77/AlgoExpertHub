@extends(Config::theme() . 'layout.auth')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="sp_site_card">
            <div class="card-body text-center py-5">
                <div class="mb-4">
                    <div class="success-animation mb-3">
                        <i class="las la-check-circle" style="font-size: 100px; color: #28a745; animation: scaleIn 0.5s ease-out;"></i>
                    </div>
                </div>
                <h2 class="mb-3">{{ __('Onboarding Complete!') }}</h2>
                <p class="text-muted mb-4">{{ __('Congratulations! You\'ve completed the setup process. You\'re all set to start trading.') }}</p>
                
                @if(isset($steps))
                <div class="row g-3 mb-4">
                    @foreach($steps as $stepKey => $step)
                        @if($stepKey !== 'welcome' && ($step['completed'] ?? false))
                            <div class="col-md-6">
                                <div class="p-3 border rounded bg-light">
                                    <i class="las la-check-circle text-success me-2"></i>
                                    <span>{{ $step['label'] ?? ucfirst(str_replace('_', ' ', $stepKey)) }}</span>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
                @endif
                
                <div class="d-flex gap-3 justify-content-center flex-wrap">
                    <a href="{{ route('user.dashboard') }}" class="btn sp_theme_btn btn-lg">
                        <i class="las la-home me-2"></i> {{ __('Go to Dashboard') }}
                    </a>
                    <a href="{{ route('user.trading.multi-channel-signal.index') }}" class="btn btn-outline-primary btn-lg">
                        <i class="las la-signal me-2"></i> {{ __('View Signals') }}
                    </a>
                </div>
                
                <div class="mt-4">
                    <p class="text-muted small mb-0">
                        {{ __('Need help?') }} 
                        <a href="{{ route('user.ticket.create') ?? '#' }}">{{ __('Contact Support') }}</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('style')
<style>
    @keyframes scaleIn {
        from {
            transform: scale(0);
            opacity: 0;
        }
        to {
            transform: scale(1);
            opacity: 1;
        }
    }
</style>
@endpush
@endsection

