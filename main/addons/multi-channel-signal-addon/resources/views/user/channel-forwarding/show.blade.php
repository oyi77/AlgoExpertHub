@extends(Config::theme() . 'layout.auth')

@section('content')
    <div class="row gy-4">
        <div class="col-12">
            <div class="sp_site_card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <a href="{{ route('user.channel-forwarding.index') }}" class="btn btn-link p-0">
                        <i class="las la-arrow-left me-1"></i> {{ __('Back') }}
                    </a>
                    <h4 class="mb-0">{{ $title }}: {{ $channel->name }}</h4>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <h5>{{ __('Channel Information') }}</h5>
                        <table class="table table-sm">
                            <tr>
                                <td><strong>{{ __('Type') }}</strong></td>
                                <td><span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $channel->type)) }}</span></td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('Status') }}</strong></td>
                                <td>
                                    @if ($channel->status === 'active')
                                        <span class="badge bg-success">{{ __('Active') }}</span>
                                    @else
                                        <span class="badge bg-secondary">{{ __('Inactive') }}</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('Assigned Via') }}</strong></td>
                                <td>
                                    @php
                                        $info = $assignment_info ?? ['type' => 'none', 'description' => 'Not assigned'];
                                    @endphp
                                    <span class="badge bg-{{ $info['type'] === 'global' ? 'success' : ($info['type'] === 'plan' ? 'info' : ($info['type'] === 'user' ? 'primary' : 'secondary')) }}">
                                        {{ $info['description'] }}
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <h5>{{ __('Forwarded Signals') }}</h5>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>{{ __('Pair') }}</th>
                                        <th>{{ __('Direction') }}</th>
                                        <th>{{ __('Entry') }}</th>
                                        <th>{{ __('SL') }}</th>
                                        <th>{{ __('TP') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th>{{ __('Created') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($signals as $signal)
                                        <tr>
                                            <td>{{ $signal->pair->name ?? 'N/A' }}</td>
                                            <td>
                                                <span class="badge bg-{{ $signal->direction === 'buy' ? 'success' : 'danger' }}">
                                                    {{ strtoupper($signal->direction) }}
                                                </span>
                                            </td>
                                            <td>{{ $signal->open_price ?? 'N/A' }}</td>
                                            <td>{{ $signal->sl ?? 'N/A' }}</td>
                                            <td>{{ $signal->tp ?? 'N/A' }}</td>
                                            <td>
                                                @if ($signal->is_published)
                                                    <span class="badge bg-success">{{ __('Published') }}</span>
                                                @else
                                                    <span class="badge bg-warning">{{ __('Draft') }}</span>
                                                @endif
                                            </td>
                                            <td>{{ $signal->created_at->diffForHumans() }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center py-4">
                                                <i class="las la-inbox la-2x text-muted mb-2"></i>
                                                <p class="mb-0 text-muted">{{ __('No signals forwarded yet.') }}</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if ($signals->hasPages())
                            <div class="mt-3">
                                {{ $signals->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

