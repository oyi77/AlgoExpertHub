@extends(Config::theme() . 'layout.auth')

@section('title')
    {{ $title }}
@endsection

@section('content')
    <div class="sp_site_card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0">{{ __($title) }}</h4>
                @if(isset($connection))
                <div>
                    <a href="{{ route('user.execution-analytics.export.csv', ['connection_id' => $connection->id, 'days' => request('days', 30)]) }}" class="btn btn-sm btn-success">
                        <i class="fas fa-file-csv"></i> Export CSV
                    </a>
                    <a href="{{ route('user.execution-analytics.export.json', ['connection_id' => $connection->id, 'days' => request('days', 30)]) }}" class="btn btn-sm btn-info">
                        <i class="fas fa-file-code"></i> Export JSON
                    </a>
                </div>
                @endif
            </div>
        </div>
        <div class="card-body">
                        @if(isset($connection) && isset($summary))
                            <div class="row mb-4">
                                <div class="col-md-3">
                                    <div class="card bg-primary text-white">
                                        <div class="card-body">
                                            <h5>Total Trades</h5>
                                            <h3>{{ $summary['total_trades'] }}</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-success text-white">
                                        <div class="card-body">
                                            <h5>Win Rate</h5>
                                            <h3>{{ $summary['win_rate'] }}%</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-info text-white">
                                        <div class="card-body">
                                            <h5>Total PnL</h5>
                                            <h3>{{ $summary['total_pnl'] }}</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-warning text-white">
                                        <div class="card-body">
                                            <h5>Profit Factor</h5>
                                            <h3>{{ $summary['profit_factor'] }}</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                        <p class="text-muted">{{ __('Select a connection to view analytics') }}</p>
        </div>
    </div>
@endsection

