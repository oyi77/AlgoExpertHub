<header class="fixed top-0 left-0 right-0 z-50 transition-all duration-300" id="main-header">
    <nav class="container mx-auto px-6 py-4 flex items-center justify-between">
        <div class="flex items-center space-x-2">
            <div class="w-10 h-10 bg-primary-600 rounded-xl flex items-center justify-center shadow-lg shadow-primary-500/20">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </div>
            <span class="text-2xl font-bold tracking-tight text-white">{{ config('app.name') }}</span>
        </div>

        <div class="hidden md:flex items-center space-x-8 text-sm font-medium">
            <a href="#features" class="hover:text-primary-400 transition-colors">{{ __('Features') }}</a>
            <a href="#bots" class="hover:text-primary-400 transition-colors">{{ __('Bots') }}</a>
            <a href="#pricing" class="hover:text-primary-400 transition-colors">{{ __('Pricing') }}</a>
            <a href="#faq" class="hover:text-primary-400 transition-colors">{{ __('FAQ') }}</a>
        </div>

        <div class="flex items-center space-x-4">
            @auth
                <a href="{{ route('user.dashboard') }}" class="px-5 py-2.5 rounded-xl border border-white/10 hover:bg-white/5 transition-all text-sm font-semibold">
                    {{ __('Dashboard') }}
                </a>
            @else
                <a href="{{ route('user.login') }}" class="px-5 py-2.5 rounded-xl text-sm font-semibold hover:text-primary-400 transition-colors">
                    {{ __('Login') }}
                </a>
                <a href="{{ route('user.register') }}" class="btn-shimmer px-6 py-2.5 rounded-xl text-sm font-bold shadow-lg shadow-primary-500/20">
                    {{ __('Get Started') }}
                </a>
            @endauth
        </div>
    </nav>
</header>

@push('scripts')
<script>
    window.addEventListener('scroll', () => {
        const header = document.getElementById('main-header');
        if (window.scrollY > 50) {
            header.classList.add('glass-dark', 'py-2');
            header.classList.remove('py-4');
        } else {
            header.classList.remove('glass-dark', 'py-2');
            header.classList.add('py-4');
        }
    });
</script>
@endpush
