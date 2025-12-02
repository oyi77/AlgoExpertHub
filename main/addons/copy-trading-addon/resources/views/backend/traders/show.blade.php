@extends('backend.layout.master')

@section('title')
    {{ $title }}
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4>{{ $title }} - {{ $trader->name ?? ($trader->username ?? $trader->email ?? 'N/A') }}</h4>
                        <span class="badge badge-{{ $trader_type === 'admin' ? 'info' : 'success' }}">
                            {{ ucfirst($trader_type) }}
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5>Statistics</h5>
                                <p>Win Rate: <strong>{{ number_format($stats['win_rate'] ?? 0, 2) }}%</strong></p>
                                <p>Total PnL: <strong class="{{ ($stats['total_pnl'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                    ${{ number_format($stats['total_pnl'] ?? 0, 2) }}
                                </strong></p>
                                <p>Total Trades: <strong>{{ $stats['total_trades'] ?? 0 }}</strong></p>
                                <p>Followers: <strong>{{ $stats['follower_count'] ?? 0 }}</strong></p>
                                <p>Status: 
                                    <span class="badge badge-{{ $setting->is_enabled ? 'success' : 'secondary' }}">
                                        {{ $setting->is_enabled ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <h5>Settings</h5>
                                <p>Max Followers: <strong>{{ $setting->max_copiers ?? 'Unlimited' }}</strong></p>
                                <p>Min Follower Balance: <strong>{{ $setting->min_followers_balance ? '$' . number_format($setting->min_followers_balance, 2) : 'None' }}</strong></p>
                                <p>Default Risk Multiplier: <strong>{{ $setting->risk_multiplier_default }}x</strong></p>
                                <p>Allow Manual Trades: <strong>{{ $setting->allow_manual_trades ? 'Yes' : 'No' }}</strong></p>
                                <p>Allow Auto Trades: <strong>{{ $setting->allow_auto_trades ? 'Yes' : 'No' }}</strong></p>
                            </div>
                        </div>

                        <h5>Subscriptions ({{ $subscriptions->count() }})</h5>
                        @if($subscriptions->count() > 0)
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Follower</th>
                                            <th>Copy Mode</th>
                                            <th>Status</th>
                                            <th>Subscribed At</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($subscriptions as $subscription)
                                            <tr>
                                                <td>{{ $subscription->follower->username ?? $subscription->follower->email }}</td>
                                                <td>
                                                    <span class="badge badge-{{ $subscription->copy_mode === 'easy' ? 'primary' : 'info' }}">
                                                        {{ ucfirst($subscription->copy_mode) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-{{ $subscription->is_active ? 'success' : 'secondary' }}">
                                                        {{ $subscription->is_active ? 'Active' : 'Inactive' }}
                                                    </span>
                                                </td>
                                                <td>{{ $subscription->subscribed_at ? $subscription->subscribed_at->format('Y-m-d H:i') : 'N/A' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted">No active subscriptions</p>
                        @endif

                        <div class="mt-3">
                            <form action="{{ route('admin.copy-trading.traders.toggle', $setting->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-{{ $setting->is_enabled ? 'warning' : 'success' }}">
                                    {{ $setting->is_enabled ? 'Disable' : 'Enable' }} Trader
                                </button>
                            </form>
                            <a href="{{ route('admin.copy-trading.traders.index') }}" class="btn btn-secondary">Back</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

