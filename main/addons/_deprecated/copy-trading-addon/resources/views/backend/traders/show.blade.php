@extends('backend.layout.master')

@section('title')
    {{ $title }}
@endsection

@section('element')
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12">
                <a href="{{ route('admin.copy-trading.traders.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Traders
                </a>
            </div>
        </div>

        <!-- Trader Info Card -->
        <div class="row">
            <div class="col-md-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Trader Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <strong>Name:</strong> {{ $trader->name }}
                        </div>
                        <div class="mb-3">
                            <strong>Email:</strong> {{ $trader->email ?? 'N/A' }}
                        </div>
                        <div class="mb-3">
                            <strong>Type:</strong> 
                            <span class="badge badge-{{ $trader_type === 'admin' ? 'danger' : 'primary' }}">
                                {{ ucfirst($trader_type) }}
                            </span>
                        </div>
                        <div class="mb-3">
                            <strong>Status:</strong> 
                            <span class="badge badge-{{ $setting->is_enabled ? 'success' : 'secondary' }}">
                                {{ $setting->is_enabled ? 'Enabled' : 'Disabled' }}
                            </span>
                        </div>
                        <div class="mb-3">
                            <strong>Joined:</strong> {{ $trader->created_at->format('Y-m-d') }}
                        </div>

                        <hr>

                        <h6 class="font-weight-bold">Copy Trading Settings</h6>
                        <div class="mb-2">
                            <small><strong>Min Follower Balance:</strong> {{ $setting->min_followers_balance ?? 'None' }}</small>
                        </div>
                        <div class="mb-2">
                            <small><strong>Max Copiers:</strong> {{ $setting->max_copiers ?? 'Unlimited' }}</small>
                        </div>
                        <div class="mb-2">
                            <small><strong>Allow Manual Trades:</strong> {{ $setting->allow_manual_trades ? 'Yes' : 'No' }}</small>
                        </div>
                        <div class="mb-2">
                            <small><strong>Allow Auto Trades:</strong> {{ $setting->allow_auto_trades ? 'Yes' : 'No' }}</small>
                        </div>

                        <hr>

                        <form action="{{ route('admin.copy-trading.traders.toggle', $setting->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-block btn-{{ $setting->is_enabled ? 'warning' : 'success' }}"
                                    onclick="return confirm('Are you sure you want to {{ $setting->is_enabled ? 'disable' : 'enable' }} this trader?')">
                                <i class="fas fa-{{ $setting->is_enabled ? 'ban' : 'check' }}"></i> 
                                {{ $setting->is_enabled ? 'Disable' : 'Enable' }} Trader
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Followers</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['follower_count'] ?? 0 }}</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Win Rate</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['win_rate'] ?? 0, 2) }}%</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-percentage fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total P&L</div>
                                        <div class="h5 mb-0 font-weight-bold {{ ($stats['total_pnl'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ ($stats['total_pnl'] ?? 0) >= 0 ? '+' : '' }}{{ number_format($stats['total_pnl'] ?? 0, 2) }}
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Trades</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['total_trades'] ?? 0 }}</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Subscriptions Card -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Active Subscriptions</h6>
                    </div>
                    <div class="card-body">
                        @if($subscriptions->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Follower</th>
                                        <th>Copy Mode</th>
                                        <th>Risk Multiplier</th>
                                        <th>Status</th>
                                        <th>Started</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($subscriptions as $subscription)
                                    <tr>
                                        <td>{{ $subscription->follower->username ?? $subscription->follower->email ?? 'User #' . $subscription->follower_id }}</td>
                                        <td>
                                            <span class="badge badge-info">
                                                {{ ucfirst($subscription->copy_mode ?? 'easy') }}
                                            </span>
                                        </td>
                                        <td>{{ $subscription->risk_multiplier ?? 1.0 }}x</td>
                                        <td>
                                            <span class="badge badge-{{ $subscription->is_active ? 'success' : 'secondary' }}">
                                                {{ $subscription->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td>{{ $subscription->created_at->format('Y-m-d') }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div class="alert alert-info">
                            No active subscriptions yet.
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
