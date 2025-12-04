@extends('trading-management::backend.trading-management.layout')

@section('submenu-content')
<div class="card">
    <div class="card-header">
        <h4>Edit Filter Strategy</h4>
        <div class="card-header-action">
            <a href="{{ route('admin.trading-management.strategy.filters.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.trading-management.strategy.filters.update', $strategy) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="name">Strategy Name</label>
                <input type="text" class="form-control" id="name" name="name" value="{{ $strategy->name }}" required>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3">{{ $strategy->description }}</textarea>
            </div>

            <div class="form-group">
                <label for="config">Configuration (JSON)</label>
                <textarea class="form-control" id="config" name="config" rows="15" required>{{ json_encode($strategy->config, JSON_PRETTY_PRINT) }}</textarea>
            </div>

            <div class="form-group">
                <label for="visibility">Visibility</label>
                <select class="form-control" id="visibility" name="visibility">
                    <option value="PRIVATE" {{ $strategy->visibility === 'PRIVATE' ? 'selected' : '' }}>Private</option>
                    <option value="PUBLIC_MARKETPLACE" {{ $strategy->visibility === 'PUBLIC_MARKETPLACE' ? 'selected' : '' }}>Public</option>
                </select>
            </div>

            <div class="form-group">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="clonable" name="clonable" value="1" {{ $strategy->clonable ? 'checked' : '' }}>
                    <label class="custom-control-label" for="clonable">Allow cloning</label>
                </div>
            </div>

            <div class="form-group">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="enabled" name="enabled" value="1" {{ $strategy->enabled ? 'checked' : '' }}>
                    <label class="custom-control-label" for="enabled">Enabled</label>
                </div>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Strategy
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

