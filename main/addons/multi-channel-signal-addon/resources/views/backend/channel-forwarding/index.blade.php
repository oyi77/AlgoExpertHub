@extends('backend.layout.master')

@section('element')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header site-card-header justify-content-between flex-wrap gap-2">
                    <div class="card-header-left">
                        <h4 class="card-title">{{ $title }}</h4>
                    </div>
                    <div class="card-header-right">
                        <a href="{{ route('admin.signal-sources.index') }}" class="btn btn-sm btn-outline-primary">
                            <i class="fa fa-plug"></i> {{ __('Manage Signal Sources') }}
                        </a>
                    </div>
                </div>

                <div class="card-body p-0">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
                            {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
                            {{ session('error') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('ID') }}</th>
                                    <th>{{ __('Channel Name') }}</th>
                                    <th>{{ __('Type') }}</th>
                                    <th>{{ __('Owner') }}</th>
                                    <th>{{ __('Assignment') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th class="text-end">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($channels as $channel)
                                    <tr>
                                        <td>{{ $channel->id }}</td>
                                        <td>
                                            <strong>{{ $channel->name }}</strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $channel->type)) }}</span>
                                        </td>
                                        <td>
                                            @if ($channel->is_admin_owned)
                                                <span class="badge bg-primary">{{ __('Admin') }}</span>
                                            @elseif ($channel->user)
                                                <span class="badge bg-secondary">{{ $channel->user->username ?? 'User #' . $channel->user_id }}</span>
                                            @else
                                                <span class="badge bg-secondary">{{ __('Unknown') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $summary = $channel->assignment_summary ?? ['label' => 'Not assigned'];
                                            @endphp
                                            <span class="badge bg-secondary">{{ $summary['label'] }}</span>
                                        </td>
                                        <td>
                                            @if ($channel->status === 'active')
                                                <span class="badge bg-success">{{ __('Active') }}</span>
                                            @elseif ($channel->status === 'paused')
                                                <span class="badge bg-warning">{{ __('Paused') }}</span>
                                            @elseif ($channel->status === 'error')
                                                <span class="badge bg-danger">{{ __('Error') }}</span>
                                            @else
                                                <span class="badge bg-secondary">{{ __('Pending') }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('admin.channel-forwarding.show', $channel->id) }}" 
                                                   class="btn btn-xs btn-outline-info" 
                                                   title="{{ __('View Details') }}">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                
                                                @if ($channel->type === 'telegram_mtproto')
                                                    @if (empty($channel->config['channels'] ?? []))
                                                    <a href="{{ route('admin.channel-forwarding.select-channel', $channel->id) }}" 
                                                       class="btn btn-xs btn-outline-primary" 
                                                           title="{{ __('Select Channels') }}">
                                                            <i class="fa fa-check-circle"></i> {{ __('Select Channels') }}
                                                        </a>
                                                    @else
                                                        <a href="{{ route('admin.channel-forwarding.select-channel', $channel->id) }}" 
                                                           class="btn btn-xs btn-outline-warning" 
                                                           title="{{ __('Re-select Channels') }}">
                                                            <i class="fa fa-edit"></i> {{ __('Re-select') }}
                                                        </a>
                                                    @endif
                                                @endif
                                                
                                                @if ($channel->type === 'telegram_mtproto' && !empty($channel->config['channels'] ?? []))
                                                    <a href="{{ route('admin.channel-forwarding.view-samples', $channel->id) }}" 
                                                       class="btn btn-xs btn-outline-info" 
                                                       title="{{ __('View Sample Messages & Create Parser') }}">
                                                        <i class="fa fa-eye"></i> {{ __('Samples') }}
                                                    </a>
                                                @endif
                                                
                                                @if ($channel->is_admin_owned)
                                                    <a href="{{ route('admin.channel-forwarding.assign', $channel->id) }}" 
                                                       class="btn btn-xs btn-outline-primary" 
                                                       title="{{ __('Assign to Users/Plans') }}">
                                                        <i class="fa fa-users"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <i class="fa fa-inbox fa-2x text-muted mb-2"></i>
                                            <p class="mb-0 text-muted">{{ __('No channels found.') }}</p>
                                            <a href="{{ route('admin.signal-sources.create') }}" class="btn btn-sm btn-primary mt-2">
                                                {{ __('Create Signal Source First') }}
                                            </a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if ($channels->hasPages())
                    <div class="card-footer">
                        {{ $channels->withQueryString()->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

