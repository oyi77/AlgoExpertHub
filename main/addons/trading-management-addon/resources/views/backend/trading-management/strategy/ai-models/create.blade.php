@extends('trading-management::backend.trading-management.layout')

@section('submenu-content')
<div class="card">
    <div class="card-header">
        <h4>Create AI Model Profile</h4>
        <div class="card-header-action">
            <a href="{{ route('admin.trading-management.strategy.ai-models.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.trading-management.strategy.ai-models.store') }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="name">Profile Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
            </div>

            <div class="form-group">
                <label for="ai_connection_id">AI Connection <span class="text-danger">*</span></label>
                <select class="form-control" id="ai_connection_id" name="ai_connection_id" required>
                    <option value="">Select AI Connection</option>
                    {{-- Load from ai-connection-addon --}}
                </select>
                <small class="text-muted">Configure AI connections in AI Manager addon</small>
            </div>

            <div class="form-group">
                <label for="prompt_template">Prompt Template <span class="text-danger">*</span></label>
                <textarea class="form-control" id="prompt_template" name="prompt_template" rows="10" required>Analyze this trading signal and provide confidence score (0-100):

Signal: {signal_title}
Pair: {currency_pair}
Direction: {direction}
Entry: {open_price}
SL: {sl}
TP: {tp}

Return JSON: {"confidence": 0-100, "reasoning": "..."}</textarea>
            </div>

            <div class="form-group">
                <label for="min_confidence_required">Min Confidence Required (%)</label>
                <input type="number" class="form-control" id="min_confidence_required" name="min_confidence_required" value="60" min="0" max="100">
            </div>

            <div class="form-group">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="enabled" name="enabled" value="1" checked>
                    <label class="custom-control-label" for="enabled">Enabled</label>
                </div>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Create Profile
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

