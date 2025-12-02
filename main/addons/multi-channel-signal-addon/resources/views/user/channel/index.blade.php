@extends(Config::theme() . 'layout.auth')

@section('content')
    <div class="row gy-4">
        <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h4 class="mb-0">{{ __('My Channels') }}</h4>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('user.channels.create', ['type' => 'telegram']) }}" class="btn sp_theme_btn">
                    <i class="lab la-telegram-plane me-1"></i> {{ __('Add Telegram Channel') }}
                </a>
                <a href="{{ route('user.channels.create', ['type' => 'telegram_mtproto']) }}" class="btn btn-outline-primary">
                    <i class="las la-mobile me-1"></i> {{ __('Add Telegram MTProto') }}
                </a>
                <a href="{{ route('user.channels.create', ['type' => 'api']) }}" class="btn btn-outline-secondary">
                    <i class="las la-code-branch me-1"></i> {{ __('Add API Channel') }}
                </a>
                <a href="{{ route('user.channels.create', ['type' => 'web_scrape']) }}" class="btn btn-outline-info">
                    <i class="las la-spider me-1"></i> {{ __('Add Web Scrape') }}
                </a>
                <a href="{{ route('user.channels.create', ['type' => 'rss']) }}" class="btn btn-outline-success">
                    <i class="las la-rss me-1"></i> {{ __('Add RSS Feed') }}
                </a>
            </div>
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
                        <h5 class="mb-1 text-success">{{ __('Active') }}</h5>
                        <span class="fw-semibold fs-4 text-success">{{ $stats['active'] }}</span>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="sp_site_card text-center">
                        <h5 class="mb-1 text-warning">{{ __('Paused') }}</h5>
                        <span class="fw-semibold fs-4 text-warning">{{ $stats['paused'] }}</span>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="sp_site_card text-center">
                        <h5 class="mb-1 text-danger">{{ __('Error') }}</h5>
                        <span class="fw-semibold fs-4 text-danger">{{ $stats['error'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="sp_site_card">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <form class="row g-2 align-items-end" method="get" action="{{ route('user.channels.index') }}">
                        <div class="col-auto">
                            <label class="form-label d-block">{{ __('Type') }}</label>
                            <select name="type" class="form-select">
                                <option value="">{{ __('All Types') }}</option>
                                @foreach (['telegram' => 'Telegram', 'telegram_mtproto' => 'Telegram MTProto', 'api' => 'API', 'web_scrape' => 'Web Scrape', 'rss' => 'RSS'] as $value => $label)
                                    <option value="{{ $value }}" {{ request('type') === $value ? 'selected' : '' }}>
                                        {{ __($label) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-auto">
                            <label class="form-label d-block">{{ __('Status') }}</label>
                            <select name="status" class="form-select">
                                <option value="">{{ __('All Statuses') }}</option>
                                @foreach (['active' => 'Active', 'paused' => 'Paused', 'pending' => 'Pending', 'error' => 'Error'] as $value => $label)
                                    <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>
                                        {{ __($label) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn sp_theme_btn">{{ __('Filter') }}</button>
                        </div>
                    </form>
                    <a href="{{ route('user.channels.index') }}" class="btn btn-sm btn-light">
                        <i class="las la-sync-alt me-1"></i> {{ __('Reset') }}
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table sp_site_table mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('Name') }}</th>
                                    <th>{{ __('Type') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Default Plan') }}</th>
                                    <th>{{ __('Last Processed') }}</th>
                                    <th>{{ __('Errors') }}</th>
                                    <th class="text-end">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($channels as $channel)
                                    <tr>
                                        <td data-caption="{{ __('Name') }}">
                                            <span class="fw-semibold d-block">{{ $channel->name }}</span>
                                            <small class="text-muted">{{ $channel->config['chat_title'] ?? $channel->config['feed_url'] ?? $channel->config['url'] ?? '' }}</small>
                                        </td>
                                        <td data-caption="{{ __('Type') }}">
                                            <span class="badge bg-primary text-uppercase">{{ str_replace('_', ' ', $channel->type) }}</span>
                                        </td>
                                        <td data-caption="{{ __('Status') }}">
                                            @php
                                                $statusClass = match ($channel->status) {
                                                    'active' => 'bg-success',
                                                    'paused' => 'bg-warning',
                                                    'pending' => 'bg-info',
                                                    default => 'bg-danger',
                                                };
                                            @endphp
                                            <span class="badge {{ $statusClass }}">{{ __($channel->status) }}</span>
                                        </td>
                                        <td data-caption="{{ __('Default Plan') }}">
                                            {{ optional($channel->defaultPlan)->name ?? __('Not Set') }}
                                        </td>
                                        <td data-caption="{{ __('Last Processed') }}">
                                            {{ $channel->last_processed_at ? $channel->last_processed_at->diffForHumans() : __('Never') }}
                                        </td>
                                        <td data-caption="{{ __('Errors') }}">
                                            @if ($channel->error_count)
                                                <span class="text-danger fw-semibold">{{ $channel->error_count }}</span>
                                                <small class="d-block text-muted">{{ $channel->last_error }}</small>
                                            @else
                                                <span class="text-muted">0</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1">
                                                @if ($channel->type === 'telegram_mtproto' && $channel->status === 'pending')
                                                    <a href="{{ route('user.channels.authenticate', $channel->id) }}"
                                                        class="btn btn-sm btn-outline-info">
                                                        <i class="las la-key"></i> {{ __('Authenticate') }}
                                                    </a>
                                                @endif

                                                <form action="{{ route('user.channels.status', $channel->id) }}" method="post">
                                                    @csrf
                                                    <input type="hidden" name="status" value="{{ $channel->status === 'active' ? 'paused' : 'active' }}">
                                                    <button type="submit" class="btn btn-sm {{ $channel->status === 'active' ? 'btn-outline-warning' : 'btn-outline-success' }}">
                                                        <i class="{{ $channel->status === 'active' ? 'las la-pause' : 'las la-play' }}"></i>
                                                        {{ $channel->status === 'active' ? __('Pause') : __('Resume') }}
                                                    </button>
                                                </form>

                                                <form action="{{ route('user.channels.destroy', $channel->id) }}" method="post" onsubmit="return confirm('{{ __('Are you sure you want to delete this channel?') }}');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="las la-trash-alt"></i> {{ __('Delete') }}
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-5">
                                            <i class="las la-satellite-dish fs-1 mb-3 text-muted d-block"></i>
                                            <h5 class="mb-1">{{ __('No channels connected yet') }}</h5>
                                            <p class="text-muted mb-3">{{ __('Connect your first signal source to start receiving automatic signals.') }}</p>
                                            <a href="{{ route('user.channels.create') }}" class="btn sp_theme_btn">
                                                <i class="las la-plus-circle me-1"></i> {{ __('Add Channel') }}
                                            </a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="mt-3">
                    {{ $channels->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection

