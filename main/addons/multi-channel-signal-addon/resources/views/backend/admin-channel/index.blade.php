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
                        <a href="{{ route('admin.channels.create') }}" class="btn btn-sm btn-primary">
                            <i class="fa fa-plus"></i> {{ __('Create Channel') }}
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

                    @if (session('info'))
                        <div class="alert alert-info alert-dismissible fade show m-3" role="alert">
                            {{ session('info') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    @php
                        $pendingMtproto = $channels->filter(function($ch) {
                            return $ch->type === 'telegram_mtproto' && $ch->status === 'pending';
                        });
                    @endphp
                    @if ($pendingMtproto->count() > 0)
                        <div class="alert alert-warning m-3">
                            <i class="fa fa-exclamation-triangle"></i>
                            <strong>{{ __('Authentication Required!') }}</strong><br>
                            {{ __('You have') }} {{ $pendingMtproto->count() }} {{ __('Telegram MTProto channel(s) that require user authentication.') }}
                            {{ __('Click the "Authenticate" button to complete setup.') }}
                            <strong>{{ __('Note: This requires your phone number, NOT a bot token!') }}</strong>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('ID') }}</th>
                                    <th>{{ __('Name') }}</th>
                                    <th>{{ __('Type') }}</th>
                                    <th>{{ __('Assignment') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Last Processed') }}</th>
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
                                            @elseif ($channel->status === 'pending')
                                                @if ($channel->type === 'telegram_mtproto')
                                                    <span class="badge bg-info">{{ __('Pending - Authentication Required') }}</span>
                                                @else
                                                    <span class="badge bg-secondary">{{ __('Pending') }}</span>
                                                @endif
                                            @else
                                                <span class="badge bg-secondary">{{ __('Pending') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $channel->last_processed_at ? $channel->last_processed_at->format('d M Y H:i') : __('Never') }}
                                        </td>
                                        <td class="text-end">
                                            @if ($channel->type === 'telegram_mtproto' && $channel->status === 'pending')
                                                <a href="{{ route('admin.channels.authenticate', $channel->id) }}"
                                                    class="btn btn-xs btn-primary" title="{{ __('Authenticate - User Login Required') }}">
                                                    <i class="fa fa-key"></i> {{ __('Authenticate') }}
                                                </a>
                                            @endif
                                            <a href="{{ route('admin.channels.assign', $channel->id) }}"
                                                class="btn btn-xs btn-outline-primary" title="{{ __('Manage Assignments') }}">
                                                <i class="fa fa-users"></i>
                                            </a>
                                            <a href="{{ route('admin.channels.edit', $channel->id) }}"
                                                class="btn btn-xs btn-outline-secondary" title="{{ __('Edit') }}">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            @if ($channel->status === 'active')
                                                <form action="{{ route('admin.channels.status', $channel->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="status" value="paused">
                                                    <button type="submit" class="btn btn-xs btn-outline-warning" title="{{ __('Pause') }}">
                                                        <i class="fa fa-pause"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <form action="{{ route('admin.channels.status', $channel->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="status" value="active">
                                                    <button type="submit" class="btn btn-xs btn-outline-success" title="{{ __('Resume') }}">
                                                        <i class="fa fa-play"></i>
                                                    </button>
                                                </form>
                                            @endif
                                            <form action="{{ route('admin.channels.destroy', $channel->id) }}" method="POST" class="d-inline"
                                                onsubmit="return confirm('{{ __('Are you sure?') }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-xs btn-outline-danger" title="{{ __('Delete') }}">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <i class="fa fa-inbox fa-2x text-muted mb-2"></i>
                                            <p class="mb-0 text-muted">{{ __('No admin channels found.') }}</p>
                                            <a href="{{ route('admin.channels.create') }}" class="btn btn-sm btn-primary mt-2">
                                                {{ __('Create First Channel') }}
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

