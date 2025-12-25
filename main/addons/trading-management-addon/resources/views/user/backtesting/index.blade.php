@extends(Config::theme() . 'layout.auth')

@section('content')
    <div class="row gy-4">
        <div class="col-12">
            <div class="sp_site_card">
                <div class="card-header">
                    <div class="d-flex flex-wrap align-items-center justify-content-between">
                        <div>
                            <h4>{{ __('Backtesting Center') }}</h4>
                            <p class="text-muted mb-0">{{ __('Test your trading strategies on historical data') }}</p>
                        </div>
                        <div>
                            <a href="{{ route('user.backtesting.create') }}" class="btn btn-sm sp_theme_btn">
                                <i class="las la-plus"></i> {{ __('Create Backtest') }}
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Filter Tabs -->
                <div class="card-body border-bottom">
                    <ul class="nav nav-pills" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link {{ $activeFilter === 'all' ? 'active' : '' }}" 
                               href="{{ route('user.backtesting.index', ['status' => 'all']) }}">
                                {{ __('All') }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $activeFilter === 'completed' ? 'active' : '' }}" 
                               href="{{ route('user.backtesting.index', ['status' => 'completed']) }}">
                                {{ __('Completed') }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $activeFilter === 'running' ? 'active' : '' }}" 
                               href="{{ route('user.backtesting.index', ['status' => 'running']) }}">
                                {{ __('Running') }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $activeFilter === 'pending' ? 'active' : '' }}" 
                               href="{{ route('user.backtesting.index', ['status' => 'pending']) }}">
                                {{ __('Pending') }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $activeFilter === 'failed' ? 'active' : '' }}" 
                               href="{{ route('user.backtesting.index', ['status' => 'failed']) }}">
                                {{ __('Failed') }}
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="card-body">
                    @if($backtests->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>{{ __('Name') }}</th>
                                        <th>{{ __('Symbol') }}</th>
                                        <th>{{ __('Timeframe') }}</th>
                                        <th>{{ __('Date Range') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th>{{ __('Performance') }}</th>
                                        <th>{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($backtests as $backtest)
                                        <tr>
                                            <td>
                                                <strong>{{ $backtest->name }}</strong>
                                                @if($backtest->description)
                                                    <br><small class="text-muted">{{ Str::limit($backtest->description, 40) }}</small>
                                                @endif
                                            </td>
                                            <td>{{ $backtest->symbol }}</td>
                                            <td>{{ strtoupper($backtest->timeframe) }}</td>
                                            <td>
                                                <small>
                                                    {{ $backtest->start_date->format('Y-m-d') }}<br>
                                                    {{ $backtest->end_date->format('Y-m-d') }}
                                                </small>
                                            </td>
                                            <td>
                                                @if($backtest->status === 'completed')
                                                    <span class="badge bg-success">{{ __('Completed') }}</span>
                                                @elseif($backtest->status === 'running')
                                                    <span class="badge bg-info">{{ __('Running') }} {{ $backtest->progress_percent }}%</span>
                                                @elseif($backtest->status === 'pending')
                                                    <span class="badge bg-warning">{{ __('Pending') }}</span>
                                                @elseif($backtest->status === 'failed')
                                                    <span class="badge bg-danger">{{ __('Failed') }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($backtest->result)
                                                    <span class="badge {{ $backtest->result->isProfitable() ? 'bg-success' : 'bg-danger' }}">
                                                        {{ $backtest->result->return_percent > 0 ? '+' : '' }}{{ number_format($backtest->result->return_percent, 2) }}%
                                                    </span>
                                                    <br><small class="text-muted">{{ $backtest->result->grade }}</small>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('user.backtesting.show', $backtest->id) }}" 
                                                   class="btn btn-sm btn-info" title="{{ __('View Results') }}">
                                                    <i class="las la-eye"></i>
                                                </a>
                                                @if($backtest->status === 'pending')
                                                    <button class="btn btn-sm btn-success run-backtest-btn" 
                                                            data-id="{{ $backtest->id }}" 
                                                            title="{{ __('Run Backtest') }}">
                                                        <i class="las la-play"></i>
                                                    </button>
                                                @endif
                                                @if(!$backtest->isRunning())
                                                    <button class="btn btn-sm btn-danger delete-backtest-btn" 
                                                            data-id="{{ $backtest->id }}" 
                                                            title="{{ __('Delete') }}">
                                                        <i class="las la-trash"></i>
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if ($backtests->hasPages())
                            <div class="mt-3">
                                {{ $backtests->links() }}
                            </div>
                        @endif
                    @else
                        <div class="text-center py-5">
                            <i class="las la-chart-line" style="font-size: 4rem; color: var(--tv-primary);"></i>
                            <h5 class="mt-3">{{ __('No backtests found') }}</h5>
                            <p class="text-muted">{{ __('Start testing your trading strategies on historical data') }}</p>
                            <a href="{{ route('user.backtesting.create') }}" class="btn sp_theme_btn mt-2">
                                <i class="las la-plus"></i> {{ __('Create Your First Backtest') }}
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Run backtest
    $('.run-backtest-btn').on('click', function() {
        const btn = $(this);
        const backtestId = btn.data('id');
        
        if (!confirm('{{ __("Start running this backtest? This may take a few minutes.") }}')) {
            return;
        }
        
        btn.prop('disabled', true).html('<i class="las la-spinner la-spin"></i>');
        
        $.ajax({
            url: `/trading/backtesting/${backtestId}/run`,
            method: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    setTimeout(() => location.reload(), 1500);
                } else {
                    toastr.error(response.message);
                    btn.prop('disabled', false).html('<i class="las la-play"></i>');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || '{{ __("Failed to start backtest") }}');
                btn.prop('disabled', false).html('<i class="las la-play"></i>');
            }
        });
    });
    
    // Delete backtest
    $('.delete-backtest-btn').on('click', function() {
        const btn = $(this);
        const backtestId = btn.data('id');
        
        if (!confirm('{{ __("Are you sure you want to delete this backtest? This cannot be undone.") }}')) {
            return;
        }
        
        btn.prop('disabled', true).html('<i class="las la-spinner la-spin"></i>');
        
        $.ajax({
            url: `/trading/backtesting/${backtestId}`,
            method: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    toastr.error(response.message);
                    btn.prop('disabled', false).html('<i class="las la-trash"></i>');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || '{{ __("Failed to delete backtest") }}');
                btn.prop('disabled', false).html('<i class="las la-trash"></i>');
            }
        });
    });
});
</script>
@endpush
