@extends('backend.layout.master')

@section('element')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0"><i class="fas fa-cog"></i> Global Settings</h4>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> <strong>Global Configuration</strong> - These settings are shared across all MTAPI connections. Only admins can modify these settings.
                </div>

                <form action="{{ route('admin.trading-management.config.global-settings.update') }}" method="POST" id="globalSettingsForm">
                    @csrf

                    <!-- MTAPI gRPC Configuration -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-server"></i> MTAPI gRPC Configuration</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> <strong>Important:</strong> Configure these credentials once. All MTAPI connections will use these global settings.
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>API Key</label>
                                        <input type="password" name="api_key" class="form-control" value="{{ $config['api_key'] ?? '' }}" placeholder="Leave blank to keep existing">
                                        <small class="text-muted">MTAPI.io API key (optional, for future use)</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Base URL <span class="text-danger">*</span></label>
                                        <input type="text" name="base_url" class="form-control" value="{{ $config['base_url'] ?? 'mt5grpc.mtapi.io:443' }}" required>
                                        <small class="text-muted">MTAPI gRPC server endpoint</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Timeout (seconds) <span class="text-danger">*</span></label>
                                        <input type="number" name="timeout" class="form-control" value="{{ $config['timeout'] ?? 30 }}" min="5" max="300" required>
                                        <small class="text-muted">Connection timeout in seconds</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Default Host</label>
                                        <input type="text" name="default_host" class="form-control" value="{{ $config['default_host'] ?? '78.140.180.198' }}">
                                        <small class="text-muted">Default MT5 server host</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Default Port</label>
                                        <input type="number" name="default_port" class="form-control" value="{{ $config['default_port'] ?? 443 }}" min="1" max="65535">
                                        <small class="text-muted">Default MT5 server port</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Demo Account Configuration -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-vial"></i> Demo Account Configuration</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> Configure demo account credentials for testing connections. These are used for connection testing only.
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Demo User</label>
                                        <input type="text" name="demo_user" class="form-control" value="{{ $config['demo_account']['user'] ?? '62333850' }}">
                                        <small class="text-muted">MT5 demo account number</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Demo Password</label>
                                        <input type="password" name="demo_password" class="form-control" value="{{ $config['demo_account']['password'] ?? 'tecimil4' }}">
                                        <small class="text-muted">MT5 demo account password</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Demo Host</label>
                                        <input type="text" name="demo_host" class="form-control" value="{{ $config['demo_account']['host'] ?? '78.140.180.198' }}">
                                        <small class="text-muted">MT5 demo server host</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Demo Port</label>
                                        <input type="number" name="demo_port" class="form-control" value="{{ $config['demo_account']['port'] ?? 443 }}" min="1" max="65535">
                                        <small class="text-muted">MT5 demo server port</small>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <button type="button" class="btn btn-info" id="testDemoConnection">
                                    <i class="fas fa-plug"></i> Test Demo Connection
                                </button>
                                <div id="testResult" class="mt-3"></div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> Save Global Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const testBtn = document.getElementById('testDemoConnection');
    const testResult = document.getElementById('testResult');

    if (testBtn) {
        testBtn.addEventListener('click', function() {
            testBtn.disabled = true;
            testBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testing...';
            testResult.innerHTML = '';

            fetch('{{ route("admin.trading-management.config.global-settings.test-demo") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                testBtn.disabled = false;
                testBtn.innerHTML = '<i class="fas fa-plug"></i> Test Demo Connection';

                if (data.success) {
                    testResult.innerHTML = `
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <strong>Connection Successful!</strong><br>
                            ${data.message}<br>
                            <small>Latency: ${data.latency}ms</small>
                        </div>
                    `;
                } else {
                    testResult.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-times-circle"></i> <strong>Connection Failed</strong><br>
                            ${data.message}
                        </div>
                    `;
                }
            })
            .catch(error => {
                testBtn.disabled = false;
                testBtn.innerHTML = '<i class="fas fa-plug"></i> Test Demo Connection';
                testResult.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-times-circle"></i> <strong>Error</strong><br>
                        ${error.message}
                    </div>
                `;
            });
        });
    }
});
</script>
@endsection
