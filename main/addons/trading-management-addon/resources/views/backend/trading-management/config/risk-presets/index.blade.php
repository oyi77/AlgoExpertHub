@extends('backend.layout.master')

@section('element')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0"><i class="fas fa-shield-alt"></i> Risk Presets</h4>
                    <a href="{{ route('admin.trading-management.config.risk-presets.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Preset
                    </a>
                </div>
            </div>
            <div class="card-body">
                @if($presets->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Position Size Mode</th>
                                <th>Risk %</th>
                                <th>Fixed Lot</th>
                                <th>Default Template</th>
                                <th>Enabled</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($presets as $preset)
                            <tr>
                                <td><strong>{{ $preset->name }}</strong></td>
                                <td>{{ Str::limit($preset->description, 40) }}</td>
                                <td>
                                    <span class="badge {{ $preset->position_size_mode === 'RISK_PERCENT' ? 'badge-info' : 'badge-secondary' }}">
                                        {{ $preset->position_size_mode }}
                                    </span>
                                </td>
                                <td>{{ $preset->risk_per_trade_pct ? $preset->risk_per_trade_pct . '%' : '-' }}</td>
                                <td>{{ $preset->fixed_lot ?? '-' }}</td>
                                <td>
                                    @if($preset->is_default_template)
                                    <i class="fas fa-check text-success"></i>
                                    @else
                                    <i class="fas fa-times text-muted"></i>
                                    @endif
                                </td>
                                <td>
                                    @if($preset->enabled)
                                    <span class="badge badge-success">Yes</span>
                                    @else
                                    <span class="badge badge-secondary">No</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group">
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
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $presets->links() }}
                @else
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No risk presets found. <a href="{{ route('admin.trading-management.config.risk-presets.create') }}">Create your first preset</a>.
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
