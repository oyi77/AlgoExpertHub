@extends(Config::theme() . 'layout.auth')

@section('content')
    <div class="row gy-4">
        <div class="col-12">
            <div class="sp_site_card">
                <div class="card-header">
                    <h4>{{ __($title) }}</h4>
                </div>
                <div class="card-body">
                    @if($executions->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>{{ __('Trader') }}</th>
                                        <th>{{ __('Symbol') }}</th>
                                        <th>{{ __('Direction') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th>{{ __('Date') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($executions as $execution)
                                        <tr>
                                            <td>
                                                <strong>{{ $execution->trader->username ?? 'Trader #' . $execution->trader_id }}</strong>
                                            </td>
                                            <td>{{ $execution->symbol ?? '-' }}</td>
                                            <td>
                                                @if(isset($execution->direction))
                                                    <span class="badge badge-{{ $execution->direction === 'buy' ? 'success' : 'danger' }}">
                                                        {{ strtoupper($execution->direction) }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if(isset($execution->status))
                                                    <span class="badge badge-info">{{ $execution->status }}</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>{{ $execution->created_at->format('Y-m-d H:i') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if ($executions->hasPages())
                            <div class="mt-3">
                                {{ $executions->links() }}
                            </div>
                        @endif
                    @else
                        <div class="text-center py-5">
                            <p>{{ __('No copy trading history found.') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
