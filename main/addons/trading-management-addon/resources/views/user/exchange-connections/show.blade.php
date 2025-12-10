@extends(Config::theme() . 'layout.auth')

@section('title')
    {{ $title }}
@endsection

@section('content')
<div class="sp_site_card">
    <div class="card-header">
        <div class="d-flex flex-wrap align-items-center justify-content-between">
            <h4>{{ __($title) }}</h4>
            <a href="{{ route('user.trading.operations.index', ['tab' => 'connections']) }}" class="btn btn-sm btn-secondary">
                <i class="fa fa-arrow-left"></i> {{ __('Back to Connections') }}
            </a>
        </div>
    </div>
    <div class="card-body">
        <!-- Connection Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="mb-1">{{ $connection->name }}</h5>
                        <p class="text-muted mb-0">{{ $connection->exchange_name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        @php
                            $statusClass = 'bg-secondary';
                            $statusText = __('Inactive');
                            if ($connection->is_active && $connection->status === 'active') {
                                $statusClass = 'bg-success';
                                $statusText = __('Active');
                            } elseif ($connection->status === 'error') {
                                $statusClass = 'bg-danger';
                                $statusText = __('Error');
                            } elseif ($connection->status === 'testing') {
                                $statusClass = 'bg-info';
                                $statusText = __('Testing');
                            }
                        @endphp
                        <span class="badge {{ $statusClass }} badge-lg">
                            {{ $statusText }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Connection Details -->
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card border">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fa fa-info-circle"></i> {{ __('Connection Information') }}</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <strong><i class="fa fa-tag"></i> {{ __('Type') }}:</strong> 
                            <span class="ml-2">
                                @if($connection->connection_type === 'FX_BROKER')
                                    {{ __('FX Broker') }}
                                @elseif($connection->connection_type === 'CRYPTO_EXCHANGE')
                                    {{ __('Crypto Exchange') }}
                                @else
                                    {{ ucfirst($connection->exchange_type ?? 'N/A') }}
                                @endif
                            </span>
                        </div>
                        <div class="mb-3">
                            <strong><i class="fa fa-server"></i> {{ __('Provider') }}:</strong> 
                            <span class="ml-2 badge bg-info">{{ strtoupper($connection->provider ?? $connection->exchange_name ?? 'N/A') }}</span>
                        </div>
                        <div class="mb-3">
                            <strong><i class="fa fa-bullseye"></i> {{ __('Purpose') }}:</strong> 
                            <span class="ml-2">
                                @if($connection->data_fetching_enabled && $connection->trade_execution_enabled)
                                    <span class="badge bg-primary">{{ __('Data & Execution') }}</span>
                                @elseif($connection->data_fetching_enabled)
                                    <span class="badge bg-info">{{ __('Data Only') }}</span>
                                @elseif($connection->trade_execution_enabled)
                                    <span class="badge bg-success">{{ __('Execution Only') }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ __('Not Configured') }}</span>
                                @endif
                            </span>
                        </div>
                        <div class="mb-3">
                            <strong><i class="fa fa-sliders-h"></i> {{ __('Trading Preset') }}:</strong> 
                            <span class="ml-2">{{ $connection->preset->name ?? __('None') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card border">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fa fa-chart-line"></i> {{ __('Status & Activity') }}</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <strong><i class="fa fa-download"></i> {{ __('Data Fetching') }}:</strong> 
                            <span class="ml-2">
                                @if($connection->data_fetching_enabled && $connection->is_active)
                                    <i class="fa fa-check-circle text-success"></i> {{ __('Enabled') }}
                                @else
                                    <i class="fa fa-times-circle text-muted"></i> {{ __('Disabled') }}
                                @endif
                                @if($connection->last_data_fetch_at)
                                    <br><small class="text-muted">({{ __('Last') }}: {{ $connection->last_data_fetch_at->diffForHumans() }})</small>
                                @endif
                            </span>
                        </div>
                        <div class="mb-3">
                            <strong><i class="fa fa-bolt"></i> {{ __('Trade Execution') }}:</strong> 
                            <span class="ml-2">
                                @if($connection->trade_execution_enabled && $connection->is_active)
                                    <i class="fa fa-check-circle text-success"></i> {{ __('Enabled') }}
                                @else
                                    <i class="fa fa-times-circle text-muted"></i> {{ __('Disabled') }}
                                @endif
                                @if($connection->last_trade_execution_at)
                                    <br><small class="text-muted">({{ __('Last') }}: {{ $connection->last_trade_execution_at->diffForHumans() }})</small>
                                @endif
                            </span>
                        </div>
                        <div class="mb-3">
                            <strong><i class="fa fa-users"></i> {{ __('Copy Trading') }}:</strong> 
                            <span class="ml-2 badge {{ $connection->copy_trading_enabled ? 'bg-success' : 'bg-secondary' }}">
                                {{ $connection->copy_trading_enabled ? __('Enabled') : __('Disabled') }}
                            </span>
                        </div>
                        @if($connection->last_used_at)
                        <div class="mb-3">
                            <strong><i class="fa fa-clock"></i> {{ __('Last Used') }}:</strong> 
                            <span class="ml-2">{{ $connection->last_used_at->diffForHumans() }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Error Message (if any) -->
        @if($connection->last_error)
        <div class="alert alert-warning">
            <i class="fa fa-exclamation-triangle"></i> 
            <strong>{{ __('Last Error') }}:</strong> {{ $connection->last_error }}
            @if($connection->last_tested_at)
                <br><small class="text-muted">{{ __('Tested') }}: {{ $connection->last_tested_at->diffForHumans() }}</small>
            @endif
        </div>
        @endif

        <!-- Action Buttons -->
        <div class="d-flex flex-wrap gap-2 mt-4">
            @php
                $editRoute = null;
                if (Route::has('user.exchange-connections.edit')) {
                    $editRoute = route('user.exchange-connections.edit', $connection->id);
                } elseif (Route::has('user.execution-connections.edit')) {
                    $editRoute = route('user.execution-connections.edit', $connection->id);
                }
            @endphp
            @if($editRoute)
                <a href="{{ $editRoute }}" class="btn btn-primary btn-sm">
                    <i class="fa fa-edit"></i> {{ __('Edit Connection') }}
                </a>
            @endif
            
            <button type="button" class="btn btn-info btn-sm" id="testConnectionBtn" data-connection-id="{{ $connection->id }}">
                <i class="fa fa-vial"></i> {{ __('Test Connection') }}
            </button>
        </div>
    </div>
</div>

@push('script')
<script>
    $(document).ready(function() {
        // Test connection button
        $('#testConnectionBtn').on('click', function() {
            const btn = $(this);
            const connectionId = btn.data('connection-id');
            
            btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> {{ __('Testing...') }}');
            
            $.ajax({
                url: '{{ route("user.exchange-connections.test", $connection->id) }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        alert('{{ __("Connection test successful!") }}');
                    } else {
                        alert('{{ __("Connection test failed:") }} ' + (response.message || '{{ __("Unknown error") }}'));
                    }
                },
                error: function(xhr) {
                    const errorMsg = xhr.responseJSON?.message || '{{ __("Failed to test connection") }}';
                    alert('{{ __("Error:") }} ' + errorMsg);
                },
                complete: function() {
                    btn.prop('disabled', false).html('<i class="fa fa-vial"></i> {{ __("Test Connection") }}');
                }
            });
        });
    });
</script>
@endpush
@endsection


