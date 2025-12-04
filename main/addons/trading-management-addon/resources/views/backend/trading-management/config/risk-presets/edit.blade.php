@extends('backend.layout.master')

@section('element')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Edit Risk Preset</h4>
                    <a href="{{ route('admin.trading-management.config.risk-presets.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.trading-management.config.risk-presets.update', $preset) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Preset Name *</label>
                                <input type="text" name="name" class="form-control" value="{{ $preset->name }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Position Size Mode *</label>
                                <select name="position_size_mode" class="form-control" id="positionSizeMode" required>
                                    <option value="RISK_PERCENT" {{ $preset->position_size_mode === 'RISK_PERCENT' ? 'selected' : '' }}>Risk Percentage</option>
                                    <option value="FIXED" {{ $preset->position_size_mode === 'FIXED' ? 'selected' : '' }}>Fixed Lot</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3">{{ $preset->description }}</textarea>
                    </div>

                    <div class="row" id="riskPercentField" style="display:{{ $preset->position_size_mode === 'RISK_PERCENT' ? 'flex' : 'none' }};">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Risk Per Trade (%)</label>
                                <input type="number" name="risk_per_trade_pct" class="form-control" step="0.01" min="0" max="100" value="{{ $preset->risk_per_trade_pct }}">
                            </div>
                        </div>
                    </div>

                    <div class="row" id="fixedLotField" style="display:{{ $preset->position_size_mode === 'FIXED' ? 'flex' : 'none' }};">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Fixed Lot Size</label>
                                <input type="number" name="fixed_lot" class="form-control" step="0.01" min="0" value="{{ $preset->fixed_lot }}">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="enabled" name="enabled" value="1" {{ $preset->enabled ? 'checked' : '' }}>
                            <label class="custom-control-label" for="enabled">
                                <strong>Enabled</strong>
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="isDefaultTemplate" name="is_default_template" value="1" {{ $preset->is_default_template ? 'checked' : '' }}>
                            <label class="custom-control-label" for="isDefaultTemplate">
                                <strong>Set as Default Template</strong>
                            </label>
                        </div>
                    </div>

                    <hr>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Preset
                        </button>
                        <a href="{{ route('admin.trading-management.config.risk-presets.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('script')
<script>
$(document).ready(function() {
    $('#positionSizeMode').change(function() {
        if ($(this).val() === 'FIXED') {
            $('#riskPercentField').hide();
            $('#fixedLotField').show();
        } else {
            $('#riskPercentField').show();
            $('#fixedLotField').hide();
        }
    });
});
</script>
@endpush
@endsection
