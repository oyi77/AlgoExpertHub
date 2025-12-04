@extends('trading-management::backend.trading-management.layout')

@section('submenu-content')
<div class="card">
    <div class="card-header">
        <h4>🛡️ Risk Presets</h4>
        <div class="card-header-action">
            <a href="{{ route('admin.trading-management.config.risk-presets.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create Preset
            </a>
        </div>
    </div>
    <div class="card-body">
        @if($presets->isEmpty())
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No risk presets yet. Create your first preset to start managing trading risk.
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Position Sizing</th>
                            <th>Risk %</th>
                            <th>Filter</th>
                            <th>AI</th>
                            <th>Smart Risk</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($presets as $preset)
                        <tr>
                            <td><strong>{{ $preset->name }}</strong><br><small class="text-muted">{{ $preset->description }}</small></td>
                            <td>{{ $preset->position_size_mode }}</td>
                            <td>{{ $preset->risk_per_trade_pct ?? $preset->fixed_lot }}{{ $preset->position_size_mode === 'RISK_PERCENT' ? '%' : ' lot' }}</td>
                            <td>
                                @if($preset->filterStrategy)
                                    <span class="badge badge-info">{{ $preset->filterStrategy->name }}</span>
                                @else
                                    <span class="text-muted">None</span>
                                @endif
                            </td>
                            <td>
                                @if($preset->aiModelProfile)
                                    <span class="badge badge-primary">{{ $preset->aiModelProfile->name }}</span>
                                @else
                                    <span class="text-muted">None</span>
                                @endif
                            </td>
                            <td>
                                @if($preset->hasSmartRisk())
                                    <span class="badge badge-success"><i class="fas fa-check"></i> Enabled</span>
                                @else
                                    <span class="badge badge-secondary">Disabled</span>
                                @endif
                            </td>
                            <td>
                                @if($preset->enabled)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-secondary">Disabled</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.trading-management.config.risk-presets.edit', $preset) }}" class="btn btn-sm btn-info">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('admin.trading-management.config.risk-presets.destroy', $preset) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete this preset?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $presets->links() }}
        @endif
    </div>
</div>
@endsection

