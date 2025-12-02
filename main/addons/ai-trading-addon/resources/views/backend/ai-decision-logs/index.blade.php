@extends('backend.layout.master')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>AI & Filter Decision Logs</h4>
                </div>
                <div class="card-body">
                    {{-- Statistics --}}
                    <div class="row mb-3">
                        <div class="col-md-2">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h6>Total Signals</h6>
                                    <h4>{{ $stats['total'] }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h6>Filter Pass</h6>
                                    <h4>{{ $stats['filter_pass'] }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-danger text-white">
                                <div class="card-body text-center">
                                    <h6>Filter Fail</h6>
                                    <h4>{{ $stats['filter_fail'] }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h6>AI Accept</h6>
                                    <h4>{{ $stats['ai_accept'] }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-danger text-white">
                                <div class="card-body text-center">
                                    <h6>AI Reject</h6>
                                    <h4>{{ $stats['ai_reject'] }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Filters --}}
                    <form method="GET" class="mb-3">
                        <div class="row">
                            <div class="col-md-3">
                                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" placeholder="From Date">
                            </div>
                            <div class="col-md-3">
                                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" placeholder="To Date">
                            </div>
                            <div class="col-md-2">
                                <select name="filter_result" class="form-control">
                                    <option value="">All Filter Results</option>
                                    <option value="pass" {{ request('filter_result') === 'pass' ? 'selected' : '' }}>Pass</option>
                                    <option value="fail" {{ request('filter_result') === 'fail' ? 'selected' : '' }}>Fail</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="ai_decision" class="form-control">
                                    <option value="">All AI Decisions</option>
                                    <option value="execute" {{ request('ai_decision') === 'execute' ? 'selected' : '' }}>Accept</option>
                                    <option value="reject" {{ request('ai_decision') === 'reject' ? 'selected' : '' }}>Reject</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary">Filter</button>
                                <a href="{{ route('admin.ai-decision-logs.index') }}" class="btn btn-secondary">Reset</a>
                            </div>
                        </div>
                    </form>

                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Signal ID</th>
                                <th>Channel / Source</th>
                                <th>Filter Result</th>
                                <th>AI Decision</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
                                <tr>
                                    <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                                    <td>
                                        <a href="{{ route('admin.signal.show', $log->signal_id) }}" target="_blank">
                                            #{{ $log->signal_id }}
                                        </a>
                                    </td>
                                    <td>{{ $log->channelSource->name ?? 'N/A' }}</td>
                                    <td>
                                        @if(isset($log->parsed_data['filter_evaluation']))
                                            @php $filterResult = $log->parsed_data['filter_evaluation']; @endphp
                                            @if($filterResult['pass'] ?? false)
                                                <span class="badge badge-success">PASS</span>
                                            @else
                                                <span class="badge badge-danger">FAIL</span>
                                                <small class="d-block text-muted">{{ Str::limit($filterResult['reason'] ?? '', 30) }}</small>
                                            @endif
                                        @else
                                            <span class="badge badge-secondary">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(isset($log->parsed_data['ai_evaluation']))
                                            @php $aiResult = $log->parsed_data['ai_evaluation']; @endphp
                                            @if($aiResult['execute'] ?? false)
                                                <span class="badge badge-success">ACCEPT</span>
                                                @if(isset($aiResult['adjusted_risk_factor']) && $aiResult['adjusted_risk_factor'] < 1.0)
                                                    <small class="d-block text-info">Risk: {{ number_format($aiResult['adjusted_risk_factor'] * 100, 1) }}%</small>
                                                @endif
                                            @else
                                                <span class="badge badge-danger">REJECT</span>
                                                <small class="d-block text-muted">{{ Str::limit($aiResult['reason'] ?? '', 30) }}</small>
                                            @endif
                                        @else
                                            <span class="badge badge-secondary">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.ai-decision-logs.show', $log->id) }}" class="btn btn-sm btn-info">
                                            <i class="fa fa-eye"></i> View Details
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">No decision logs found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    {{ $logs->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

