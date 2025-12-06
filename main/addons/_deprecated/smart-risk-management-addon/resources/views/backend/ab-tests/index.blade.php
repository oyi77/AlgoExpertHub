@extends('backend.layout.master')

@section('title')
    {{ $title ?? 'A/B Testing' }}
@endsection

@section('element')
    <div class="row">
        <div class="col-12">
            <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">A/B Tests</h4>
                        <div class="card-tools">
                            <a href="{{ route('admin.srm.ab-tests.create') }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus"></i> Create Test
                            </a>
                        </div>
                    </div>
                <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Status</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Pilot Group</th>
                                        <th>Control Group</th>
                                        <th>Results</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($tests as $test)
                                        <tr>
                                            <td>{{ $test->name }}</td>
                                            <td>
                                                <span class="badge badge-{{ $test->status == 'running' ? 'success' : ($test->status == 'completed' ? 'info' : 'secondary') }}">
                                                    {{ ucfirst($test->status) }}
                                                </span>
                                            </td>
                                            <td>{{ $test->start_date ? $test->start_date->format('Y-m-d') : 'N/A' }}</td>
                                            <td>{{ $test->end_date ? $test->end_date->format('Y-m-d') : 'N/A' }}</td>
                                            <td>{{ $test->pilot_group_size }}</td>
                                            <td>{{ $test->control_group_size }}</td>
                                            <td>
                                                @if($test->status == 'completed')
                                                    <span class="badge badge-{{ $test->is_significant ? 'success' : 'warning' }}">
                                                        {{ $test->is_significant ? 'Significant' : 'Not Significant' }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('admin.srm.ab-tests.show', $test->id) }}" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                                @if($test->status == 'draft')
                                                    <form action="{{ route('admin.srm.ab-tests.start', $test->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-success">
                                                            <i class="fas fa-play"></i> Start
                                                        </button>
                                                    </form>
                                                @endif
                                                @if($test->status == 'running')
                                                    <form action="{{ route('admin.srm.ab-tests.stop', $test->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-warning">
                                                            <i class="fas fa-stop"></i> Stop
                                                        </button>
                                                    </form>
                                                @endif
                                                @if($test->status == 'completed')
                                                    <a href="{{ route('admin.srm.ab-tests.results', $test->id) }}" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-chart-bar"></i> Results
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center">No A/B tests found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="mt-3">
                            {{ $tests->links() }}
                        </div>
                </div>
            </div>
        </div>
    </div>
@endsection

