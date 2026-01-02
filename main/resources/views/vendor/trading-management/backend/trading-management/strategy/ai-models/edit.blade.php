@extends('trading-management::backend.trading-management.layout')

@section('submenu-content')
<div class="card">
    <div class="card-header">
        <h4>Edit AI Model Profile</h4>
        <div class="card-header-action">
            <a href="{{ route('admin.trading-management.strategy.ai-models.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.trading-management.strategy.ai-models.update', $profile) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="name">Profile Name</label>
                <input type="text" class="form-control" id="name" name="name" value="{{ $profile->name }}" required>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3">{{ $profile->description }}</textarea>
            </div>

            <div class="form-group">
                <label for="ai_connection_id">AI Connection</label>
                <select class="form-control" id="ai_connection_id" name="ai_connection_id" required>
                    <option value="{{ $profile->ai_connection_id }}">Current Connection</option>
                </select>
            </div>

            <div class="form-group">
                <label for="prompt_template">Prompt Template</label>
                <textarea class="form-control" id="prompt_template" name="prompt_template" rows="10" required>{{ $profile->prompt_template }}</textarea>
            </div>

            <div class="form-group">
                <label for="min_confidence_required">Min Confidence Required (%)</label>
                <input type="number" class="form-control" id="min_confidence_required" name="min_confidence_required" value="{{ $profile->min_confidence_required }}" min="0" max="100">
            </div>

            <div class="form-group">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="enabled" name="enabled" value="1" {{ $profile->enabled ? 'checked' : '' }}>
                    <label class="custom-control-label" for="enabled">Enabled</label>
                </div>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Profile
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

