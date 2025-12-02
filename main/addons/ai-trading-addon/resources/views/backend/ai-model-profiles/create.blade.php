@extends('backend.layout.master')

@section('element')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Create AI Model Profile</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.ai-model-profiles.store') }}" method="POST">
                        @csrf

                        <div class="form-group">
                            <label>Name *</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Provider *</label>
                                    <select name="provider" class="form-control" required>
                                        <option value="openai" {{ old('provider') === 'openai' ? 'selected' : '' }}>OpenAI</option>
                                        <option value="gemini" {{ old('provider') === 'gemini' ? 'selected' : '' }}>Google Gemini</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Model Name *</label>
                                    <input type="text" name="model_name" class="form-control" value="{{ old('model_name', 'gpt-4') }}" required>
                                    <small class="form-text text-muted">e.g., gpt-4, gemini-pro</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>API Key Reference</label>
                            <input type="text" name="api_key_ref" class="form-control" value="{{ old('api_key_ref') }}" placeholder="e.g., OPENAI_API_KEY">
                            <small class="form-text text-muted">Environment variable name or config key (not the actual key)</small>
                        </div>

                        <div class="form-group">
                            <label>Mode *</label>
                            <select name="mode" class="form-control" required>
                                <option value="CONFIRM" {{ old('mode') === 'CONFIRM' ? 'selected' : '' }}>CONFIRM (Signal Confirmation)</option>
                                <option value="SCAN" {{ old('mode') === 'SCAN' ? 'selected' : '' }}>SCAN (Market Scan)</option>
                                <option value="POSITION_MGMT" {{ old('mode') === 'POSITION_MGMT' ? 'selected' : '' }}>POSITION_MGMT (Position Management)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Prompt Template</label>
                            <textarea name="prompt_template" class="form-control" rows="10" placeholder="Enter prompt template with placeholders like @{{symbol}}, @{{direction}}, @{{market_data}}, etc.">{{ old('prompt_template') }}</textarea>
                            <small class="form-text text-muted">
                                Use placeholders: @{{symbol}}, @{{direction}}, @{{entry}}, @{{sl}}, @{{tp}}, @{{market_data}}
                            </small>
                        </div>

                        <div class="form-group">
                            <label>Settings (JSON)</label>
                            <textarea name="settings" class="form-control" rows="5">{{ old('settings', '{"temperature": 0.7, "max_tokens": 1000}') }}</textarea>
                            <small class="form-text text-muted">JSON settings: temperature, max_tokens, etc.</small>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Max Calls Per Minute</label>
                                    <input type="number" name="max_calls_per_minute" class="form-control" value="{{ old('max_calls_per_minute') }}" min="1">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Max Calls Per Day</label>
                                    <input type="number" name="max_calls_per_day" class="form-control" value="{{ old('max_calls_per_day') }}" min="1">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Visibility *</label>
                            <select name="visibility" class="form-control" required>
                                <option value="PRIVATE" {{ old('visibility') === 'PRIVATE' ? 'selected' : '' }}>Private</option>
                                <option value="PUBLIC_MARKETPLACE" {{ old('visibility') === 'PUBLIC_MARKETPLACE' ? 'selected' : '' }}>Public Marketplace</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="clonable" value="1" {{ old('clonable', true) ? 'checked' : '' }}>
                                Allow cloning
                            </label>
                        </div>

                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="enabled" value="1" {{ old('enabled', true) ? 'checked' : '' }}>
                                Enabled
                            </label>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Create Profile</button>
                            <a href="{{ route('admin.ai-model-profiles.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

