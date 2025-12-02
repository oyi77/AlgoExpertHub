@extends(Config::theme() . 'layout.auth')

@section('title')
    {{ $title }}
@endsection

@section('content')
    <div class="sp_site_card">
        <div class="card-header">
            <h4>{{ __($title) }}</h4>
        </div>
        <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Trader</th>
                                        <th>Symbol</th>
                                        <th>Direction</th>
                                        <th>Original Qty</th>
                                        <th>Copied Qty</th>
                                        <th>Status</th>
                                        <th>Copied At</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($executions as $execution)
                                        <tr>
                                            <td>{{ $execution->trader->username ?? $execution->trader->email }}</td>
                                            <td>{{ $execution->traderPosition->symbol ?? 'N/A' }}</td>
                                            <td>
                                                <span class="badge badge-{{ $execution->traderPosition->direction === 'buy' ? 'success' : 'danger' }}">
                                                    {{ strtoupper($execution->traderPosition->direction ?? 'N/A') }}
                                                </span>
                                            </td>
                                            <td>{{ number_format($execution->original_quantity, 8) }}</td>
                                            <td>{{ number_format($execution->copied_quantity, 8) }}</td>
                                            <td>
                                                <span class="badge badge-{{ $execution->status === 'executed' ? 'success' : ($execution->status === 'failed' ? 'danger' : 'warning') }}">
                                                    {{ ucfirst($execution->status) }}
                                                </span>
                                            </td>
                                            <td>{{ $execution->copied_at ? $execution->copied_at->format('Y-m-d H:i') : 'N/A' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center">No copy trading history found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if ($executions->hasPages())
                            <div class="mt-3">
                                {{ $executions->links() }}
                            </div>
                        @endif
        </div>
    </div>
@endsection

