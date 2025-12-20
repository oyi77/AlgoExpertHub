@extends(Config::theme() . 'layout.auth')

@section('title')
    {{ $title }}
@endsection

@push('styles')
<style>
/* Robust Toggle Switch Styling */
.custom-switch {
    padding-left: 2.25rem;
    position: relative;
    display: inline-block;
}

.custom-switch .custom-control-input {
    position: absolute;
    left: 0;
    z-index: -1;
    width: 1rem;
    height: 1.25rem;
    opacity: 0;
}

.custom-switch .custom-control-label {
    position: relative;
    margin-bottom: 0;
    vertical-align: top;
    cursor: pointer;
    display: block;
}

.custom-switch .custom-control-label::before {
    content: "";
    position: absolute;
    left: -2.25rem;
    display: block;
    width: 2.15rem;
    height: 1.15rem;
    pointer-events: all;
    background-color: #374151; /* Dark background */
    border-radius: 0.75rem;
    transition: background-color .15s ease-in-out,border-color .15s ease-in-out,box-shadow .15s ease-in-out;
}

.custom-switch .custom-control-label::after {
    content: "";
    position: absolute;
    top: calc(0.125rem + 1px);
    left: calc(-2.25rem + 2px);
    width: calc(1rem - 4px);
    height: calc(1rem - 4px);
    background-color: #fff;
    border-radius: 50%;
    transition: transform 0.15s ease-in-out,background-color .15s ease-in-out,border-color .15s ease-in-out,box-shadow .15s ease-in-out;
}

.custom-control-input:checked ~ .custom-control-label::before {
    color: #fff;
    border-color: #1AFFD5;
    background-color: #1AFFD5;
}

.custom-control-input:checked ~ .custom-control-label::after {
    background-color: #fff;
    transform: translateX(1rem);
}

.custom-switch .custom-control-input:disabled ~ .custom-control-label::before {
    background-color: #e9ecef;
}

/* Ensure label doesn't overlap if text is present */
.custom-switch .custom-control-label {
    min-height: 1.15rem;
}
</style>
@endpush

@section('content')
<div class="sp_site_card">
    <div class="card-header">
        <div class="d-flex flex-wrap align-items-center justify-content-between">
            <h4>{{ __($title) }}</h4>
            <a href="{{ route('user.trading.configuration.index', ['tab' => 'data-connections']) }}" class="btn btn-sm btn-secondary">
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
                        <span id="overallConnectionStatus">
                            <span class="badge {{ $statusClass }} badge-lg">
                                {{ $statusText }}
                            </span>
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
                            <span class="ml-2" id="purposeStatusBadge">
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
                            <span class="ml-2" id="dataFetchingStatus">
                                @if($connection->data_fetching_enabled)
                                    @if($connection->is_active)
                                        <i class="fa fa-check-circle text-success"></i> {{ __('Active') }}
                                    @else
                                        <i class="fa fa-check-circle text-warning"></i> {{ __('Enabled (Inactive)') }}
                                    @endif
                                @else
                                    <i class="fa fa-times-circle text-muted"></i> {{ __('Disabled') }}
                                @endif
                            </span>
                            @if($connection->last_data_fetch_at)
                                <br><small class="text-muted">({{ __('Last') }}: {{ $connection->last_data_fetch_at->diffForHumans() }})</small>
                            @endif
                        </div>
                        <div class="mb-3">
                            <strong><i class="fa fa-bolt"></i> {{ __('Trade Execution') }}:</strong> 
                            <span class="ml-2" id="tradeExecutionStatus">
                                @if($connection->trade_execution_enabled)
                                    @if($connection->is_active)
                                        <i class="fa fa-check-circle text-success"></i> {{ __('Active') }}
                                    @else
                                        <i class="fa fa-check-circle text-warning"></i> {{ __('Enabled (Inactive)') }}
                                    @endif
                                @else
                                    <i class="fa fa-times-circle text-muted"></i> {{ __('Disabled') }}
                                @endif
                            </span>
                            @if($connection->last_trade_execution_at)
                                <br><small class="text-muted">({{ __('Last') }}: {{ $connection->last_trade_execution_at->diffForHumans() }})</small>
                            @endif
                        </div>
                        <div class="mb-3">
                            <strong><i class="fa fa-users"></i> {{ __('Copy Trading') }}:</strong> 
                            <span class="ml-2" id="copyTradingStatus">
                                <span class="badge {{ $connection->copy_trading_enabled ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $connection->copy_trading_enabled ? __('Enabled') : __('Disabled') }}
                                </span>
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

        <!-- Connection Controls -->
        <div class="row mt-4">
            <div class="col-md-6 mb-3">
                <div class="card border">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fa fa-toggle-on"></i> {{ __('Connection Config') }}</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <strong>{{ __('Activation') }}</strong>
                                <br><small class="text-muted">{{ __('Enable or disable this connection') }}</small>
                            </div>
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="activationToggle" 
                                       {{ $connection->is_active ? 'checked' : '' }}>
                                <label class="custom-control-label" for="activationToggle"></label>
                            </div>
                        </div>
                        
                        @if(auth()->user()->currentplan()->exists())
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>{{ __('Copy Trading') }}</strong>
                                <br><small class="text-muted">{{ __('Auto-copy signals to this connection') }}</small>
                            </div>
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="copyTradingToggle" 
                                       {{ $connection->copy_trading_enabled ? 'checked' : '' }}>
                                <label class="custom-control-label" for="copyTradingToggle"></label>
                            </div>
                        </div>
                        @else
                        <div class="alert alert-info mb-0">
                            <i class="fa fa-info-circle"></i> {{ __('Subscribe to a plan to enable copy trading') }}
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-3">
                <div class="card border">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fa fa-cog"></i> {{ __('Connection Purpose') }}</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <strong>{{ __('Data Fetching') }}</strong>
                                <br><small class="text-muted">{{ __('Fetch market data and account info') }}</small>
                            </div>
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="dataFetchingToggle" 
                                       {{ $connection->data_fetching_enabled ? 'checked' : '' }}>
                                <label class="custom-control-label" for="dataFetchingToggle"></label>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>{{ __('Trade Execution') }}</strong>
                                <br><small class="text-muted">{{ __('Execute trades on this connection') }}</small>
                            </div>
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="tradeExecutionToggle" 
                                       {{ $connection->trade_execution_enabled ? 'checked' : '' }}>
                                <label class="custom-control-label" for="tradeExecutionToggle"></label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Action Buttons -->
        <div class="d-flex flex-wrap gap-2 mt-4">
            @php
                $editRoute = null;
                if (Route::has('user.exchange-connections.edit')) {
                    $editRoute = route('user.exchange-connections.edit', $connection->id);
                } elseif (Route::has('user.execution-connections.edit')) {
                    $editRoute = route('user.execution-connections.edit', $connection->id);
                }
                
                $deleteRoute = null;
                if (Route::has('user.exchange-connections.destroy')) {
                    $deleteRoute = route('user.exchange-connections.destroy', $connection->id);
                }
            @endphp
            @if($editRoute)
                <a href="{{ $editRoute }}" class="btn btn-primary btn-sm">
                    <i class="las la-edit"></i> {{ __('Edit Connection') }}
                </a>
            @endif
            
            <button type="button" class="btn btn-outline-info btn-sm" id="testConnectionBtn" data-connection-id="{{ $connection->id }}">
                <i class="las la-vial"></i> {{ __('Test Connection') }}
            </button>

            @if($deleteRoute)
                <form action="{{ $deleteRoute }}" method="POST" id="deleteConnectionForm" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('{{ __('Are you sure you want to delete this connection? This action cannot be undone.') }}')">
                        <i class="las la-trash"></i> {{ __('Delete Connection') }}
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        // Test connection
        $('#testConnectionBtn').on('click', function() {
            const btn = $(this);
            btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> {{ __('Testing...') }}');
            
            $.ajax({
                url: '{{ route("user.exchange-connections.test", $connection->id) }}',
                method: 'POST',
                data: { _token: '{{ csrf_token() }}' },
                success: function(response) {
                    alert(response.success ? '{{ __("Connection test successful!") }}' : response.message);
                },
                error: function(xhr) {
                    alert('{{ __("Error:") }} ' + (xhr.responseJSON?.message || '{{ __("Failed to test connection") }}'));
                },
                complete: function() {
                    btn.prop('disabled', false).html('<i class="fa fa-vial"></i> {{ __("Test Connection") }}');
                }
            });
        });
        
        // Helper to update UI in real-time
        function updateStatusUI() {
            const isActive = $('#activationToggle').is(':checked');
            const dataEnabled = $('#dataFetchingToggle').is(':checked');
            const tradeEnabled = $('#tradeExecutionToggle').is(':checked');
            const copyEnabled = $('#copyTradingToggle').is(':checked');

            // Update Overall Status Badge
            const statusBadge = $('#overallConnectionStatus');
            if (isActive) {
                statusBadge.html('<span class="badge bg-success badge-lg">{{ __("Active") }}</span>');
            } else {
                statusBadge.html('<span class="badge bg-secondary badge-lg">{{ __("Inactive") }}</span>');
            }

            // Update Purpose Badge
            const purposeBadge = $('#purposeStatusBadge');
            if (dataEnabled && tradeEnabled) {
                purposeBadge.html('<span class="badge bg-primary">{{ __("Data & Execution") }}</span>');
            } else if (dataEnabled) {
                purposeBadge.html('<span class="badge bg-info">{{ __("Data Only") }}</span>');
            } else if (tradeEnabled) {
                purposeBadge.html('<span class="badge bg-success">{{ __("Execution Only") }}</span>');
            } else {
                purposeBadge.html('<span class="badge bg-secondary">{{ __("Not Configured") }}</span>');
            }

            // Update Data Fetching Status
            const dataStatus = $('#dataFetchingStatus');
            if (dataEnabled) {
                if (isActive) {
                    dataStatus.html('<i class="fa fa-check-circle text-success"></i> {{ __("Active") }}');
                } else {
                    dataStatus.html('<i class="fa fa-check-circle text-warning"></i> {{ __("Enabled (Inactive)") }}');
                }
            } else {
                dataStatus.html('<i class="fa fa-times-circle text-muted"></i> {{ __("Disabled") }}');
            }

            // Update Trade Execution Status
            const tradeStatus = $('#tradeExecutionStatus');
            if (tradeEnabled) {
                if (isActive) {
                    tradeStatus.html('<i class="fa fa-check-circle text-success"></i> {{ __("Active") }}');
                } else {
                    tradeStatus.html('<i class="fa fa-check-circle text-warning"></i> {{ __("Enabled (Inactive)") }}');
                }
            } else {
                tradeStatus.html('<i class="fa fa-times-circle text-muted"></i> {{ __("Disabled") }}');
            }

            // Update Copy Trading Status
            const copyStatusBadge = $('#copyTradingStatus');
            if (copyEnabled) {
                copyStatusBadge.html('<span class="badge bg-success">{{ __("Enabled") }}</span>');
            } else {
                copyStatusBadge.html('<span class="badge bg-secondary">{{ __("Disabled") }}</span>');
            }
        }

        // Activation toggle
        $('#activationToggle').on('change', function() {
            const isChecked = $(this).is(':checked');
            $.ajax({
                url: '{{ route("user.exchange-connections.toggle-activation", $connection->id) }}',
                method: 'POST',
                data: { _token: '{{ csrf_token() }}' },
                success: function(response) {
                    if (response.success) {
                        (typeof toastr !== 'undefined' ? toastr.success : alert)(response.message);
                        updateStatusUI();
                    }
                },
                error: function(xhr) {
                    $('#activationToggle').prop('checked', !isChecked);
                    (typeof toastr !== 'undefined' ? toastr.error : alert)(xhr.responseJSON?.message || 'Error');
                }
            });
        });
        
        // Copy trading toggle
        $('#copyTradingToggle').on('change', function() {
            const isChecked = $(this).is(':checked');
            $.ajax({
                url: '{{ route("user.exchange-connections.toggle-copy-trading", $connection->id) }}',
                method: 'POST',
                data: { _token: '{{ csrf_token() }}' },
                success: function(response) {
                    if (response.success) {
                        (typeof toastr !== 'undefined' ? toastr.success : alert)(response.message);
                        updateStatusUI();
                    }
                },
                error: function(xhr) {
                    $('#copyTradingToggle').prop('checked', !isChecked);
                    (typeof toastr !== 'undefined' ? toastr.error : alert)(xhr.responseJSON?.message || 'Error');
                }
            });
        });
        
        // Data fetching toggle
        $('#dataFetchingToggle').on('change', function() {
            const isChecked = $(this).is(':checked');
            $.ajax({
                url: '{{ route("user.exchange-connections.update-purpose", $connection->id) }}',
                method: 'POST',
                data: { _token: '{{ csrf_token() }}', data_fetching_enabled: isChecked },
                success: function(response) {
                    if (response.success) {
                        (typeof toastr !== 'undefined' ? toastr.success : alert)(response.message);
                        updateStatusUI();
                    }
                },
                error: function(xhr) {
                    $('#dataFetchingToggle').prop('checked', !isChecked);
                    (typeof toastr !== 'undefined' ? toastr.error : alert)(xhr.responseJSON?.message || 'Error');
                }
            });
        });
        
        // Trade execution toggle
        $('#tradeExecutionToggle').on('change', function() {
            const isChecked = $(this).is(':checked');
            $.ajax({
                url: '{{ route("user.exchange-connections.update-purpose", $connection->id) }}',
                method: 'POST',
                data: { _token: '{{ csrf_token() }}', trade_execution_enabled: isChecked },
                success: function(response) {
                    if (response.success) {
                        (typeof toastr !== 'undefined' ? toastr.success : alert)(response.message);
                        updateStatusUI();
                    }
                },
                error: function(xhr) {
                    $('#tradeExecutionToggle').prop('checked', !isChecked);
                    (typeof toastr !== 'undefined' ? toastr.error : alert)(xhr.responseJSON?.message || 'Error');
                }
            });
        });
    });
</script>
@endpush
@endsection


