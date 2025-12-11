@extends('backend.layout.master')

@section('title', 'Cache Management')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Cache Management</h4>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary btn-sm" onclick="warmCache()">
                            <i class="fas fa-fire"></i> Warm Cache
                        </button>
                        <button type="button" class="btn btn-warning btn-sm" onclick="clearAllCache()">
                            <i class="fas fa-trash"></i> Clear All Cache
                        </button>
                        <button type="button" class="btn btn-info btn-sm" onclick="refreshStats()">
                            <i class="fas fa-sync"></i> Refresh Stats
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Cache Statistics -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h5>Cache Hits</h5>
                                    <h3 id="cache-hits">{{ number_format($cacheStats['hits']) }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h5>Cache Misses</h5>
                                    <h3 id="cache-misses">{{ number_format($cacheStats['misses']) }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h5>Hit Rate</h5>
                                    <h3 id="hit-rate">{{ round($cacheStats['hit_rate'], 2) }}%</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <h5>Total Keys</h5>
                                    <h3 id="total-keys">{{ number_format($cacheSize['keys_count'] ?? 0) }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Memory Statistics -->
                    @if(!empty($memoryStats))
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5>Memory Usage</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-body">
                                            <h6>Used Memory</h6>
                                            <p id="used-memory">{{ $memoryStats['used_memory_human'] ?? 'N/A' }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-body">
                                            <h6>Peak Memory</h6>
                                            <p id="peak-memory">{{ $memoryStats['used_memory_peak_human'] ?? 'N/A' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Cache Tag Management -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5>Clear Cache by Tags</h5>
                            <div class="form-group">
                                <label>Select Tags to Clear:</label>
                                <div class="row">
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-outline-danger btn-sm btn-block" onclick="clearCacheByTag('signals')">
                                            Signals
                                        </button>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-outline-danger btn-sm btn-block" onclick="clearCacheByTag('plans')">
                                            Plans
                                        </button>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-outline-danger btn-sm btn-block" onclick="clearCacheByTag('configuration')">
                                            Configuration
                                        </button>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-outline-danger btn-sm btn-block" onclick="clearCacheByTag('markets')">
                                            Markets
                                        </button>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-outline-danger btn-sm btn-block" onclick="clearCacheByTag('user_subscriptions')">
                                            Subscriptions
                                        </button>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-outline-danger btn-sm btn-block" onclick="clearCacheByTag('dashboard_signals')">
                                            Dashboard
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Database Metrics -->
                    @if(!empty($dbMetrics))
                    <div class="row">
                        <div class="col-12">
                            <h5>Database Performance</h5>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-body">
                                            <h6>Active Connections</h6>
                                            <p>{{ $dbMetrics['active_connections'] ?? 0 }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-body">
                                            <h6>Threads Connected</h6>
                                            <p>{{ $dbMetrics['threads_connected'] ?? 0 }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-body">
                                            <h6>Slow Queries</h6>
                                            <p>{{ $dbMetrics['slow_queries'] ?? 0 }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
function warmCache() {
    $.ajax({
        url: '{{ route("admin.cache.warm") }}',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.type === 'success') {
                toastr.success(response.message);
                refreshStats();
            } else {
                toastr.error(response.message);
            }
        },
        error: function(xhr) {
            toastr.error('Failed to warm cache');
        }
    });
}

function clearAllCache() {
    if (confirm('Are you sure you want to clear all cache? This action cannot be undone.')) {
        $.ajax({
            url: '{{ route("admin.cache.clear-all") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.type === 'success') {
                    toastr.success(response.message);
                    refreshStats();
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                toastr.error('Failed to clear cache');
            }
        });
    }
}

function clearCacheByTag(tag) {
    $.ajax({
        url: '{{ route("admin.cache.clear-tags") }}',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            tags: [tag]
        },
        success: function(response) {
            if (response.type === 'success') {
                toastr.success(response.message);
                refreshStats();
            } else {
                toastr.error(response.message);
            }
        },
        error: function(xhr) {
            toastr.error('Failed to clear cache for tag: ' + tag);
        }
    });
}

function refreshStats() {
    $.ajax({
        url: '{{ route("admin.cache.stats") }}',
        method: 'GET',
        success: function(response) {
            if (response.type === 'success') {
                const data = response.data;
                
                // Update cache stats
                $('#cache-hits').text(new Intl.NumberFormat().format(data.cache.hits));
                $('#cache-misses').text(new Intl.NumberFormat().format(data.cache.misses));
                $('#hit-rate').text(Math.round(data.cache.hit_rate * 100) / 100 + '%');
                $('#total-keys').text(new Intl.NumberFormat().format(data.size.keys_count || 0));
                
                // Update memory stats if available
                if (data.memory.used_memory_human) {
                    $('#used-memory').text(data.memory.used_memory_human);
                }
                if (data.memory.used_memory_peak_human) {
                    $('#peak-memory').text(data.memory.used_memory_peak_human);
                }
                
                toastr.success('Statistics refreshed');
            }
        },
        error: function(xhr) {
            toastr.error('Failed to refresh statistics');
        }
    });
}

// Auto-refresh stats every 30 seconds
setInterval(refreshStats, 30000);
</script>
@endpush