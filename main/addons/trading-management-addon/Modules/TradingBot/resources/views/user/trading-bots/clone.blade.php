@extends(Config::theme() . 'layout.auth')

@section('title')
    {{ $title }}
@endsection

@section('content')
<div class="sp_site_card">
    <div class="card-header">
        <h4>{{ __($title) }}</h4>
    </div>
    <div class="card-body">
        @if(isset($error))
            <div class="alert alert-danger">
                <i class="fa fa-exclamation-triangle"></i> {{ $error }}
            </div>
            <a href="{{ route('user.trading-bots.marketplace') }}" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> Back to Marketplace
            </a>
            @if($connections->isEmpty())
                <a href="{{ route('user.execution-connections.create') }}" class="btn btn-primary">
                    <i class="fa fa-plus"></i> Create Exchange Connection
                </a>
            @endif
        @else
            {{-- Template Preview --}}
            <div class="card bg-light mb-4">
                <div class="card-body">
                    <h5 class="card-title">Template Preview</h5>
                    <p class="text-muted">{{ $template->description }}</p>
                    <div class="row">
                        <div class="col-md-4">
                            <small class="text-muted">Risk Preset:</small>
                            <strong>{{ $template->tradingPreset->name ?? 'N/A' }}</strong>
                        </div>
                        @if($template->filterStrategy)
                            <div class="col-md-4">
                                <small class="text-muted">Technical Filter:</small>
                                <strong>{{ $template->filterStrategy->name }}</strong>
                            </div>
                        @endif
                        <div class="col-md-4">
                            <small class="text-muted">Market Type:</small>
                            <strong>{{ ucfirst($template->suggested_connection_type ?? 'Both') }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Clone Form --}}
            <form method="POST" action="{{ route('user.trading-bots.clone.store', $template->id) }}">
                @csrf

                <div class="mb-3">
                    <label for="exchange_connection_id" class="form-label">
                        Exchange Connection <span class="text-danger">*</span>
                    </label>
                    <select name="exchange_connection_id" id="exchange_connection_id" class="form-control @error('exchange_connection_id') is-invalid @enderror" required>
                        <option value="">-- Select Exchange Connection --</option>
                        @foreach($connections as $connection)
                            <option value="{{ $connection->id }}" {{ old('exchange_connection_id') == $connection->id ? 'selected' : '' }}>
                                {{ $connection->name }} 
                                ({{ $connection->connection_type === 'CRYPTO_EXCHANGE' ? 'Crypto' : ($connection->connection_type === 'FX_BROKER' ? 'Forex' : 'Unknown') }})
                                @if(isset($connection->execution_settings['is_paper_trading']) && $connection->execution_settings['is_paper_trading'])
                                    - Demo
                                @endif
                            </option>
                        @endforeach
                    </select>
                    @error('exchange_connection_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text text-muted">
                        Select the exchange connection you want to use for this bot.
                        @if($template->suggested_connection_type && $template->suggested_connection_type !== 'both')
                            This template is optimized for <strong>{{ ucfirst($template->suggested_connection_type) }}</strong> markets.
                        @endif
                    </small>
                </div>

                <div class="mb-3">
                    <label for="name" class="form-label">Bot Name</label>
                    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" 
                           value="{{ old('name', $template->name . ' (Copy)') }}" placeholder="Enter bot name">
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text text-muted">Leave empty to use default: "{{ $template->name }} (Copy)"</small>
                </div>

                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input type="checkbox" name="is_paper_trading" id="is_paper_trading" 
                               class="form-check-input" value="1" 
                               {{ old('is_paper_trading', true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_paper_trading">
                            <strong>Paper Trading (Demo Mode)</strong>
                        </label>
                    </div>
                    <small class="form-text text-muted">
                        Enable paper trading to test the bot without risking real money. Recommended for first-time setup.
                    </small>
                </div>

                <div class="alert alert-warning">
                    <i class="fa fa-info-circle"></i> 
                    <strong>Note:</strong> After cloning, you can review and customize the bot settings before activating it.
                </div>

                <div class="d-flex justify-content-between">
                    <a href="{{ route('user.trading-bots.marketplace') }}" class="btn btn-secondary">
                        <i class="fa fa-arrow-left"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-copy"></i> Clone Bot
                    </button>
                </div>
            </form>
        @endif
    </div>
</div>
@endsection
