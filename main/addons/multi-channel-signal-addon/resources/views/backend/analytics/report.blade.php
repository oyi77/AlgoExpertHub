@extends('backend.layout.master')

@section('element')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header site-card-header justify-content-between flex-wrap gap-2">
                    <div class="card-header-left">
                        <h4 class="card-title">{{ __('Signal Report') }} - {{ ucfirst($period) }}</h4>
                    </div>
                    <div class="card-header-right">
                        <a href="{{ route('admin.signal-analytics.export', array_merge(request()->all(), ['format' => 'csv'])) }}" 
                           class="btn btn-sm btn-success">
                            <i class="fa fa-download"></i> {{ __('Export CSV') }}
                        </a>
                        <a href="{{ route('admin.signal-analytics.index') }}" class="btn btn-sm btn-secondary">
                            <i class="fa fa-arrow-left"></i> {{ __('Back') }}
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.signal-analytics.report') }}" class="mb-4">
                        <div class="row">
                            <div class="col-md-3">
                                <label>{{ __('Period') }}</label>
                                <select name="period" class="form-control">
                                    <option value="daily" {{ $period == 'daily' ? 'selected' : '' }}>{{ __('Daily') }}</option>
                                    <option value="weekly" {{ $period == 'weekly' ? 'selected' : '' }}>{{ __('Weekly') }}</option>
                                    <option value="monthly" {{ $period == 'monthly' ? 'selected' : '' }}>{{ __('Monthly') }}</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label>{{ __('Date') }}</label>
                                <input type="date" name="date" class="form-control" value="{{ request('date', now()->format('Y-m-d')) }}">
                            </div>
                            <div class="col-md-3">
                                <label>{{ __('Channel') }}</label>
                                <select name="channel_source_id" class="form-control">
                                    <option value="">{{ __('All Channels') }}</option>
                                    @foreach ($channels as $channel)
                                        <option value="{{ $channel->id }}" {{ $channelSourceId == $channel->id ? 'selected' : '' }}>
                                            {{ $channel->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label>{{ __('Plan') }}</label>
                                <select name="plan_id" class="form-control">
                                    <option value="">{{ __('All Plans') }}</option>
                                    @foreach ($plans as $plan)
                                        <option value="{{ $plan->id }}" {{ $planId == $plan->id ? 'selected' : '' }}>
                                            {{ $plan->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fa fa-filter"></i> {{ __('Filter') }}
                                </button>
                            </div>
                        </div>
                    </form>

                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h5>{{ __('Total Signals') }}</h5>
                                    <h2>{{ number_format($report['total_signals']) }}</h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h5>{{ __('Closed Trades') }}</h5>
                                    <h2>{{ number_format($report['closed_trades']) }}</h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h5>{{ __('Win Rate') }}</h5>
                                    <h2>{{ number_format($report['win_rate'], 2) }}%</h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-{{ $report['total_profit_loss'] >= 0 ? 'success' : 'danger' }} text-white">
                                <div class="card-body">
                                    <h5>{{ __('Total P/L') }}</h5>
                                    <h2>{{ number_format($report['total_profit_loss'], 2) }}</h2>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>{{ __('Trade Statistics') }}</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table">
                                        <tr>
                                            <td><strong>{{ __('Published Signals') }}</strong></td>
                                            <td>{{ number_format($report['published_signals']) }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>{{ __('Profitable Trades') }}</strong></td>
                                            <td class="text-success">{{ number_format($report['profitable_trades']) }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>{{ __('Loss Trades') }}</strong></td>
                                            <td class="text-danger">{{ number_format($report['loss_trades']) }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>{{ __('Total Pips') }}</strong></td>
                                            <td>{{ number_format($report['total_pips'] ?? 0, 2) }}</td>
                                        </tr>
                                        @if (isset($report['avg_profit_loss']))
                                        <tr>
                                            <td><strong>{{ __('Average P/L') }}</strong></td>
                                            <td>{{ number_format($report['avg_profit_loss'], 2) }}</td>
                                        </tr>
                                        @endif
                                    </table>
                                </div>
                            </div>
                        </div>

                        @if (!empty($report['daily_breakdown']))
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>{{ __('Daily Breakdown') }}</h5>
                                </div>
                                <div class="card-body">
                                    <div style="max-height: 400px; overflow-y: auto;">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>{{ __('Date') }}</th>
                                                    <th>{{ __('Signals') }}</th>
                                                    <th>{{ __('Closed') }}</th>
                                                    <th>{{ __('P/L') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($report['daily_breakdown'] as $day)
                                                <tr>
                                                    <td>{{ $day['date'] }}</td>
                                                    <td>{{ $day['total_signals'] }}</td>
                                                    <td>{{ $day['closed_trades'] }}</td>
                                                    <td class="{{ $day['total_profit_loss'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                        {{ number_format($day['total_profit_loss'], 2) }}
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>

                    @if (!empty($report['signals_by_pair']))
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>{{ __('Signals by Currency Pair') }}</h5>
                                </div>
                                <div class="card-body">
                                    <div style="max-height: 300px; overflow-y: auto;">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>{{ __('Pair') }}</th>
                                                    <th>{{ __('Signals') }}</th>
                                                    <th>{{ __('Closed') }}</th>
                                                    <th>{{ __('Win Rate') }}</th>
                                                    <th>{{ __('P/L') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($report['signals_by_pair'] as $pair)
                                                <tr>
                                                    <td><strong>{{ $pair['currency_pair'] }}</strong></td>
                                                    <td>{{ $pair['total_signals'] }}</td>
                                                    <td>{{ $pair['closed_trades'] }}</td>
                                                    <td>{{ number_format($pair['win_rate'], 1) }}%</td>
                                                    <td class="{{ $pair['total_profit_loss'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                        {{ number_format($pair['total_profit_loss'], 2) }}
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if (!empty($report['top_performers']))
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>{{ __('Top Performers') }}</h5>
                                </div>
                                <div class="card-body">
                                    <div style="max-height: 300px; overflow-y: auto;">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>{{ __('Pair') }}</th>
                                                    <th>{{ __('Direction') }}</th>
                                                    <th>{{ __('P/L') }}</th>
                                                    <th>{{ __('Pips') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($report['top_performers'] as $signal)
                                                <tr>
                                                    <td><strong>{{ $signal['currency_pair'] }}</strong></td>
                                                    <td><span class="badge bg-{{ $signal['direction'] == 'buy' ? 'success' : 'danger' }}">{{ strtoupper($signal['direction']) }}</span></td>
                                                    <td class="text-success">{{ number_format($signal['profit_loss'], 2) }}</td>
                                                    <td>{{ number_format($signal['pips'], 2) }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

