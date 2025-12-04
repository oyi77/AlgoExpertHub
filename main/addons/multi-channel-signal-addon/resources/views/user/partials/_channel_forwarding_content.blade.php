<div class="row gy-4">
    <div class="col-12">
        <h4 class="mb-0">{{ __('Channel Forwarding') }}</h4>
        <p class="text-muted">{{ __('Channels assigned to you by admin or through your plan') }}</p>
    </div>

    <div class="col-12">
        <div class="row g-3">
            <div class="col-sm-6 col-lg-3">
                <div class="sp_site_card text-center">
                    <h5 class="mb-1">{{ __('Total') }}</h5>
                    <span class="fw-semibold fs-4">{{ $stats['total'] }}</span>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="sp_site_card text-center">
                    <h5 class="mb-1 text-primary">{{ __('By User') }}</h5>
                    <span class="fw-semibold fs-4 text-primary">{{ $stats['by_user'] }}</span>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="sp_site_card text-center">
                    <h5 class="mb-1 text-info">{{ __('By Plan') }}</h5>
                    <span class="fw-semibold fs-4 text-info">{{ $stats['by_plan'] }}</span>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="sp_site_card text-center">
                    <h5 class="mb-1 text-success">{{ __('Global') }}</h5>
                    <span class="fw-semibold fs-4 text-success">{{ $stats['global'] }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="sp_site_card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Channel Name') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('Assigned Via') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($channels as $channel)
                            <tr>
                                <td>
                                    <strong>{{ $channel->name }}</strong>
                                </td>
                                <td>
                                    <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $channel->type)) }}</span>
                                </td>
                                <td>
                                    @php
                                        $info = $channel->assignment_info ?? ['type' => 'none', 'description' => 'Not assigned'];
                                    @endphp
                                    <span class="badge bg-{{ $info['type'] === 'global' ? 'success' : ($info['type'] === 'plan' ? 'info' : ($info['type'] === 'user' ? 'primary' : 'secondary')) }}">
                                        {{ $info['description'] }}
                                    </span>
                                </td>
                                <td>
                                    @if ($channel->status === 'active')
                                        <span class="badge bg-success">{{ __('Active') }}</span>
                                    @elseif ($channel->status === 'paused')
                                        <span class="badge bg-warning">{{ __('Paused') }}</span>
                                    @else
                                        <span class="badge bg-secondary">{{ __('Inactive') }}</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('user.channel-forwarding.show', $channel->id) }}" 
                                       class="btn btn-xs btn-outline-info" 
                                       title="{{ __('View Details') }}">
                                        <i class="las la-eye"></i> {{ __('View') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <i class="las la-inbox la-2x text-muted mb-2"></i>
                                    <p class="mb-0 text-muted">{{ __('No channels assigned to you yet.') }}</p>
                                    <small class="text-muted">{{ __('Contact admin to assign channels to your account or plan.') }}</small>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($channels->hasPages())
                <div class="mt-3">
                    {{ $channels->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

