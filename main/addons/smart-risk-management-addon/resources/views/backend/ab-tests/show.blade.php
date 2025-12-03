@extends('backend.layout.master')

@section('title')
    {{ $title ?? 'A/B Test Details' }}
@endsection

@section('element')
    <div class="row">
        <div class="col-12">
            <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{ $test->name }}</h4>
                        <div class="card-tools">
                            @if($test->status == 'draft')
                                <form action="{{ route('admin.srm.ab-tests.start', $test->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success">
                                        <i class="fas fa-play"></i> Start Test
                                    </button>
                                </form>
                            @endif
                            @if($test->status == 'running')
                                <form action="{{ route('admin.srm.ab-tests.stop', $test->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-warning">
                                        <i class="fas fa-stop"></i> Stop Test
                                    </button>
                                </form>
                            @endif
                            @if($test->status == 'completed')
                                <a href="{{ route('admin.srm.ab-tests.results', $test->id) }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-chart-bar"></i> View Results
                                </a>
                            @endif
                        </div>
                    </div>
                <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <tr>
                                    <th>Status</th>
                                    <td>
                                        <span class="badge badge-{{ $test->status == 'running' ? 'success' : ($test->status == 'completed' ? 'info' : 'secondary') }}">
                                            {{ ucfirst($test->status) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Description</th>
                                    <td>{{ $test->description ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Pilot Group Percentage</th>
                                    <td>{{ number_format($test->pilot_group_percentage, 2) }}%</td>
                                </tr>
                                <tr>
                                    <th>Test Duration</th>
                                    <td>{{ $test->test_duration_days }} days</td>
                                </tr>
                                <tr>
                                    <th>Start Date</th>
                                    <td>{{ $test->start_date ? $test->start_date->format('Y-m-d') : 'Not started' }}</td>
                                </tr>
                                <tr>
                                    <th>End Date</th>
                                    <td>{{ $test->end_date ? $test->end_date->format('Y-m-d') : 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Pilot Group Size</th>
                                    <td>{{ $test->pilot_group_size }}</td>
                                </tr>
                                <tr>
                                    <th>Control Group Size</th>
                                    <td>{{ $test->control_group_size }}</td>
                                </tr>
                                <tr>
                                    <th>Created By</th>
                                    <td>{{ $test->createdBy->username ?? 'N/A' }}</td>
                                </tr>
                            </table>
                        </div>

                        <div class="mt-4">
                            <h5>Pilot Logic</h5>
                            <pre class="bg-light p-3">{{ json_encode($test->pilot_logic, JSON_PRETTY_PRINT) }}</pre>
                        </div>
                </div>
            </div>
        </div>
    </div>
@endsection

