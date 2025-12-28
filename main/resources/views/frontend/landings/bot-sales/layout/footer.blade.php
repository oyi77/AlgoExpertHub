<footer class="bg-dark-950 border-t border-white/5 pt-20 pb-10 mt-20">
    <div class="container mx-auto px-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-16">
            <div class="col-span-1 md:col-span-1">
                <div class="flex items-center space-x-2 mb-6">
                    <div class="w-8 h-8 bg-primary-600 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <span class="text-xl font-bold tracking-tight text-white">{{ config('app.name') }}</span>
                </div>
                <p class="text-dark-400 text-sm leading-relaxed">
                    {{ __('Empowering traders with institutional-grade automation tools and real-time execution across global markets.') }}
                </p>
                <div class="flex space-x-4 mt-8">
                    <a href="#" class="w-10 h-10 rounded-full bg-white/5 flex items-center justify-center hover:bg-primary-600/20 hover:text-primary-400 transition-all">
                        <i data-feather="twitter" class="w-5 h-5"></i>
                    </a>
                    <a href="#" class="w-10 h-10 rounded-full bg-white/5 flex items-center justify-center hover:bg-primary-600/20 hover:text-primary-400 transition-all">
                        <i data-feather="github" class="w-5 h-5"></i>
                    </a>
                    <a href="#" class="w-10 h-10 rounded-full bg-white/5 flex items-center justify-center hover:bg-primary-600/20 hover:text-primary-400 transition-all">
                        <i data-feather="send" class="w-5 h-5"></i>
                    </a>
                </div>
            </div>

            <div>
                <h4 class="text-white font-bold mb-6">{{ __('Platform') }}</h4>
                <ul class="space-y-4 text-sm text-dark-400">
                    <li><a href="#" class="hover:text-primary-400 transition-colors">{{ __('Trading Terminal') }}</a></li>
                    <li><a href="#" class="hover:text-primary-400 transition-colors">{{ __('Copy Trading') }}</a></li>
                    <li><a href="#" class="hover:text-primary-400 transition-colors">{{ __('Bot Marketplace') }}</a></li>
                    <li><a href="#" class="hover:text-primary-400 transition-colors">{{ __('Strategy Builder') }}</a></li>
                </ul>
            </div>

            <div>
                <h4 class="text-white font-bold mb-6">{{ __('Support') }}</h4>
                <ul class="space-y-4 text-sm text-dark-400">
                    <li><a href="#" class="hover:text-primary-400 transition-colors">{{ __('Help Center') }}</a></li>
                    <li><a href="#" class="hover:text-primary-400 transition-colors">{{ __('API Documentation') }}</a></li>
                    <li><a href="#" class="hover:text-primary-400 transition-colors">{{ __('Community') }}</a></li>
                    <li><a href="#" class="hover:text-primary-400 transition-colors">{{ __('Contact Us') }}</a></li>
                </ul>
            </div>

            <div>
                <h4 class="text-white font-bold mb-6">{{ __('Legal') }}</h4>
                <ul class="space-y-4 text-sm text-dark-400">
                    <li><a href="#" class="hover:text-primary-400 transition-colors">{{ __('Privacy Policy') }}</a></li>
                    <li><a href="#" class="hover:text-primary-400 transition-colors">{{ __('Terms of Service') }}</a></li>
                    <li><a href="#" class="hover:text-primary-400 transition-colors">{{ __('Risk Disclosure') }}</a></li>
                    <li><a href="#" class="hover:text-primary-400 transition-colors">{{ __('Refund Policy') }}</a></li>
                </ul>
            </div>
        </div>

        <div class="border-t border-white/5 pt-10 flex flex-col md:flex-row justify-between items-center text-sm text-dark-500">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            <div class="flex items-center space-x-2 mt-4 md:mt-0">
                <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                <span>{{ __('System Status: Operational') }}</span>
            </div>
        </div>
    </div>
</footer>

@push('scripts')
<script src="https://unpkg.com/feather-icons"></script>
<script>
    feather.replace();
</script>
@endpush
