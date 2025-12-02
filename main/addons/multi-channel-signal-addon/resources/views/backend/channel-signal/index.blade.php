@extends('backend.layout.master')

@section('element')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header site-card-header justify-content-between flex-wrap gap-2">
                    <div class="card-header-left">
                        <form action="{{ route('admin.channel-signals.index') }}" method="get" class="row g-2 align-items-end">
                            <div class="col-sm-3">
                                <label class="form-label mb-1">{{ __('Search') }}</label>
                                <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm"
                                    placeholder="{{ __('Search messages or signals') }}">
                            </div>
                            <div class="col-sm-2">
                                <label class="form-label mb-1">{{ __('Channel Source') }}</label>
                                <select name="channel_source_id" class="form-control form-control-sm">
                                    <option value="">{{ __('All Sources') }}</option>
                                    @foreach ($channelSources as $source)
                                        <option value="{{ $source->id }}" @selected(request('channel_source_id') == $source->id)>
                                            {{ $source->name }} ({{ optional($source->user)->name ?? __('System') }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-sm-2">
                                <label class="form-label mb-1">{{ __('Message Status') }}</label>
                                <select name="message_status" class="form-control form-control-sm">
                                    <option value="">{{ __('All') }}</option>
                                    <option value="pending" @selected(request('message_status') === 'pending')>{{ __('Pending') }}</option>
                                    <option value="failed" @selected(request('message_status') === 'failed')>{{ __('Failed') }}</option>
                                    <option value="manual_review" @selected(request('message_status') === 'manual_review')>{{ __('Manual Review') }}</option>
                                    <option value="processed" @selected(request('message_status') === 'processed')>{{ __('Processed') }}</option>
                                </select>
                            </div>
                            <div class="col-sm-2">
                                <label class="form-label mb-1">{{ __('Signal Status') }}</label>
                                <select name="status" class="form-control form-control-sm">
                                    <option value="">{{ __('All') }}</option>
                                    <option value="draft" @selected(request('status') === 'draft')>{{ __('Draft') }}</option>
                                    <option value="published" @selected(request('status') === 'published')>{{ __('Published') }}</option>
                                </select>
                            </div>
                            <div class="col-sm-3">
                                <button class="btn btn-sm btn-primary mt-3 mt-sm-0" type="submit">
                                    <i class="fa fa-search"></i> {{ __('Filter') }}
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="card-header-right">
                        <span class="badge bg-light text-dark">
                            {{ __('Total Items') }}: {{ $items->total() }}
                        </span>
                    </div>
                </div>

                @if (!empty($migrationPending))
                    <div class="alert alert-warning mx-3 mt-3 mb-0">
                        <strong>{{ __('Migrasi dibutuhkan') }}:</strong>
                        {{ __('Kolom auto_created belum tersedia di tabel signals. Jalankan perintah') }}
                        <code>php artisan migrate --path=main/addons/multi-channel-signal-addon/database/migrations</code>
                        {{ __('kemudian muat ulang halaman ini.') }}
                    </div>
                @endif

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('Type') }}</th>
                                    <th>{{ __('Content') }}</th>
                                    <th>{{ __('Channel') }}</th>
                                    <th>{{ __('Pair / Timeframe') }}</th>
                                    <th>{{ __('Direction') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Created At') }}</th>
                                    <th class="text-end">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($items as $item)
                                    @if($item['type'] === 'signal' && $item['signal'])
                                        {{-- Parsed Signal --}}
                                        <tr>
                                            <td>
                                                <span class="badge bg-success">
                                                    <i class="fa fa-check-circle"></i> {{ __('Parsed') }}
                                                </span>
                                            </td>
                                            <td>
                                                <strong>{{ $item['signal']->title }}</strong>
                                                <div class="small text-muted">{{ \Illuminate\Support\Str::limit($item['signal']->description, 80) }}</div>
                                                @if($item['message'])
                                                    <div class="small text-info mt-1">
                                                        <i class="fa fa-comment"></i> {{ __('Original') }}: {{ \Illuminate\Support\Str::limit($item['message']->raw_message, 60) }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td>
                                                {{ optional($item['signal']->channelSource)->name ?? __('Unknown source') }}
                                                <div class="small text-muted">
                                                    {{ optional(optional($item['signal']->channelSource)->user)->name ?? __('System linked') }}
                                                </div>
                                            </td>
                                            <td>
                                                {{ optional($item['signal']->pair)->name ?? __('N/A') }}
                                                <div class="small text-muted">{{ optional($item['signal']->time)->name ?? __('N/A') }}</div>
                                            </td>
                                            <td>
                                                @if ($item['signal']->direction === 'buy')
                                                    <span class="badge bg-success">{{ strtoupper($item['signal']->direction) }}</span>
                                                @elseif ($item['signal']->direction === 'sell')
                                                    <span class="badge bg-danger">{{ strtoupper($item['signal']->direction) }}</span>
                                                @else
                                                    <span class="badge bg-secondary">{{ __('Unknown') }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($item['signal']->is_published)
                                                    <span class="badge bg-success">{{ __('Published') }}</span>
                                                @else
                                                    <span class="badge bg-warning">{{ __('Draft') }}</span>
                                                @endif
                                            </td>
                                            <td>{{ $item['signal']->created_at?->format('d M Y H:i') }}</td>
                                            <td class="text-end">
                                                <a href="{{ route('admin.channel-signals.show', $item['signal']->id) }}"
                                                    class="btn btn-xs btn-outline-primary">
                                                    <i class="fa fa-eye"></i> {{ __('Review') }}
                                                </a>
                                                <a href="{{ route('admin.channel-signals.edit', $item['signal']->id) }}"
                                                    class="btn btn-xs btn-outline-secondary">
                                                    <i class="fa fa-edit"></i> {{ __('Edit') }}
                                                </a>
                                            </td>
                                        </tr>
                                    @elseif($item['type'] === 'message' && $item['message'])
                                        {{-- Unparsed Raw Message --}}
                                        <tr>
                                            <td>
                                                <span class="badge bg-warning">
                                                    <i class="fa fa-exclamation-triangle"></i> {{ __('Raw') }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="font-monospace small">
                                                    {{ \Illuminate\Support\Str::limit($item['message']->raw_message, 150) }}
                                                </div>
                                                @if($item['message']->error_message)
                                                    <div class="small text-danger mt-1">
                                                        <i class="fa fa-times-circle"></i> {{ $item['message']->error_message }}
                                                    </div>
                                                @endif
                                                @if($item['message']->parsed_data)
                                                    <div class="small text-info mt-1">
                                                        <i class="fa fa-info-circle"></i> {{ __('Partial parsing attempted') }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td>
                                                {{ optional($item['message']->channelSource)->name ?? __('Unknown source') }}
                                                <div class="small text-muted">
                                                    {{ optional(optional($item['message']->channelSource)->user)->name ?? __('System linked') }}
                                                </div>
                                            </td>
                                            <td>
                                                <span class="text-muted">{{ __('N/A') }}</span>
                                            </td>
                                            <td>
                                                <span class="text-muted">{{ __('N/A') }}</span>
                                            </td>
                                            <td>
                                                @if($item['message']->status === 'pending')
                                                    <span class="badge bg-info">{{ __('Pending') }}</span>
                                                @elseif($item['message']->status === 'failed')
                                                    <span class="badge bg-danger">{{ __('Failed') }}</span>
                                                @elseif($item['message']->status === 'manual_review')
                                                    <span class="badge bg-warning">{{ __('Manual Review') }}</span>
                                                @elseif($item['message']->status === 'processed')
                                                    <span class="badge bg-success">{{ __('Processed') }}</span>
                                                @else
                                                    <span class="badge bg-secondary">{{ ucfirst($item['message']->status) }}</span>
                                                @endif
                                                @if($item['message']->confidence_score !== null)
                                                    <div class="small text-muted mt-1">
                                                        {{ __('Confidence') }}: {{ $item['message']->confidence_score }}%
                                                    </div>
                                                @endif
                                            </td>
                                            <td>{{ $item['message']->created_at?->format('d M Y H:i') }}</td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-xs btn-outline-info" 
                                                    onclick="showRawMessage({{ $item['message']->id }}, {{ json_encode($item['message']->raw_message) }})">
                                                    <i class="fa fa-eye"></i> {{ __('View') }}
                                                </button>
                                                @if($item['message']->status === 'failed' || $item['message']->status === 'manual_review')
                                                    <a href="{{ route('admin.pattern-templates.create') }}" 
                                                        class="btn btn-xs btn-outline-primary" 
                                                        title="{{ __('Create pattern template') }}">
                                                        <i class="fa fa-plus"></i> {{ __('Create Pattern') }}
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endif
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <i class="fa fa-inbox fa-2x text-muted mb-2"></i>
                                            <p class="mb-0 text-muted">{{ __('No channel messages or parsed signals found.') }}</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if ($items->hasPages())
                    <div class="card-footer">
                        {{ $items->withQueryString()->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Modal for viewing raw message --}}
    <div class="modal fade" id="rawMessageModal" tabindex="-1" aria-labelledby="rawMessageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="rawMessageModalLabel">{{ __('Raw Message') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <pre id="rawMessageContent" class="bg-light p-3 rounded" style="white-space: pre-wrap; word-wrap: break-word;"></pre>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showRawMessage(messageId, rawMessage) {
            document.getElementById('rawMessageContent').textContent = rawMessage;
            const modal = new bootstrap.Modal(document.getElementById('rawMessageModal'));
            modal.show();
        }
    </script>
@endsection

