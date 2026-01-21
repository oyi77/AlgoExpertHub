@extends('backend.layout.master')

@section('content')
<div class="card">
    <div class="card-header">
        <h4>Crowded Trades</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Symbol</th>
                        <th>Traders</th>
                        <th>Total Size</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($positions as $position)
                        <tr>
                            <td>{{ $position->symbol }}</td>
                            <td>{{ $position->traders }}</td>
                            <td>{{ $position->total_size }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3">No data.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
