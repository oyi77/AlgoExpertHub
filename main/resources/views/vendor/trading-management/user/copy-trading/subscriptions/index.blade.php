@extends(Config::theme() . 'layout.auth')

@section('content')
    <div class="row gy-4">
        <div class="col-12">
            <div class="sp_site_card">
                <div class="card-header">
                    <h4>{{ __($title) }}</h4>
                </div>
                <div class="card-body">
                    @if($subscriptions->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>{{ __('Trader') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th>{{ __('Risk Multiplier') }}</th>
                                        <th>{{ __('Subscribed') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($subscriptions as $subscription)
                                        <tr>
                                            <td>
                                                <strong>{{ $subscription->trader->username ?? 'Trader #' . $subscription->trader_id }}</strong>
                                            </td>
                                            <td>
                                                @if($subscription->is_active)
                                                    <span class="badge badge-success">{{ __('Active') }}</span>
                                                @else
                                                    <span class="badge badge-secondary">{{ __('Inactive') }}</span>
                                                @endif
                                            </td>
                                            <td>{{ $subscription->risk_multiplier ?? 1.0 }}x</td>
                                            <td>{{ $subscription->subscribed_at ? $subscription->subscribed_at->format('Y-m-d') : '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if ($subscriptions->hasPages())
                            <div class="mt-3">
                                {{ $subscriptions->links() }}
                            </div>
                        @endif
                    @else
                        <div class="text-center py-5">
                            <p>{{ __('You have no active subscriptions.') }}</p>
                            <a href="{{ route('user.copy-trading.traders.index') }}" class="btn btn-primary">
                                {{ __('Browse Traders') }}
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
