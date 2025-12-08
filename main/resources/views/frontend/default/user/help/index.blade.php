@extends(Config::theme() . 'layout.auth')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <div>
                <h4 class="mb-0">{{ __('Help Center') }}</h4>
                <p class="text-muted mb-0">{{ __('Find answers to common questions') }}</p>
            </div>
        </div>
    </div>
    
    <div class="col-12">
        <div class="row g-3">
            @foreach($topics as $topicKey => $topicLabel)
            <div class="col-md-6 col-lg-4">
                <div class="sp_site_card help-topic-card h-100">
                    <div class="card-body">
                        <div class="mb-3">
                            @if($topicKey === 'general')
                                <i class="las la-question-circle" style="font-size: 48px; color: var(--base-color);"></i>
                            @elseif($topicKey === 'signals')
                                <i class="las la-signal" style="font-size: 48px; color: var(--base-color);"></i>
                            @elseif($topicKey === 'auto-trading')
                                <i class="las la-bolt" style="font-size: 48px; color: var(--base-color);"></i>
                            @elseif($topicKey === 'presets')
                                <i class="las la-cog" style="font-size: 48px; color: var(--base-color);"></i>
                            @elseif($topicKey === 'marketplaces')
                                <i class="las la-store" style="font-size: 48px; color: var(--base-color);"></i>
                            @elseif($topicKey === 'wallet')
                                <i class="las la-wallet" style="font-size: 48px; color: var(--base-color);"></i>
                            @endif
                        </div>
                        <h5 class="mb-2">{{ $topicLabel }}</h5>
                        <p class="text-muted small mb-3">
                            @if($topicKey === 'general')
                                {{ __('General information about the platform') }}
                            @elseif($topicKey === 'signals')
                                {{ __('How to receive and manage trading signals') }}
                            @elseif($topicKey === 'auto-trading')
                                {{ __('Setting up automated trading execution') }}
                            @elseif($topicKey === 'presets')
                                {{ __('Creating and managing trading presets') }}
                            @elseif($topicKey === 'marketplaces')
                                {{ __('Browsing and using marketplace items') }}
                            @elseif($topicKey === 'wallet')
                                {{ __('Deposits, withdrawals, and transactions') }}
                            @endif
                        </p>
                        <a href="{{ route('user.help.topic', ['topic' => $topicKey]) }}" class="btn btn-outline-primary btn-sm">
                            {{ __('Learn More') }} <i class="las la-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    
    <div class="col-12 mt-4">
        <div class="sp_site_card">
            <div class="card-body text-center">
                <h5 class="mb-3">{{ __('Still need help?') }}</h5>
                <p class="text-muted mb-3">{{ __('Can\'t find what you\'re looking for? Contact our support team.') }}</p>
                <a href="{{ route('user.ticket.create') ?? '#' }}" class="btn sp_theme_btn">
                    <i class="las la-headset me-2"></i> {{ __('Contact Support') }}
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

