@extends('backend.layout.master')

@section('title')
    {{ $title }}
@endsection

@section('element')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{ $title }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Symbol</th>
                                        <th>Direction</th>
                                        <th>Entry Price</th>
                                        <th>Close Price</th>
                                        <th>PnL</th>
                                        <th>Closed At</th>
                                        <th>Reason</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($positions as $position)
                                        <tr>
                                            <td>{{ $position->symbol }}</td>
                                            <td>{{ strtoupper($position->direction) }}</td>
                                            <td>{{ $position->entry_price }}</td>
                                            <td>{{ $position->current_price ?? 'N/A' }}</td>
                                            <td class="{{ $position->pnl >= 0 ? 'text-success' : 'text-danger' }}">
                                                {{ $position->pnl }} ({{ $position->pnl_percentage }}%)
                                            </td>
                                            <td>{{ $position->closed_at ? $position->closed_at->format('Y-m-d H:i') : 'N/A' }}</td>
                                            <td>{{ $position->closed_reason ?? 'N/A' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center">No closed positions</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        {{ $positions->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

