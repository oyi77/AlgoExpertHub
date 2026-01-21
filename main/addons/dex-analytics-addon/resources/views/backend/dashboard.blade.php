@extends('backend.layout.master')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4>DEX Analytics Dashboard</h4>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <h6>Total Traders</h6>
                                <h3>{{ $stats['total_traders'] ?? 0 }}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <h6>Active Positions</h6>
                                <h3>{{ $stats['active_positions'] ?? 0 }}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <h6>Total PnL</h6>
                                <h3>{{ $stats['total_pnl'] ?? 0 }}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <h6>Liquidations</h6>
                                <h3>{{ $stats['liquidations'] ?? 0 }}</h3>
                            </div>
                        </div>
                    </div>
                </div>

                <h5>Recent Activity</h5>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Wallet</th>
                                <th>Platform</th>
                                <th>Symbol</th>
                                <th>PnL</th>
                                <th>Closed At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentActivity as $activity)
                                <tr>
                                    <td>{{ $activity->wallet_address }}</td>
                                    <td>{{ $activity->platform }}</td>
                                    <td>{{ $activity->symbol }}</td>
                                    <td>{{ $activity->realized_pnl }}</td>
                                    <td>{{ $activity->closed_at }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">No recent activity.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <h5 class="mt-4">Platform Status</h5>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Platform</th>
                                <th>Enabled</th>
                                <th>Rate Limit</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($platformHealth as $platform)
                                <tr>
                                    <td>{{ $platform['platform'] }}</td>
                                    <td>{{ $platform['enabled'] ? 'Enabled' : 'Disabled' }}</td>
                                    <td>{{ $platform['rate_limit'] ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
