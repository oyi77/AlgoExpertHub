@extends('trading-management::backend.trading-management.layout')

@section('submenu-content')
<div class="card">
    <div class="card-header">
        <h4>Create Risk Preset</h4>
        <div class="card-header-action">
            <a href="{{ route('admin.trading-management.config.risk-presets.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.trading-management.config.risk-presets.store') }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="name">Preset Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                       id="name" name="name" value="{{ old('name') }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3">{{ old('description') }}</textarea>
            </div>

            <div class="form-group">
                <label for="position_size_mode">Position Sizing Mode <span class="text-danger">*</span></label>
                <select class="form-control" id="position_size_mode" name="position_size_mode" required>
                    <option value="RISK_PERCENT" {{ old('position_size_mode') === 'RISK_PERCENT' ? 'selected' : '' }}>Risk Percentage</option>
                    <option value="FIXED" {{ old('position_size_mode') === 'FIXED' ? 'selected' : '' }}>Fixed Lot</option>
                </select>
            </div>

            <div class="form-group">
                <label for="risk_per_trade_pct">Risk Per Trade (%)</label>
                <input type="number" step="0.01" class="form-control" id="risk_per_trade_pct" name="risk_per_trade_pct" value="{{ old('risk_per_trade_pct', 1.0) }}">
            </div>

            <div class="form-group">
                <label for="fixed_lot">Fixed Lot Size</label>
                <input type="number" step="0.01" class="form-control" id="fixed_lot" name="fixed_lot" value="{{ old('fixed_lot', 0.01) }}">
            </div>

            <div class="form-group">
                <label for="filter_strategy_id">Filter Strategy (Optional)</label>
                <select class="form-control" id="filter_strategy_id" name="filter_strategy_id">
                    <option value="">None</option>
                    @foreach($filterStrategies as $filter)
                        <option value="{{ $filter->id }}">{{ $filter->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="ai_model_profile_id">AI Model Profile (Optional)</label>
                <select class="form-control" id="ai_model_profile_id" name="ai_model_profile_id">
                    <option value="">None</option>
                    @foreach($aiModelProfiles as $profile)
                        <option value="{{ $profile->id }}">{{ $profile->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="smart_risk_enabled" name="smart_risk_enabled" value="1" {{ old('smart_risk_enabled') ? 'checked' : '' }}>
                    <label class="custom-control-label" for="smart_risk_enabled">
                        Enable Smart Risk (AI Adaptive)
                    </label>
                </div>
            </div>

            <div class="form-group">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="enabled" name="enabled" value="1" checked>
                    <label class="custom-control-label" for="enabled">
                        Enabled
                    </label>
                </div>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Create Preset
                </button>
                <a href="{{ route('admin.trading-management.config.risk-presets.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

