@extends('trading-management::backend.trading-management.layout')

@section('submenu-content')
<div class="card">
    <div class="card-header">
        <h4>Edit Execution Connection</h4>
        <div class="card-header-action">
            <a href="{{ route('admin.trading-management.operations.connections.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.trading-management.operations.connections.update', $connection) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="name">Connection Name</label>
                <input type="text" class="form-control" id="name" name="name" value="{{ $connection->name }}" required>
            </div>

            <div class="form-group">
                <label for="type">Connection Type</label>
                <select class="form-control" id="type" name="type" required>
                    <option value="crypto" {{ $connection->type === 'crypto' ? 'selected' : '' }}>Crypto Exchange</option>
                    <option value="fx" {{ $connection->type === 'fx' ? 'selected' : '' }}>FX Broker</option>
                </select>
            </div>

            <div class="form-group">
                <label for="exchange_name">Exchange/Broker Name</label>
                <input type="text" class="form-control" id="exchange_name" name="exchange_name" value="{{ $connection->exchange_name }}" required>
            </div>

            <div class="form-group">
                <label>API Credentials (JSON)</label>
                <textarea class="form-control" name="credentials[raw]" rows="5">{{ json_encode($connection->credentials, JSON_PRETTY_PRINT) }}</textarea>
            </div>

            <div class="form-group">
                <label for="preset_id">Risk Preset</label>
                <select class="form-control" id="preset_id" name="preset_id">
                    <option value="">Select preset</option>
                    @foreach($presets as $preset)
                        <option value="{{ $preset->id }}" {{ $connection->preset_id == $preset->id ? 'selected' : '' }}>{{ $preset->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="data_connection_id">Data Connection</label>
                <select class="form-control" id="data_connection_id" name="data_connection_id">
                    <option value="">Select data connection</option>
                    @foreach($dataConnections as $dc)
                        <option value="{{ $dc->id }}" {{ $connection->data_connection_id == $dc->id ? 'selected' : '' }}>{{ $dc->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Connection
                </button>
            </div>
        </form>
    </div>
</div>

@if($connection->circuit_breaker_enabled)
    @php
        $status = 'Active';
        $statusClass = 'bg-green-100 text-green-800';
        if ($connection->consecutive_failures >= $connection->max_consecutive_failures) {
            $status = 'Tripped';
            $statusClass = 'bg-red-100 text-red-800';
        }

        $cooldownStatus = 'COOLDOWN EXPIRED';
        $cooldownMinutes = null;
        if ($connection->last_failure_at && $connection->last_failure_at->diffInMinutes(now()) < 15) {
            $cooldownStatus = 'IN COOLDOWN';
            $cooldownMinutes = 15 - $connection->last_failure_at->diffInMinutes(now());
        }
    @endphp

    <div class="card mt-4">
        <div class="card-header">
            <h4>Circuit Breaker Status</h4>
        </div>
        <div class="card-body">
            <div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
                <div class="row">
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between items-center mb-3">
                            <span class="text-gray-600">Status:</span>
                            <span class="px-3 py-1 rounded-full text-xs font-medium {{ $statusClass }}">
                                {{ $status }}
                            </span>
                        </div>
                        <div class="d-flex justify-content-between items-center mb-3">
                            <span class="text-gray-600">Consecutive Failures:</span>
                            <span class="font-medium text-gray-900">{{ $connection->consecutive_failures }} / {{ $connection->max_consecutive_failures }}</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between items-center mb-3">
                            <span class="text-gray-600">Last Failure:</span>
                            <span class="font-medium text-gray-900">{{ $connection->last_failure_at?->format('Y-m-d H:i:s') ?? 'Never' }}</span>
                        </div>
                        @if($status === 'Tripped')
                            <div class="d-flex justify-content-between items-center mb-3">
                                <span class="text-gray-600">Cooldown:</span>
                                @if($cooldownStatus === 'IN COOLDOWN')
                                    <span class="font-medium text-yellow-600">{{ $cooldownStatus }} ({{ $cooldownMinutes }}m remaining)</span>
                                @else
                                    <span class="font-medium text-green-600">{{ $cooldownStatus }}</span>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                <div class="mt-4">
                    <button type="button" class="btn btn-warning text-white font-medium reset-circuit-breaker-btn" data-url="{{ route('admin.trading-management.operations.connections.reset-circuit-breaker', $connection->id) }}">
                        <i class="fas fa-sync-alt mr-1"></i> Reset Circuit Breaker
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    $(document).ready(function() {
        $('.reset-circuit-breaker-btn').click(function() {
            if(!confirm('Are you sure you want to reset the circuit breaker? This will clear failure counts.')) return;
            
            var btn = $(this);
            var originalText = btn.html();
            btn.html('<i class="fas fa-spinner fa-spin"></i> Resetting...').prop('disabled', true);
            
            $.ajax({
                url: btn.data('url'),
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if(response.type === 'success' || response.success) {
                         alert('✓ ' + (response.message || 'Circuit breaker reset successfully'));
                         location.reload();
                    } else {
                         alert('✗ ' + (response.message || 'Reset failed'));
                         btn.html(originalText).prop('disabled', false);
                    }
                },
                error: function(xhr) {
                    var message = xhr.responseJSON ? xhr.responseJSON.message : 'Reset failed';
                    alert('✗ ' + message);
                    btn.html(originalText).prop('disabled', false);
                }
            });
        });
    });
    </script>
    @endpush
@endif
@endsection

