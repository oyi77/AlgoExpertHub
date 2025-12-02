@extends('backend.layout.master')

@section('element')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.channel-forwarding.index') }}">
                        <i class="fa fa-arrow-left"></i> {{ __('Back') }}
                    </a>
                    <h4 class="card-title mb-0">{{ $title }}: {{ $channel->name }}</h4>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
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
                                        @elseif ($channel->status === 'paused')
                                            <span class="badge bg-warning">{{ __('Paused') }}</span>
                                        @else
                                            <span class="badge bg-secondary">{{ __('Inactive') }}</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>{{ __('Owner') }}</strong></td>
                                    <td>
                                        @if ($channel->is_admin_owned)
                                            <span class="badge bg-primary">{{ __('Admin') }}</span>
                                        @elseif ($channel->user)
                                            <span class="badge bg-secondary">{{ $channel->user->username ?? 'User #' . $channel->user_id }}</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>{{ __('Last Processed') }}</strong></td>
                                    <td>{{ $channel->last_processed_at ? $channel->last_processed_at->diffForHumans() : __('Never') }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5>{{ __('Assignment Summary') }}</h5>
                            @php
                                $summary = $assignment_summary ?? ['label' => 'Not assigned'];
                            @endphp
                            <p><strong>{{ __('Status') }}:</strong> {{ $summary['label'] }}</p>
                            
                            @if ($channel->is_admin_owned)
                                <a href="{{ route('admin.channel-forwarding.assign', $channel->id) }}" class="btn btn-sm btn-primary">
                                    <i class="fa fa-users"></i> {{ __('Manage Assignments') }}
                                </a>
                            @endif
                            
                            @if ($channel->type === 'telegram_mtproto')
                                @if (!empty($channel->config['channels'] ?? []))
                                    <a href="{{ route('admin.channel-forwarding.view-samples', $channel->id) }}" class="btn btn-sm btn-info">
                                        <i class="fa fa-eye"></i> {{ __('View Sample Messages & Create Parser') }}
                                    </a>
                                    <a href="{{ route('admin.channel-forwarding.select-channel', $channel->id) }}" class="btn btn-sm btn-warning">
                                        <i class="fa fa-edit"></i> {{ __('Re-select Channels') }}
                                    </a>
                                @else
                                    <a href="{{ route('admin.channel-forwarding.select-channel', $channel->id) }}" class="btn btn-sm btn-primary">
                                        <i class="fa fa-check-circle"></i> {{ __('Select Channels') }}
                                    </a>
                                @endif
                            @endif
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <h5>{{ __('Forwarded Signals') }}</h5>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>{{ __('ID') }}</th>
                                            <th>{{ __('Pair') }}</th>
                                            <th>{{ __('Direction') }}</th>
                                            <th>{{ __('Status') }}</th>
                                            <th>{{ __('Created') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($signals as $signal)
                                            <tr>
                                                <td>{{ $signal->id }}</td>
                                                <td>{{ $signal->pair->name ?? 'N/A' }}</td>
                                                <td>
                                                    <span class="badge bg-{{ $signal->direction === 'buy' ? 'success' : 'danger' }}">
                                                        {{ strtoupper($signal->direction) }}
                                                    </span>
                                                </td>
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
                                                <td colspan="5" class="text-center py-4">
                                                    <i class="fa fa-inbox fa-2x text-muted mb-2"></i>
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
    </div>
@endsection

