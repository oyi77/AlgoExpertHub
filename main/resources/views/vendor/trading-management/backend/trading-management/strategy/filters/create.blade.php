@extends('trading-management::backend.trading-management.layout')

@section('submenu-content')
<div class="card">
    <div class="card-header">
        <h4>Create Filter Strategy</h4>
        <div class="card-header-action">
            <a href="{{ route('admin.trading-management.strategy.filters.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.trading-management.strategy.filters.store') }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="name">Strategy Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                       id="name" name="name" value="{{ old('name') }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3">{{ old('description') }}</textarea>
            </div>

            <div class="form-group">
                <label for="config">Configuration (JSON) <span class="text-danger">*</span></label>
                <textarea class="form-control @error('config') is-invalid @enderror" 
                          id="config" name="config" rows="15" required>{{ old('config', json_encode([
    'indicators' => [
        'ema_fast' => ['period' => 10],
        'ema_slow' => ['period' => 100],
        'stoch' => ['k' => 14, 'd' => 3, 'smooth' => 3],
        'psar' => ['step' => 0.02, 'max' => 0.2]
    ],
    'rules' => [
        'logic' => 'AND',
        'conditions' => [
            ['left' => 'ema_fast', 'operator' => '>', 'right' => 'ema_slow'],
            ['left' => 'stoch', 'operator' => '<', 'right' => 80],
            ['left' => 'psar', 'operator' => 'below_price', 'right' => null]
        ]
    ]
], JSON_PRETTY_PRINT)) }}</textarea>
                <small class="form-text text-muted">JSON configuration for indicators and rules</small>
                @error('config')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label for="visibility">Visibility</label>
                <select class="form-control" id="visibility" name="visibility">
                    <option value="PRIVATE">Private</option>
                    <option value="PUBLIC_MARKETPLACE">Public</option>
                </select>
            </div>

            <div class="form-group">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="clonable" name="clonable" value="1">
                    <label class="custom-control-label" for="clonable">Allow cloning</label>
                </div>
            </div>

            <div class="form-group">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="enabled" name="enabled" value="1" checked>
                    <label class="custom-control-label" for="enabled">Enabled</label>
                </div>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Create Strategy
                </button>
                <a href="{{ route('admin.trading-management.strategy.filters.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

