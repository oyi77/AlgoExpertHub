@extends('trading-management::backend.trading-management.layout')

@section('submenu-content')
<div class="card">
    <div class="card-header">
        <h4>Edit Risk Preset</h4>
        <div class="card-header-action">
            <a href="{{ route('admin.trading-management.config.risk-presets.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.trading-management.config.risk-presets.update', $preset) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="name">Preset Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="name" name="name" value="{{ $preset->name }}" required>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3">{{ $preset->description }}</textarea>
            </div>

            <div class="form-group">
                <label for="position_size_mode">Position Sizing Mode</label>
                <select class="form-control" id="position_size_mode" name="position_size_mode">
                    <option value="RISK_PERCENT" {{ $preset->position_size_mode === 'RISK_PERCENT' ? 'selected' : '' }}>Risk Percentage</option>
                    <option value="FIXED" {{ $preset->position_size_mode === 'FIXED' ? 'selected' : '' }}>Fixed Lot</option>
                </select>
            </div>

            <div class="form-group">
                <label for="risk_per_trade_pct">Risk Per Trade (%)</label>
                <input type="number" step="0.01" class="form-control" id="risk_per_trade_pct" name="risk_per_trade_pct" value="{{ $preset->risk_per_trade_pct }}">
            </div>

            <div class="form-group">
                <label for="fixed_lot">Fixed Lot</label>
                <input type="number" step="0.01" class="form-control" id="fixed_lot" name="fixed_lot" value="{{ $preset->fixed_lot }}">
            </div>

            <div class="form-group">
                <label for="filter_strategy_id">Filter Strategy</label>
                <select class="form-control" id="filter_strategy_id" name="filter_strategy_id">
                    <option value="">None</option>
                    @foreach($filterStrategies as $filter)
                        <option value="{{ $filter->id }}" {{ $preset->filter_strategy_id == $filter->id ? 'selected' : '' }}>{{ $filter->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="ai_model_profile_id">AI Model</label>
                <select class="form-control" id="ai_model_profile_id" name="ai_model_profile_id">
                    <option value="">None</option>
                    @foreach($aiModelProfiles as $profile)
                        <option value="{{ $profile->id }}" {{ $preset->ai_model_profile_id == $profile->id ? 'selected' : '' }}>{{ $profile->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="smart_risk_enabled" name="smart_risk_enabled" value="1" {{ $preset->smart_risk_enabled ? 'checked' : '' }}>
                    <label class="custom-control-label" for="smart_risk_enabled">Smart Risk Enabled</label>
                </div>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Preset
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

