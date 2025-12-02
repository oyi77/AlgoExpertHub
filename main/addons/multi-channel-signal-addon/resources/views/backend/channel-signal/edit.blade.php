@extends('backend.layout.master')

@section('element')
    <div class="row">
        <div class="col-xl-8">
            <div class="card mb-3">
                <div class="card-header site-card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ __('Edit Auto-Created Signal') }}</h5>
                    <a href="{{ route('admin.channel-signals.show', $signal->id) }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fa fa-eye"></i> {{ __('Preview') }}
                    </a>
                </div>

                <form action="{{ route('admin.channel-signals.update', $signal->id) }}" method="post">
                    @csrf
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="title" class="form-label">{{ __('Title') }}</label>
                            <input type="text" id="title" name="title" class="form-control"
                                value="{{ old('title', $signal->title) }}" required>
                            @error('title')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">{{ __('Description') }}</label>
                            <textarea name="description" id="description" rows="5" class="form-control">{{ old('description', $signal->description) }}</textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="market" class="form-label">{{ __('Market') }}</label>
                                <select name="market" id="market" class="form-control" required>
                                    @foreach ($markets as $market)
                                        <option value="{{ $market->id }}" @selected(old('market', $signal->market_id) == $market->id)>
                                            {{ $market->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('market')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="currency_pair" class="form-label">{{ __('Currency Pair') }}</label>
                                <select name="currency_pair" id="currency_pair" class="form-control" required>
                                    @foreach ($pairs as $pair)
                                        <option value="{{ $pair->id }}"
                                            @selected(old('currency_pair', $signal->currency_pair_id) == $pair->id)>
                                            {{ $pair->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('currency_pair')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="time_frame" class="form-label">{{ __('Time Frame') }}</label>
                                <select name="time_frame" id="time_frame" class="form-control" required>
                                    @foreach ($times as $time)
                                        <option value="{{ $time->id }}" @selected(old('time_frame', $signal->time_frame_id) == $time->id)>
                                            {{ $time->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('time_frame')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="open_price" class="form-label">{{ __('Entry Price') }}</label>
                                <input type="number" step="0.00001" name="open_price" id="open_price" class="form-control"
                                    value="{{ old('open_price', $signal->open_price) }}" required>
                                @error('open_price')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="sl" class="form-label">{{ __('Stop Loss') }}</label>
                                <input type="number" step="0.00001" name="sl" id="sl" class="form-control"
                                    value="{{ old('sl', $signal->sl) }}" required>
                                @error('sl')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="tp" class="form-label">{{ __('Take Profit') }}</label>
                                <input type="number" step="0.00001" name="tp" id="tp" class="form-control"
                                    value="{{ old('tp', $signal->tp) }}" required>
                                @error('tp')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="direction" class="form-label">{{ __('Direction') }}</label>
                                <select name="direction" id="direction" class="form-control" required>
                                    <option value="buy" @selected(old('direction', $signal->direction) === 'buy')>{{ __('Buy') }}</option>
                                    <option value="sell" @selected(old('direction', $signal->direction) === 'sell')>{{ __('Sell') }}</option>
                                </select>
                                @error('direction')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-8 mb-3">
                                <label class="form-label">{{ __('Plans') }}</label>
                                <select name="plans[]" class="form-control select2" multiple required>
                                    @foreach ($plans as $plan)
                                        <option value="{{ $plan->id }}"
                                            @selected(collect(old('plans', $signal->plans->pluck('id')->all()))->contains($plan->id))>
                                            {{ $plan->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('plans')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="card-footer d-flex justify-content-between">
                        <a href="{{ route('admin.channel-signals.index') }}" class="btn btn-outline-secondary">
                            <i class="fa fa-arrow-left"></i> {{ __('Cancel') }}
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i> {{ __('Save changes') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card">
                <div class="card-header site-card-header">
                    <h5 class="mb-0">{{ __('Channel Message Snapshot') }}</h5>
                </div>
                <div class="card-body">
                    @if ($channelMessage)
                        <div class="mb-3">
                            <h6 class="text-muted">{{ __('Raw message') }}</h6>
                            <pre class="bg-light p-3 rounded">{{ $channelMessage->message ?? __('N/A') }}</pre>
                        </div>
                        <dl class="row mb-0">
                            <dt class="col-sm-5">{{ __('Confidence') }}</dt>
                            <dd class="col-sm-7">{{ $channelMessage->confidence_score ? $channelMessage->confidence_score . '%' : __('N/A') }}</dd>

                            <dt class="col-sm-5">{{ __('Received at') }}</dt>
                            <dd class="col-sm-7">{{ $channelMessage->created_at?->format('d M Y H:i') }}</dd>
                        </dl>
                    @else
                        <p class="mb-0 text-muted">{{ __('No message context available for this signal.') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

