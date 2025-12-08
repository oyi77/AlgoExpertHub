@extends(Config::theme() . 'layout.auth')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="sp_site_card">
            <div class="card-body text-center py-5">
                <div class="mb-4">
                    <i class="las la-rocket" style="font-size: 80px; color: var(--base-color);"></i>
                </div>
                <h2 class="mb-3">{{ __('Welcome to AlgoExpert Hub!') }}</h2>
                <p class="text-muted mb-4">{{ __('Let\'s get you started with a quick setup guide. This will only take a few minutes.') }}</p>
                
                <div class="progress mb-4" style="height: 10px;">
                    <div class="progress-bar" role="progressbar" style="width: {{ $progress }}%" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                
                <p class="text-muted mb-4">{{ __('Progress: :progress%', ['progress' => $progress]) }}</p>
                
                <form action="{{ route('user.onboarding.welcome.complete') }}" method="POST">
                    @csrf
                    <div class="d-flex gap-3 justify-content-center">
                        <button type="submit" class="btn sp_theme_btn btn-lg">
                            <i class="las la-arrow-right me-2"></i> {{ __('Get Started') }}
                        </button>
                        <a href="{{ route('user.onboarding.skip') }}" class="btn btn-outline-secondary btn-lg" onclick="event.preventDefault(); document.getElementById('skip-form').submit();">
                            {{ __('Skip for Now') }}
                        </a>
                    </div>
                </form>
                
                <form id="skip-form" action="{{ route('user.onboarding.skip') }}" method="POST" style="display: none;">
                    @csrf
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

