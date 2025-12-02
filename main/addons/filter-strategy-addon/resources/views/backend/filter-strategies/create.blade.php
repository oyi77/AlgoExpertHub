@extends('backend.layout.master')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Create Filter Strategy</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.filter-strategies.store') }}" method="POST">
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
                            <label>Configuration (JSON) *</label>
                            <textarea name="config" class="form-control" rows="15" required>{{ old('config', '{
  "indicators": {
    "ema_fast": {"period": 10},
    "ema_slow": {"period": 100},
    "stoch": {"k": 14, "d": 3, "smooth": 3},
    "psar": {"step": 0.02, "max": 0.2}
  },
  "rules": {
    "logic": "AND",
    "conditions": [
      {"left": "ema_fast", "operator": ">", "right": "ema_slow"},
      {"left": "stoch", "operator": "<", "right": 80},
      {"left": "psar", "operator": "below_price", "right": null}
    ]
  }
}') }}</textarea>
                            <small class="form-text text-muted">
                                JSON configuration for indicators and rules. See documentation for format.
                            </small>
                            @error('config')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Create Strategy</button>
                            <a href="{{ route('admin.filter-strategies.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

