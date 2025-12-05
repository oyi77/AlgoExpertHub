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
                    <i class="fas fa-info-circle"></i> <strong>Global Configuration</strong> - These settings are shared across all MT4/MT5 connections. Configure your mtapi.io credentials here to use them globally.
                </div>

                <form action="{{ route('admin.trading-management.config.global-settings.update') }}" method="POST">
                    @csrf

                    <!-- MTAPI.io Global Config -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-key"></i> mtapi.io Global Configuration</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> <strong>Note:</strong> You need an mtapi.io account to connect MT4/MT5 brokers. 
                                <a href="https://mtapi.io" target="_blank" class="alert-link">Get API key here</a>
                            </div>

                            <div class="form-group">
                                <div class="custom-control custom-switch custom-switch-lg">
                                    <input type="hidden" name="mtapi_enabled" value="0">
                                    <input type="checkbox" class="custom-control-input" id="mtapi_enabled" name="mtapi_enabled" value="1" {{ $settings['mtapi_enabled'] ?? false ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="mtapi_enabled">
                                        <strong>Enable Global MTAPI Credentials</strong>
                                    </label>
                                </div>
                                <small class="form-text text-muted">When enabled, these credentials will be used as defaults for all MT4/MT5 connections</small>
                            </div>

                            <div id="mtapiFields" style="{{ ($settings['mtapi_enabled'] ?? false) ? '' : 'display:none;' }}">
                                <div class="form-group">
                                    <label for="mtapi_api_key">mtapi.io API Key <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="mtapi_api_key" 
                                           name="mtapi_api_key" 
                                           value="{{ old('mtapi_api_key', $settings['mtapi_api_key'] ?? '') }}" 
                                           placeholder="Your mtapi.io API key"
                                           {{ ($settings['mtapi_enabled'] ?? false) ? '' : 'disabled' }}>
                                    <small class="form-text text-muted">Get your API key from <a href="https://mtapi.io/dashboard" target="_blank">mtapi.io dashboard</a></small>
                                </div>

                                <div class="form-group">
                                    <label for="mtapi_account_id">mtapi.io Account ID</label>
                                    <input type="text" class="form-control" id="mtapi_account_id" 
                                           name="mtapi_account_id" 
                                           value="{{ old('mtapi_account_id', $settings['mtapi_account_id'] ?? '') }}" 
                                           placeholder="Your mtapi.io account ID (optional)"
                                           {{ ($settings['mtapi_enabled'] ?? false) ? '' : 'disabled' }}>
                                    <small class="form-text text-muted">The account ID from your mtapi.io dashboard (optional, can be set per connection)</small>
                                </div>

                                <div class="form-group">
                                    <label for="mtapi_base_url">mtapi.io Base URL</label>
                                    <input type="url" class="form-control" id="mtapi_base_url" 
                                           name="mtapi_base_url" 
                                           value="{{ old('mtapi_base_url', $settings['mtapi_base_url'] ?? 'https://api.mtapi.io') }}" 
                                           placeholder="https://api.mtapi.io"
                                           {{ ($settings['mtapi_enabled'] ?? false) ? '' : 'disabled' }}>
                                    <small class="form-text text-muted">Default: https://api.mtapi.io (usually no need to change)</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Global Settings
                        </button>
                        <a href="{{ route('admin.trading-management.config.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Configuration
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
    $(function() {
        'use strict'
        
        $('#mtapi_enabled').on('change', function() {
            const enabled = $(this).is(':checked');
            const fields = $('#mtapiFields input, #mtapiFields select, #mtapiFields textarea');
            
            if (enabled) {
                $('#mtapiFields').slideDown();
                fields.prop('disabled', false);
            } else {
                $('#mtapiFields').slideUp();
                fields.prop('disabled', true);
            }
        });
    });
</script>
@endpush
