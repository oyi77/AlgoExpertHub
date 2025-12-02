@extends('backend.layout.master')

@section('element')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header site-card-header justify-content-between flex-wrap gap-2">
                    <div class="card-header-left">
                        <h4 class="card-title">{{ __('Pattern Templates') }}</h4>
                    </div>
                    <div class="card-header-right">
                        <a href="{{ route('admin.pattern-templates.create', ['channel_source_id' => $channelSourceId]) }}" class="btn btn-sm btn-primary">
                            <i class="fa fa-plus"></i> {{ __('Create Pattern') }}
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <form method="GET" action="{{ route('admin.pattern-templates.index') }}">
                                <select name="channel_source_id" class="form-control" onchange="this.form.submit()">
                                    <option value="">{{ __('All Channels') }}</option>
                                    @foreach ($channels as $channel)
                                        <option value="{{ $channel->id }}" {{ $channelSourceId == $channel->id ? 'selected' : '' }}>
                                            {{ $channel->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </form>
                        </div>
                    </div>

                    @if(isset($defaultTemplates) && count($defaultTemplates) > 0)
                    <div class="alert alert-info mb-3">
                        <h5 class="mb-2"><i class="fa fa-lightbulb-o"></i> {{ __('Default Templates Available') }}</h5>
                        <p class="mb-2">{{ __('Quickly create patterns from these pre-configured templates:') }}</p>
                        <div class="row">
                            @foreach($defaultTemplates as $template)
                            <div class="col-md-3 mb-2">
                                <div class="card border-primary">
                                    <div class="card-body p-2">
                                        <h6 class="card-title mb-1">{{ $template['name'] }}</h6>
                                        <small class="text-muted d-block mb-2">{{ $template['description'] ?? '' }}</small>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="badge bg-primary">{{ ucfirst($template['pattern_type']) }}</span>
                                            <span class="badge bg-secondary">Priority: {{ $template['priority'] ?? 0 }}</span>
                                        </div>
                                        <form action="{{ route('admin.pattern-templates.store') }}" method="POST" class="mt-2" onsubmit="return confirm('{{ __('Create pattern from this template?') }}');">
                                            @csrf
                                            <input type="hidden" name="name" value="{{ $template['name'] }}">
                                            <input type="hidden" name="description" value="{{ $template['description'] ?? '' }}">
                                            <input type="hidden" name="pattern_type" value="{{ $template['pattern_type'] }}">
                                            <input type="hidden" name="pattern_config" value="{{ json_encode($template['pattern_config']) }}">
                                            <input type="hidden" name="priority" value="{{ $template['priority'] ?? 0 }}">
                                            <input type="hidden" name="is_active" value="1">
                                            @if($channelSourceId)
                                                <input type="hidden" name="channel_source_id" value="{{ $channelSourceId }}">
                                            @endif
                                            <button type="submit" class="btn btn-sm btn-primary w-100">
                                                <i class="fa fa-plus"></i> {{ __('Create') }}
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('ID') }}</th>
                                    <th>{{ __('Name') }}</th>
                                    <th>{{ __('Channel') }}</th>
                                    <th>{{ __('Type') }}</th>
                                    <th>{{ __('Priority') }}</th>
                                    <th>{{ __('Success Rate') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th class="text-end">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($patterns as $pattern)
                                    <tr>
                                        <td>{{ $pattern->id }}</td>
                                        <td>
                                            <strong>{{ $pattern->name }}</strong>
                                            @if ($pattern->description)
                                                <br><small class="text-muted">{{ \Illuminate\Support\Str::limit($pattern->description, 50) }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($pattern->channelSource)
                                                <span class="badge bg-info">{{ $pattern->channelSource->name }}</span>
                                            @else
                                                <span class="badge bg-secondary">{{ __('Global') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-primary">{{ ucfirst($pattern->pattern_type) }}</span>
                                        </td>
                                        <td>{{ $pattern->priority }}</td>
                                        <td>
                                            @php
                                                $successRate = $pattern->getSuccessRate();
                                                $color = $successRate >= 70 ? 'success' : ($successRate >= 50 ? 'warning' : 'danger');
                                            @endphp
                                            <span class="badge bg-{{ $color }}">
                                                {{ number_format($successRate, 1) }}%
                                            </span>
                                            <br>
                                            <small class="text-muted">
                                                {{ $pattern->success_count }}/{{ $pattern->success_count + $pattern->failure_count }}
                                            </small>
                                        </td>
                                        <td>
                                            @if ($pattern->is_active)
                                                <span class="badge bg-success">{{ __('Active') }}</span>
                                            @else
                                                <span class="badge bg-secondary">{{ __('Inactive') }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('admin.pattern-templates.edit', $pattern->id) }}"
                                                class="btn btn-xs btn-outline-secondary" title="{{ __('Edit') }}">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            <form action="{{ route('admin.pattern-templates.destroy', $pattern->id) }}" method="POST" class="d-inline"
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
                                        <td colspan="8" class="text-center py-4">
                                            <i class="fa fa-inbox fa-2x text-muted mb-2"></i>
                                            <p class="mb-0 text-muted">{{ __('No pattern templates found.') }}</p>
                                            <a href="{{ route('admin.pattern-templates.create', ['channel_source_id' => $channelSourceId]) }}" class="btn btn-sm btn-primary mt-2">
                                                {{ __('Create First Pattern') }}
                                            </a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if ($patterns->hasPages())
                    <div class="card-footer">
                        {{ $patterns->withQueryString()->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

