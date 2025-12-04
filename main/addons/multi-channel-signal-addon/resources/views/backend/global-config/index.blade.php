@extends('backend.layout.master')

@section('element')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0"><i class="fas fa-cog"></i> Multi-Channel Global Settings</h4>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> <strong>Global Configuration</strong> - These settings are shared across all signal sources. Only admins can modify these settings.
                </div>

                <form action="{{ route('admin.multi-channel.global-config.update') }}" method="POST">
                    @csrf

                    <!-- Telegram MTProto Global Config -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fab fa-telegram"></i> Telegram MTProto Configuration</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> <strong>Important:</strong> Configure these credentials once. All Telegram MTProto sources will use these global settings.
                            </div>

                            <div class="form-group">
                                <div class="custom-control custom-switch custom-switch-lg">
                                    <input type="hidden" name="telegram_enabled" value="0">
                                    <input type="checkbox" class="custom-control-input" id="telegram_enabled" name="telegram_enabled" value="1" {{ $config['telegram_enabled'] ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="telegram_enabled">
                                        <strong>Enable Telegram MTProto</strong>
                                    </label>
                                </div>
                                <small class="text-muted">Allow creating Telegram MTProto signal sources</small>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Telegram API ID <span class="text-danger">*</span></label>
                                        <input type="text" name="telegram_api_id" class="form-control" value="{{ $config['telegram_api_id'] ?? '' }}" {{ $config['telegram_enabled'] ? 'required' : '' }}>
                                        <small class="text-muted">Get from <a href="https://my.telegram.org/apps" target="_blank">https://my.telegram.org/apps</a></small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Telegram API Hash <span class="text-danger">*</span></label>
                                        <input type="password" name="telegram_api_hash" class="form-control" value="{{ $config['telegram_api_hash'] ?? '' }}" {{ $config['telegram_enabled'] ? 'required' : '' }}>
                                        <small class="text-muted">Your API Hash from Telegram</small>
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-secondary">
                                <strong>Note:</strong> Once configured, users creating Telegram MTProto sources will automatically use these credentials. They only need to authenticate with their phone number.
                            </div>
                        </div>
                    </div>

                    <!-- Default Parser Settings -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-robot"></i> Default Parser Settings</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Default Parser Type</label>
                                        <select name="default_parser" class="form-control" required>
                                            <option value="regex" {{ ($config['default_parser'] ?? 'regex') === 'regex' ? 'selected' : '' }}>Regex Pattern</option>
                                            <option value="pattern" {{ ($config['default_parser'] ?? '') === 'pattern' ? 'selected' : '' }}>Pattern Template</option>
                                            <option value="ai" {{ ($config['default_parser'] ?? '') === 'ai' ? 'selected' : '' }}>AI Parser</option>
                                        </select>
                                        <small class="text-muted">Default parsing method for new signal sources</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Default Confidence Threshold (0-100)</label>
                                        <input type="number" name="default_confidence_threshold" class="form-control" 
                                            value="{{ $config['default_confidence_threshold'] ?? 80 }}" min="0" max="100" step="1" required>
                                        <small class="text-muted">Minimum confidence score to create signal</small>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="hidden" name="auto_publish_enabled" value="0">
                                    <input type="checkbox" class="custom-control-input" id="auto_publish" name="auto_publish_enabled" value="1" {{ $config['auto_publish_enabled'] ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="auto_publish">
                                        <strong>Enable Auto-Publish for High Confidence Signals</strong>
                                    </label>
                                </div>
                                <small class="text-muted">Automatically publish signals that meet the confidence threshold</small>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> Save Global Configuration
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

