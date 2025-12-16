@extends(Config::themeView('layout.auth'))

@section('content')
    <div class="row gy-4">
        <div class="col-12">
            <h4 class="mb-0">{{ __('External Signal') }}</h4>
            <p class="text-muted">{{ __('Manage your signal sources, channel forwarding, and pattern templates') }}</p>
        </div>

        @if(!$multiChannelEnabled)
            <div class="col-12">
                <div class="alert alert-warning">
                    <i class="las la-exclamation-triangle"></i> 
                    {{ __('Multi-Channel Signal Addon is not enabled. Please contact administrator.') }}
                </div>
            </div>
        @else
            <div class="col-12">
                <!-- Tab Navigation -->
                <ul class="nav nav-pills mb-4" id="externalSignalTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link {{ $activeTab === 'sources' ? 'active' : '' }}" 
                           id="sources-tab" 
                           data-toggle="tab" 
                           href="#sources" 
                           role="tab" 
                           aria-controls="sources" 
                           aria-selected="{{ $activeTab === 'sources' ? 'true' : 'false' }}">
                            <i class="las la-signal me-1"></i> {{ __('Signal Sources') }}
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link {{ $activeTab === 'forwarding' ? 'active' : '' }}" 
                           id="forwarding-tab" 
                           data-toggle="tab" 
                           href="#forwarding" 
                           role="tab" 
                           aria-controls="forwarding" 
                           aria-selected="{{ $activeTab === 'forwarding' ? 'true' : 'false' }}">
                            <i class="las la-share-alt me-1"></i> {{ __('Channel Forwarding') }}
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link {{ $activeTab === 'templates' ? 'active' : '' }}" 
                           id="templates-tab" 
                           data-toggle="tab" 
                           href="#templates" 
                           role="tab" 
                           aria-controls="templates" 
                           aria-selected="{{ $activeTab === 'templates' ? 'true' : 'false' }}">
                            <i class="las la-code me-1"></i> {{ __('Pattern Templates') }}
                        </a>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="externalSignalTabContent">
                    <!-- Signal Sources Tab -->
                    <div class="tab-pane fade {{ $activeTab === 'sources' ? 'show active' : '' }}" 
                         id="sources" 
                         role="tabpanel" 
                         aria-labelledby="sources-tab">
                        @include('multi-channel-signal-addon::user.partials._signal_sources_content', [
                            'sources' => $sources ?? collect(),
                            'stats' => $stats ?? []
                        ])
                    </div>

                    <!-- Channel Forwarding Tab -->
                    <div class="tab-pane fade {{ $activeTab === 'forwarding' ? 'show active' : '' }}" 
                         id="forwarding" 
                         role="tabpanel" 
                         aria-labelledby="forwarding-tab">
                        @include('multi-channel-signal-addon::user.partials._channel_forwarding_content', [
                            'channels' => $channels ?? collect(),
                            'stats' => $channelStats ?? []
                        ])
                    </div>

                    <!-- Pattern Templates Tab -->
                    <div class="tab-pane fade {{ $activeTab === 'templates' ? 'show active' : '' }}" 
                         id="templates" 
                         role="tabpanel" 
                         aria-labelledby="templates-tab">
                        <div class="row gy-4">
                            <div class="col-12">
                                <div class="sp_site_card">
                                    <div class="text-center py-5">
                                        <i class="las la-code la-3x text-muted mb-3"></i>
                                        <h5 class="mb-2">{{ __('Pattern Templates') }}</h5>
                                        <p class="text-muted mb-4">{{ __('Pattern templates are managed by administrators. Contact your admin to create or modify pattern templates for signal parsing.') }}</p>
                                        @if(Route::has('admin.pattern-templates.index'))
                                            <a href="{{ route('admin.pattern-templates.index') }}" class="btn btn-outline-primary" target="_blank">
                                                <i class="las la-external-link-alt me-1"></i> {{ __('View Admin Panel') }}
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    @push('script')
    <script>
        // Handle tab switching (Bootstrap 4)
        $(document).ready(function() {
            'use strict'
            
            // Function to show tab by ID
            function showTab(tabId) {
                const tabLink = $('#externalSignalTabs a[href="#' + tabId + '"]');
                if (tabLink.length) {
                    tabLink.tab('show');
                }
            }
            
            // Handle URL hash on page load
            const hash = window.location.hash;
            if (hash) {
                const tabId = hash.replace('#', '');
                // Small delay to ensure DOM is ready
                setTimeout(function() {
                    showTab(tabId);
                }, 100);
            }
            
            // Also listen for hash changes (when back button is clicked)
            $(window).on('hashchange', function() {
                const hash = window.location.hash;
                if (hash) {
                    const tabId = hash.replace('#', '');
                    showTab(tabId);
                }
            });

            // Update URL hash when tab changes
            $('#externalSignalTabs a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
                const targetId = $(e.target).attr('href').replace('#', '');
                // Update hash without triggering hashchange
                if (window.location.hash !== '#' + targetId) {
                    window.history.replaceState(null, null, '#' + targetId);
                }
            });
        });
    </script>
    @endpush
@endsection

