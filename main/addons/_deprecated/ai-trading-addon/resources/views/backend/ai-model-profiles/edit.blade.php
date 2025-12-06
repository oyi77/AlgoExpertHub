@extends('backend.layout.master')

@section('element')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Edit AI Model Profile</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.ai-model-profiles.update', $aiModelProfile->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="form-group">
                            <label>Name *</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $aiModelProfile->name) }}" required>
                        </div>

                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="3">{{ old('description', $aiModelProfile->description) }}</textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Provider *</label>
                                    <select name="provider" class="form-control" required>
                                        <option value="openai" {{ old('provider', $aiModelProfile->provider) === 'openai' ? 'selected' : '' }}>OpenAI</option>
                                        <option value="gemini" {{ old('provider', $aiModelProfile->provider) === 'gemini' ? 'selected' : '' }}>Google Gemini</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Model Name *</label>
                                    <input type="text" name="model_name" class="form-control" value="{{ old('model_name', $aiModelProfile->model_name) }}" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>API Key Reference</label>
                            <input type="text" name="api_key_ref" class="form-control" value="{{ old('api_key_ref', $aiModelProfile->api_key_ref) }}">
                        </div>

                        <div class="form-group">
                            <label>Mode *</label>
                            <select name="mode" class="form-control" required>
                                <option value="CONFIRM" {{ old('mode', $aiModelProfile->mode) === 'CONFIRM' ? 'selected' : '' }}>CONFIRM (Signal Confirmation)</option>
                                <option value="SCAN" {{ old('mode', $aiModelProfile->mode) === 'SCAN' ? 'selected' : '' }}>SCAN (Market Scan)</option>
                                <option value="POSITION_MGMT" {{ old('mode', $aiModelProfile->mode) === 'POSITION_MGMT' ? 'selected' : '' }}>POSITION_MGMT (Position Management)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Prompt Template</label>
                            <textarea name="prompt_template" class="form-control" rows="10">{{ old('prompt_template', $aiModelProfile->prompt_template) }}</textarea>
                        </div>

                        <div class="form-group">
                            <label>Settings (JSON)</label>
                            <textarea name="settings" class="form-control" rows="5">{{ old('settings', json_encode($aiModelProfile->settings ?? [], JSON_PRETTY_PRINT)) }}</textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Max Calls Per Minute</label>
                                    <input type="number" name="max_calls_per_minute" class="form-control" value="{{ old('max_calls_per_minute', $aiModelProfile->max_calls_per_minute) }}" min="1">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Max Calls Per Day</label>
                                    <input type="number" name="max_calls_per_day" class="form-control" value="{{ old('max_calls_per_day', $aiModelProfile->max_calls_per_day) }}" min="1">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Visibility *</label>
                            <select name="visibility" class="form-control" required>
                                <option value="PRIVATE" {{ old('visibility', $aiModelProfile->visibility) === 'PRIVATE' ? 'selected' : '' }}>Private</option>
                                <option value="PUBLIC_MARKETPLACE" {{ old('visibility', $aiModelProfile->visibility) === 'PUBLIC_MARKETPLACE' ? 'selected' : '' }}>Public Marketplace</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="clonable" value="1" {{ old('clonable', $aiModelProfile->clonable) ? 'checked' : '' }}>
                                Allow cloning
                            </label>
                        </div>

                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="enabled" value="1" {{ old('enabled', $aiModelProfile->enabled) ? 'checked' : '' }}>
                                Enabled
                            </label>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Update Profile</button>
                            <a href="{{ route('admin.ai-model-profiles.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

