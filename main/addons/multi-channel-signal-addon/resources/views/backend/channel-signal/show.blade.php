@extends('backend.layout.master')

@section('element')
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header site-card-header">
                    <h5 class="mb-0">{{ __('Signal Preview') }}</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">{{ __('Title') }}</dt>
                        <dd class="col-sm-8">{{ $signal->title }}</dd>

                        <dt class="col-sm-4">{{ __('Channel Source') }}</dt>
                        <dd class="col-sm-8">
                            {{ optional($signal->channelSource)->name ?? __('Unknown source') }}
                            <div class="small text-muted">
                                {{ optional(optional($signal->channelSource)->user)->name ?? __('System linked') }}
                            </div>
                        </dd>

                        <dt class="col-sm-4">{{ __('Plans') }}</dt>
                        <dd class="col-sm-8">
                            @forelse ($signal->plans as $plan)
                                <span class="badge bg-info text-dark">{{ $plan->name }}</span>
                            @empty
                                <span class="text-muted">{{ __('No plan attached') }}</span>
                            @endforelse
                        </dd>

                        <dt class="col-sm-4">{{ __('Market / Pair / Timeframe') }}</dt>
                        <dd class="col-sm-8">
                            {{ optional($signal->market)->name ?? __('N/A') }} /
                            {{ optional($signal->pair)->name ?? __('N/A') }} /
                            {{ optional($signal->time)->name ?? __('N/A') }}
                        </dd>

                        <dt class="col-sm-4">{{ __('Direction') }}</dt>
                        <dd class="col-sm-8">
                            <span class="badge {{ $signal->direction === 'buy' ? 'bg-success' : 'bg-danger' }}">
                                {{ strtoupper($signal->direction) }}
                            </span>
                        </dd>

                        <dt class="col-sm-4">{{ __('Pricing') }}</dt>
                        <dd class="col-sm-8">
                            <div>{{ __('Entry') }}: <strong>{{ $signal->open_price }}</strong></div>
                            <div>{{ __('Stop Loss') }}: <strong>{{ $signal->sl }}</strong></div>
                            <div>{{ __('Take Profit') }}: <strong>{{ $signal->tp }}</strong></div>
                        </dd>

                        <dt class="col-sm-4">{{ __('Description') }}</dt>
                        <dd class="col-sm-8">
                            <pre class="bg-light p-3 rounded">{{ $signal->description }}</pre>
                        </dd>
                    </dl>
                </div>
                <div class="card-footer d-flex gap-2">
                    <a href="{{ route('admin.channel-signals.edit', $signal->id) }}"
                        class="btn btn-sm btn-outline-secondary">
                        <i class="fa fa-edit"></i> {{ __('Edit signal') }}
                    </a>
                    <a href="{{ route('admin.channel-signals.index') }}" class="btn btn-sm btn-outline-primary">
                        <i class="fa fa-arrow-left"></i> {{ __('Back to list') }}
                    </a>
                </div>
            </div>

            <div class="card">
                <div class="card-header site-card-header">
                    <h5 class="mb-0">{{ __('Channel Message') }}</h5>
                </div>
                <div class="card-body">
                    @if ($channelMessage)
                        <dl class="row mb-0">
                            <dt class="col-sm-4">{{ __('Original Message') }}</dt>
                            <dd class="col-sm-8">
                                <pre class="bg-light p-3 rounded">{{ $channelMessage->message ?? __('N/A') }}</pre>
                            </dd>

                            <dt class="col-sm-4">{{ __('Confidence Score') }}</dt>
                            <dd class="col-sm-8">
                                {{ $channelMessage->confidence_score ? $channelMessage->confidence_score . '%' : __('N/A') }}
                            </dd>

                            <dt class="col-sm-4">{{ __('Received At') }}</dt>
                            <dd class="col-sm-8">{{ $channelMessage->created_at?->format('d M Y H:i') }}</dd>
                        </dl>
                    @else
                        <p class="mb-0 text-muted">{{ __('No channel message linked to this signal.') }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header site-card-header">
                    <h5 class="mb-0">{{ __('Actions') }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.channel-signals.approve', $signal->id) }}" method="post" class="mb-3">
                        @csrf
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fa fa-check-circle"></i> {{ __('Approve & Publish') }}
                        </button>
                    </form>

                    <form action="{{ route('admin.channel-signals.reject', $signal->id) }}" method="post">
                        @csrf
                        <div class="mb-3">
                            <label for="reject-reason" class="form-label">{{ __('Reject reason (optional)') }}</label>
                            <textarea name="reason" id="reject-reason" rows="3" class="form-control"
                                placeholder="{{ __('Provide context for audit trail') }}"></textarea>
                        </div>
                        <button type="submit" class="btn btn-outline-danger w-100">
                            <i class="fa fa-times-circle"></i> {{ __('Reject & Delete') }}
                        </button>
                    </form>
                </div>
            </div>

            @if ($signal->is_published)
                <div class="alert alert-success mt-3 mb-0">
                    <i class="fa fa-info-circle"></i>
                    {{ __('This signal has already been published.') }}
                </div>
            @endif
        </div>
    </div>
@endsection

