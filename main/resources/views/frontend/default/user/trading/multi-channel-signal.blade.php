@extends(Config::theme() . 'layout.auth')

@section('content')
<div class="row gy-4">
    <div class="col-12 mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h4 class="mb-2">{{ __('Multi-Channel Signal') }}</h4>
                <p class="text-muted mb-0">{{ __('Manage signal sources, channel forwarding, and review auto-created signals') }}</p>
            </div>
        </div>
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
            <div class="sp_site_card">
                <div class="card-header p-3 border-bottom">
                    <ul class="nav nav-pills" id="multiChannelTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $activeTab === 'all-signals' ? 'active' : '' }}" 
                               id="all-signals-tab" 
                               data-bs-toggle="tab" 
                               href="#all-signals" 
                               role="tab"
                               onclick="switchTab('all-signals')">
                                <i class="las la-signal me-1"></i> {{ __('All Signals') }}
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $activeTab === 'signal-sources' ? 'active' : '' }}" 
                               id="signal-sources-tab" 
                               data-bs-toggle="tab" 
                               href="#signal-sources" 
                               role="tab"
                               onclick="switchTab('signal-sources')">
                                <i class="las la-plug me-1"></i> {{ __('Signal Sources') }}
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $activeTab === 'channel-forwarding' ? 'active' : '' }}" 
                               id="channel-forwarding-tab" 
                               data-bs-toggle="tab" 
                               href="#channel-forwarding" 
                               role="tab"
                               onclick="switchTab('channel-forwarding')">
                                <i class="las la-share-alt me-1"></i> {{ __('Channel Forwarding') }}
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $activeTab === 'signal-review' ? 'active' : '' }}" 
                               id="signal-review-tab" 
                               data-bs-toggle="tab" 
                               href="#signal-review" 
                               role="tab"
                               onclick="switchTab('signal-review')">
                                <i class="las la-clipboard-check me-1"></i> {{ __('Signal Review') }}
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $activeTab === 'pattern-templates' ? 'active' : '' }}" 
                               id="pattern-templates-tab" 
                               data-bs-toggle="tab" 
                               href="#pattern-templates" 
                               role="tab"
                               onclick="switchTab('pattern-templates')">
                                <i class="las la-code me-1"></i> {{ __('Pattern Templates') }}
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $activeTab === 'analytics' ? 'active' : '' }}" 
                               id="analytics-tab" 
                               data-bs-toggle="tab" 
                               href="#analytics" 
                               role="tab"
                               onclick="switchTab('analytics')">
                                <i class="las la-chart-bar me-1"></i> {{ __('Analytics') }}
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="multiChannelTabContent">
                        <!-- All Signals Tab -->
                        <div class="tab-pane fade {{ $activeTab === 'all-signals' ? 'show active' : '' }}" 
                             id="all-signals" 
                             role="tabpanel">
                            @if(isset($signals))
                                <div class="row gy-4">
                                    @forelse ($signals as $signal)
                                    <div class="col-xxl-3 col-lg-4 col-md-6">
                                        <div class="singnal-card {{ $signal->direction === 'sell' ? 'border-warning' : 'border-success' }}">
                                            <div class="singnal-card-top">
                                                <div class="left">
                                                    <span class="status text-uppercase {{ $signal->direction === 'sell' ? 'sell' : 'buy' }}">{{ $signal->direction }}</span>
                                                    <span class="fw-medium">{{ $signal->pair->name ?? 'N/A' }}
                                                        @if ($signal->direction === 'sell')
                                                            <i class="fas fa-arrow-down sp_text_danger"></i>
                                                        @else
                                                            <i class="fas fa-arrow-up sp_text_success"></i>
                                                        @endif
                                                    </span>
                                                </div>
                                                <div class="right">
                                                    <p class="text-uppercase">{{ __('ID') }}: {{ $signal->id }}</p>
                                                </div>
                                            </div>
                                            @if($signal->image)
                                            <div class="singnal-card-thumb">
                                                <img src="{{ Config::getFile('signal', $signal->image, true) }}" alt="">
                                            </div>
                                            @endif
                                            <div class="singnal-card-body">
                                                <h5 class="title">
                                                    <a href="{{ route('user.signal.details', ['id' => $signal->id, 'slug' => Str::slug($signal->title)]) }}">
                                                        {{ $signal->title }}
                                                    </a>
                                                </h5>
                                                <ul class="signal-info-list">
                                                    <li class="signal-single-list">
                                                        <span class="caption"><i class="fas fa-id-badge"></i> {{ __('Stop Loss') }}:</span>
                                                        <span class="value">{{ $signal->sl }}</span>
                                                    </li>
                                                    <li class="signal-single-list">
                                                        <span class="caption"><i class="far fa-clock"></i> {{ __('Time Frame') }}:</span>
                                                        <span class="value">{{ $signal->time->name ?? 'N/A' }}</span>
                                                    </li>
                                                    <li class="signal-single-list">
                                                        <span class="caption"><i class="fas fa-money-bill"></i> {{ __('Open') }}:</span>
                                                        <span class="value">{{ $signal->open_price }}</span>
                                                    </li>
                                                    <li class="signal-single-list">
                                                        <span class="caption"><i class="fas fa-hand-holding-usd"></i> {{ __('Take profit') }}:</span>
                                                        <span class="value">{{ $signal->tp }}</span>
                                                    </li>
                                                </ul>
                                                <a href="{{ route('user.signal.details', ['id' => $signal->id, 'slug' => Str::slug($signal->title)]) }}" 
                                                   class="view-signal-btn w-100 text-center mt-3">
                                                    {{ __('View Details') }}
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    @empty
                                    <div class="col-12">
                                        <div class="text-center py-5">
                                            <i class="las la-inbox la-3x text-muted mb-3"></i>
                                            <p class="text-muted">{{ __('No auto-created signals found.') }}</p>
                                        </div>
                                    </div>
                                    @endforelse
                                </div>
                                @if ($signals->hasPages())
                                    <div class="mt-3">
                                        {{ $signals->links() }}
                                    </div>
                                @endif
                            @else
                                <div class="text-center py-5">
                                    <i class="las la-inbox la-3x text-muted mb-3"></i>
                                    <p class="text-muted">{{ __('No signals available.') }}</p>
                                </div>
                            @endif
                        </div>

                        <!-- Signal Sources Tab -->
                        <div class="tab-pane fade {{ $activeTab === 'signal-sources' ? 'show active' : '' }}" 
                             id="signal-sources" 
                             role="tabpanel">
                            @if(isset($sources))
                                @include('multi-channel-signal-addon::user.partials._signal_sources_content', [
                                    'sources' => $sources,
                                    'stats' => [
                                        'total' => $sources->total(),
                                        'active' => $sources->where('status', 'active')->count(),
                                        'paused' => $sources->where('status', 'paused')->count(),
                                        'error' => $sources->where('status', 'error')->count(),
                                    ]
                                ])
                            @else
                                <div class="text-center py-5">
                                    <i class="las la-plug la-3x text-muted mb-3"></i>
                                    <p class="text-muted">{{ __('No signal sources found.') }}</p>
                                    <a href="{{ route('user.signal-sources.create') }}" class="btn sp_theme_btn mt-2">
                                        {{ __('Create First Source') }}
                                    </a>
                                </div>
                            @endif
                        </div>

                        <!-- Channel Forwarding Tab -->
                        <div class="tab-pane fade {{ $activeTab === 'channel-forwarding' ? 'show active' : '' }}" 
                             id="channel-forwarding" 
                             role="tabpanel">
                            @if(isset($channels))
                                @include('multi-channel-signal-addon::user.partials._channel_forwarding_content', [
                                    'channels' => $channels,
                                    'stats' => [
                                        'total' => $channels->total(),
                                        'by_user' => 0,
                                        'by_plan' => 0,
                                        'global' => 0,
                                    ]
                                ])
                            @else
                                <div class="text-center py-5">
                                    <i class="las la-share-alt la-3x text-muted mb-3"></i>
                                    <p class="text-muted">{{ __('No channels assigned to you yet.') }}</p>
                                </div>
                            @endif
                        </div>

                        <!-- Signal Review Tab -->
                        <div class="tab-pane fade {{ $activeTab === 'signal-review' ? 'show active' : '' }}" 
                             id="signal-review" 
                             role="tabpanel">
                            @if(isset($reviewSignals) && $reviewSignals->count() > 0)
                                <div class="row gy-4">
                                    @foreach ($reviewSignals as $signal)
                                    <div class="col-xxl-3 col-lg-4 col-md-6">
                                        <div class="singnal-card border-warning">
                                            <div class="singnal-card-top">
                                                <div class="left">
                                                    <span class="badge bg-warning">{{ __('Draft') }}</span>
                                                    <span class="fw-medium">{{ $signal->pair->name ?? 'N/A' }}</span>
                                                </div>
                                                <div class="right">
                                                    <p class="text-uppercase">{{ __('ID') }}: {{ $signal->id }}</p>
                                                </div>
                                            </div>
                                            <div class="singnal-card-body">
                                                <h5 class="title">{{ $signal->title }}</h5>
                                                <p class="text-muted small">{{ __('Auto-created from channel') }}: {{ $signal->channelSource->name ?? 'N/A' }}</p>
                                                <div class="d-flex gap-2 mt-3">
                                                    <a href="{{ route('admin.signals.edit', $signal->id) }}" 
                                                       class="btn btn-sm btn-primary" 
                                                       target="_blank">
                                                        <i class="las la-edit"></i> {{ __('Review') }}
                                                    </a>
                                                    <a href="{{ route('user.signal.details', ['id' => $signal->id, 'slug' => Str::slug($signal->title)]) }}" 
                                                       class="btn btn-sm btn-outline-info">
                                                        <i class="las la-eye"></i> {{ __('View') }}
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                                @if ($reviewSignals->hasPages())
                                    <div class="mt-3">
                                        {{ $reviewSignals->links() }}
                                    </div>
                                @endif
                            @else
                                <div class="text-center py-5">
                                    <i class="las la-clipboard-check la-3x text-muted mb-3"></i>
                                    <p class="text-muted">{{ __('No draft signals pending review.') }}</p>
                                </div>
                            @endif
                        </div>

                        <!-- Pattern Templates Tab -->
                        <div class="tab-pane fade {{ $activeTab === 'pattern-templates' ? 'show active' : '' }}" 
                             id="pattern-templates" 
                             role="tabpanel">
                            @if(isset($patterns) && $patterns->count() > 0)
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="mb-0">{{ __('My Pattern Templates') }}</h5>
                                    <a href="{{ route('admin.pattern-templates.create') }}" class="btn sp_theme_btn" target="_blank">
                                        <i class="las la-plus"></i> {{ __('Create Pattern') }}
                                    </a>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Name') }}</th>
                                                <th>{{ __('Type') }}</th>
                                                <th>{{ __('Pattern') }}</th>
                                                <th>{{ __('Created') }}</th>
                                                <th class="text-end">{{ __('Actions') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($patterns as $pattern)
                                            <tr>
                                                <td><strong>{{ $pattern->name }}</strong></td>
                                                <td><span class="badge bg-info">{{ ucfirst($pattern->type) }}</span></td>
                                                <td><code class="small">{{ Str::limit($pattern->pattern, 50) }}</code></td>
                                                <td>{{ $pattern->created_at->diffForHumans() }}</td>
                                                <td class="text-end">
                                                    <a href="{{ route('admin.pattern-templates.edit', $pattern->id) }}" 
                                                       class="btn btn-xs btn-outline-primary" 
                                                       target="_blank">
                                                        <i class="las la-edit"></i> {{ __('Edit') }}
                                                    </a>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                @if ($patterns->hasPages())
                                    <div class="mt-3">
                                        {{ $patterns->links() }}
                                    </div>
                                @endif
                            @else
                                <div class="text-center py-5">
                                    <i class="las la-code la-3x text-muted mb-3"></i>
                                    <p class="text-muted">{{ __('No pattern templates found.') }}</p>
                                    <a href="{{ route('admin.pattern-templates.create') }}" class="btn sp_theme_btn mt-2" target="_blank">
                                        <i class="las la-plus"></i> {{ __('Create First Pattern') }}
                                    </a>
                                </div>
                            @endif
                        </div>

                        <!-- Analytics Tab -->
                        <div class="tab-pane fade {{ $activeTab === 'analytics' ? 'show active' : '' }}" 
                             id="analytics" 
                             role="tabpanel">
                            @if(isset($analytics))
                                <div class="row g-3 mb-4">
                                    <div class="col-md-3">
                                        <div class="sp_site_card text-center">
                                            <h5 class="mb-1">{{ __('Total Signals') }}</h5>
                                            <span class="fw-semibold fs-4">{{ $analytics['total_signals'] ?? 0 }}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="sp_site_card text-center">
                                            <h5 class="mb-1 text-success">{{ __('Published') }}</h5>
                                            <span class="fw-semibold fs-4 text-success">{{ $analytics['published_signals'] ?? 0 }}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="sp_site_card text-center">
                                            <h5 class="mb-1 text-warning">{{ __('Draft') }}</h5>
                                            <span class="fw-semibold fs-4 text-warning">{{ $analytics['draft_signals'] ?? 0 }}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="sp_site_card text-center">
                                            <h5 class="mb-1 text-info">{{ __('Active Sources') }}</h5>
                                            <span class="fw-semibold fs-4 text-info">{{ $analytics['active_sources'] ?? 0 }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="alert alert-info">
                                    <i class="las la-info-circle"></i>
                                    {{ __('Detailed analytics coming soon.') }}
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <i class="las la-chart-bar la-3x text-muted mb-3"></i>
                                    <p class="text-muted">{{ __('Analytics data not available.') }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

    @push('script')
    <script>
        $(function() {
            'use strict'
            
            // Handle tab switching via URL parameter
            const urlParams = new URLSearchParams(window.location.search);
            const tabParam = urlParams.get('tab');
            
            if (tabParam) {
                const tabLink = $('#multiChannelTabs a[href="#' + tabParam + '"]');
                if (tabLink.length) {
                    const tab = new bootstrap.Tab(tabLink[0]);
                    tab.show();
                }
            }
            // Function to switch tabs and update URL
            function switchTab(tabName) {
                const url = new URL(window.location);
                url.searchParams.set('tab', tabName);
                window.location.href = url.toString();
            }
            
            // Make switchTab available globally
            window.switchTab = switchTab;
        });
    </script>
    @endpush
@endsection

