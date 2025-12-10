@extends('backend.layout.master')

@section('element')
<div class="row">
    <div class="col-12">
        {{-- Main Form Card --}}
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">
                    <i class="fas fa-robot"></i> {{ $title }}
                </h4>
            </div>
            <div class="card-body">
                {{-- Back Button --}}
                <div class="mb-3">
                    <a href="{{ route('admin.trading-management.trading-bots.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fa fa-arrow-left"></i> Back to List
                    </a>
                </div>

                <form action="{{ route('admin.trading-management.trading-bots.store') }}" method="POST" id="trading-bot-form">
                    @csrf

                    {{-- Progress Indicator --}}
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <small class="text-muted">Form Progress</small>
                            <small class="text-muted" id="progress-text">0% Complete</small>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                 role="progressbar" 
                                 style="width: 0%" 
                                 id="form-progress"
                                 aria-valuenow="0" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100"></div>
                        </div>
                    </div>

                    {{-- Tabs Navigation --}}
                    <ul class="nav nav-tabs page-link-list mb-4" role="tablist">
                        <li>
                            <a class="active" data-toggle="tab" href="#basic-info" role="tab">
                                <i class="fa fa-info-circle"></i> Basic Info
                            </a>
                        </li>
                        <li>
                            <a data-toggle="tab" href="#connection" role="tab">
                                <i class="fa fa-exchange-alt"></i> Connection
                            </a>
                        </li>
                        <li>
                            <a data-toggle="tab" href="#strategy" role="tab">
                                <i class="fa fa-chart-line"></i> Strategy
                            </a>
                        </li>
                        <li>
                            <a data-toggle="tab" href="#settings" role="tab">
                                <i class="fa fa-cog"></i> Settings
                            </a>
                        </li>
                    </ul>

                    {{-- Tab Content --}}
                    <div class="tab-content tabcontent-border">
                        {{-- Tab 1: Basic Information --}}
                        <div class="tab-pane fade show active" id="basic-info" role="tabpanel">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group mb-3">
                                        <label for="name">
                                            <i class="fa fa-tag text-primary"></i> Bot Name 
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" 
                                               class="form-control @error('name') is-invalid @enderror" 
                                               id="name" 
                                               name="name" 
                                               value="{{ old('name', isset($bot) && $bot ? $bot->name : '') }}" 
                                               placeholder="e.g., My Scalping Bot"
                                               required>
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="form-text text-muted">
                                            Choose a descriptive name for your trading bot
                                        </small>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group mb-3">
                                        <label for="description">
                                            <i class="fa fa-align-left text-primary"></i> Description
                                        </label>
                                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                                  id="description" 
                                                  name="description" 
                                                  rows="4"
                                                  placeholder="Describe your bot's trading strategy, goals, or any special notes...">{{ old('description', $bot->description ?? '') }}</textarea>
                                        @error('description')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="form-text text-muted">
                                            Optional: Add notes about this bot's purpose or strategy
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Tab 2: Connection & Risk --}}
                        <div class="tab-pane fade" id="connection" role="tabpanel">
                            <div class="row">
                                {{-- Exchange Connection --}}
                                <div class="col-md-6 mb-4">
                                    <div class="border rounded p-3 h-100">
                                        <h6 class="mb-3">
                                            <i class="fa fa-exchange-alt text-primary"></i> Exchange Connection
                                        </h6>
                                        <div class="form-group mb-3">
                                            <label for="exchange_connection_id">
                                                Select Exchange/Broker <span class="text-danger">*</span>
                                            </label>
                                            @if($connections->isEmpty())
                                                <div class="alert alert-warning mb-2">
                                                    <i class="fa fa-exclamation-triangle"></i> No exchange connections available.
                                                </div>
                                                @if(Route::has('admin.trading-management.config.exchange-connections.create'))
                                                    <a href="{{ route('admin.trading-management.config.exchange-connections.create') }}" 
                                                       class="btn btn-primary btn-sm" 
                                                       target="_blank">
                                                        <i class="fa fa-plus"></i> Create Exchange Connection
                                                    </a>
                                                @elseif(Route::has('admin.trading-management.exchange-connections.create'))
                                                    <a href="{{ route('admin.trading-management.exchange-connections.create') }}" 
                                                       class="btn btn-primary btn-sm" 
                                                       target="_blank">
                                                        <i class="fa fa-plus"></i> Create Exchange Connection
                                                    </a>
                                                @endif
                                                <input type="hidden" name="exchange_connection_id" value="">
                                            @else
                                                <select class="form-control @error('exchange_connection_id') is-invalid @enderror" 
                                                        id="exchange_connection_id" 
                                                        name="exchange_connection_id" 
                                                        required>
                                                    <option value="">-- Select Exchange --</option>
                                                    @foreach($connections as $connection)
                                                        <option value="{{ $connection->id }}" 
                                                                {{ old('exchange_connection_id', isset($bot) && $bot ? $bot->exchange_connection_id : '') == $connection->id ? 'selected' : '' }}>
                                                            {{ $connection->name }} ({{ $connection->exchange_name }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('exchange_connection_id')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <small class="form-text text-muted mt-2">
                                                    @if(Route::has('admin.trading-management.config.exchange-connections.create'))
                                                        <a href="{{ route('admin.trading-management.config.exchange-connections.create') }}" 
                                                           target="_blank" 
                                                           class="text-primary">
                                                            <i class="fa fa-plus"></i> Add New Connection
                                                        </a>
                                                    @elseif(Route::has('admin.trading-management.exchange-connections.create'))
                                                        <a href="{{ route('admin.trading-management.exchange-connections.create') }}" 
                                                           target="_blank" 
                                                           class="text-primary">
                                                            <i class="fa fa-plus"></i> Add New Connection
                                                        </a>
                                                    @endif
                                                </small>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                {{-- Risk Management Preset --}}
                                <div class="col-md-6 mb-4">
                                    <div class="border rounded p-3 h-100">
                                        <h6 class="mb-3">
                                            <i class="fa fa-shield-alt text-success"></i> Risk Management
                                        </h6>
                                        <div class="form-group mb-3">
                                            <label for="trading_preset_id" class="form-label">
                                                Trading Preset <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-control @error('trading_preset_id') is-invalid @enderror" 
                                                    id="trading_preset_id" 
                                                    name="trading_preset_id" 
                                                    required>
                                                <option value="">-- Select Preset --</option>
                                                @foreach($presets as $preset)
                                                    <option value="{{ $preset->id }}" 
                                                            {{ old('trading_preset_id', $bot->trading_preset_id ?? '') == $preset->id ? 'selected' : '' }}>
                                                        {{ $preset->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('trading_preset_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="form-text text-muted mt-2">
                                                Defines position sizing, stop loss, and take profit rules
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Tab 3: Strategy Configuration --}}
                        <div class="tab-pane fade" id="strategy" role="tabpanel">
                            <div class="row">
                                {{-- Trading Mode --}}
                                <div class="col-md-12 mb-4">
                                    <div class="card border-primary">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0">
                                                <i class="fa fa-cogs text-primary"></i> Trading Mode
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="form-group mb-3">
                                                <label for="trading_mode">
                                                    Select Trading Mode <span class="text-danger">*</span>
                                                </label>
                                                <select class="form-control @error('trading_mode') is-invalid @enderror" 
                                                        id="trading_mode" 
                                                        name="trading_mode" 
                                                        required
                                                        onchange="toggleMarketStreamFields()">
                                                    <option value="SIGNAL_BASED" {{ old('trading_mode', isset($bot) && $bot ? $bot->trading_mode : 'SIGNAL_BASED') == 'SIGNAL_BASED' ? 'selected' : '' }}>
                                                        📡 Signal-Based (Execute on published signals)
                                                    </option>
                                                    <option value="MARKET_STREAM_BASED" {{ old('trading_mode', isset($bot) && $bot ? $bot->trading_mode : '') == 'MARKET_STREAM_BASED' ? 'selected' : '' }}>
                                                        📊 Market Stream-Based (Continuous market analysis)
                                                    </option>
                                                </select>
                                                @error('trading_mode')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <div class="alert alert-info mt-3 mb-0">
                                                    <strong>Signal-Based:</strong> Bot executes trades only when signals are published.<br>
                                                    <strong>Market Stream-Based:</strong> Bot continuously streams OHLCV data, applies technical indicators, and makes trading decisions based on market conditions.
                                                </div>
                                            </div>

                                            {{-- Market Stream Configuration (Conditional) --}}
                                            <div id="market-stream-config" style="display: {{ old('trading_mode', isset($bot) && $bot ? $bot->trading_mode : 'SIGNAL_BASED') == 'MARKET_STREAM_BASED' ? 'block' : 'none' }};">
                                                <hr>
                                                <h6 class="mb-3">Market Stream Configuration</h6>
                                                
                                                {{-- Data Connection --}}
                                                <div class="form-group mb-3">
                                                    <label for="data_connection_id" class="form-label">
                                                        Data Connection <span class="text-danger">*</span>
                                                    </label>
                                                    @php
                                                        $dataConnections = isset($dataConnections) ? $dataConnections : collect();
                                                    @endphp
                                                    @if($dataConnections->isEmpty())
                                                        <div class="alert alert-warning">
                                                            <i class="fa fa-exclamation-triangle"></i> 
                                                            <strong>No active exchange connections available.</strong>
                                                            <p class="mb-0 mt-2">
                                                                Data connections are unified with exchange connections. 
                                                                Please create at least one active exchange connection first.
                                                            </p>
                                                            @if(Route::has('admin.trading-management.config.exchange-connections.create'))
                                                                <a href="{{ route('admin.trading-management.config.exchange-connections.create') }}" 
                                                                   class="btn btn-primary btn-sm mt-2" 
                                                                   target="_blank">
                                                                    <i class="fa fa-plus"></i> Create Exchange Connection
                                                                </a>
                                                            @elseif(Route::has('admin.trading-management.exchange-connections.create'))
                                                                <a href="{{ route('admin.trading-management.exchange-connections.create') }}" 
                                                                   class="btn btn-primary btn-sm mt-2" 
                                                                   target="_blank">
                                                                    <i class="fa fa-plus"></i> Create Exchange Connection
                                                                </a>
                                                            @endif
                                                        </div>
                                                        <input type="hidden" name="data_connection_id" value="">
                                                    @else
                                                        <select class="form-control @error('data_connection_id') is-invalid @enderror" 
                                                                id="data_connection_id" 
                                                                name="data_connection_id">
                                                            <option value="">-- Select Data Connection --</option>
                                                            @foreach($dataConnections as $connection)
                                                                <option value="{{ $connection->id }}" 
                                                                        data-connection-type="{{ $connection->connection_type ?? $connection->type }}"
                                                                        {{ old('data_connection_id', isset($bot) && $bot ? $bot->data_connection_id : '') == $connection->id ? 'selected' : '' }}>
                                                                    {{ $connection->name }} 
                                                                    @if($connection->exchange_name)
                                                                        ({{ $connection->exchange_name }})
                                                                    @elseif($connection->provider)
                                                                        ({{ $connection->provider }})
                                                                    @endif
                                                                    @if($connection->id == old('exchange_connection_id', ''))
                                                                        - Same as Execution Connection
                                                                    @endif
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        @error('data_connection_id')
                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                        @enderror
                                                        <small class="form-text text-muted mt-2">
                                                            <i class="fa fa-info-circle"></i> 
                                                            Unified connections: You can use the same connection for both execution and data, 
                                                            or select a different one of the same type (crypto/fx).
                                                        </small>
                                                    @endif
                                                </div>

                                                {{-- Streaming Symbols & Timeframes --}}
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group mb-3">
                                                            <label for="streaming_symbols">
                                                                Trading Symbols <span class="text-danger">*</span>
                                                            </label>
                                                            
                                                            {{-- Multi-select dropdown (shown when symbols are available) --}}
                                                            <select class="form-control @error('streaming_symbols') is-invalid @enderror" 
                                                                    id="streaming_symbols" 
                                                                    name="streaming_symbols[]" 
                                                                    multiple
                                                                    size="8"
                                                                    style="min-height: 200px;">
                                                                <option value="">Select exchange connection first...</option>
                                                            </select>
                                                            
                                                            {{-- Manual entry textarea (shown when no symbols are available) --}}
                                                            <textarea class="form-control @error('streaming_symbols_manual') is-invalid @enderror" 
                                                                      id="streaming_symbols_manual" 
                                                                      name="streaming_symbols_manual" 
                                                                      rows="4"
                                                                      placeholder="Enter symbols manually (one per line or comma-separated):&#10;EURUSD&#10;GBPUSD&#10;XAUUSD"
                                                                      style="display: none; margin-top: 10px;"></textarea>
                                                            
                                                            <small class="form-text text-muted">
                                                                <span id="symbols-loading" style="display: none;"><i class="fa fa-spinner fa-spin"></i> Loading symbols...</span>
                                                                <span id="symbols-count" style="display: none;"></span>
                                                                <span id="symbols-manual-hint" style="display: none;" class="text-warning">
                                                                    <i class="fa fa-exclamation-triangle"></i> No symbols loaded. Please enter symbols manually above.
                                                                </span>
                                                                <span id="symbols-auto-hint">Select trading pairs to monitor. Symbols are loaded from the selected exchange connection.</span>
                                                            </small>
                                                            @error('streaming_symbols')
                                                                <div class="invalid-feedback">{{ $message }}</div>
                                                            @enderror
                                                            @error('streaming_symbols_manual')
                                                                <div class="invalid-feedback">{{ $message }}</div>
                                                            @enderror
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group mb-3">
                                                            <label for="streaming_timeframes">
                                                                Timeframes <span class="text-danger">*</span>
                                                            </label>
                                                            <select class="form-control @error('streaming_timeframes') is-invalid @enderror" 
                                                                    id="streaming_timeframes" 
                                                                    name="streaming_timeframes[]" 
                                                                    multiple
                                                                    size="5">
                                                                @php
                                                                    $timeframes = ['1m', '5m', '15m', '30m', '1h', '4h', '1d', '1w'];
                                                                    $selectedTimeframes = old('streaming_timeframes', isset($bot) && $bot && $bot->streaming_timeframes ? $bot->streaming_timeframes : []);
                                                                @endphp
                                                                @foreach($timeframes as $tf)
                                                                    <option value="{{ $tf }}" {{ in_array($tf, $selectedTimeframes) ? 'selected' : '' }}>{{ $tf }}</option>
                                                                @endforeach
                                                            </select>
                                                            @error('streaming_timeframes')
                                                                <div class="invalid-feedback">{{ $message }}</div>
                                                            @enderror
                                                            <small class="form-text text-muted">
                                                                Hold Ctrl/Cmd to select multiple
                                                            </small>
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- Intervals --}}
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group mb-3">
                                                            <label for="market_analysis_interval" class="form-label">
                                                                Market Analysis Interval (seconds)
                                                            </label>
                                                            <input type="number" 
                                                                   class="form-control @error('market_analysis_interval') is-invalid @enderror" 
                                                                   id="market_analysis_interval" 
                                                                   name="market_analysis_interval" 
                                                                   value="{{ old('market_analysis_interval', isset($bot) && $bot ? $bot->market_analysis_interval : 60) }}"
                                                                   min="10"
                                                                   step="1">
                                                            @error('market_analysis_interval')
                                                                <div class="invalid-feedback">{{ $message }}</div>
                                                            @enderror
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group mb-3">
                                                        <label for="position_monitoring_interval">
                                                            Position Monitoring Interval (seconds)
                                                        </label>
                                                            <input type="number" 
                                                                   class="form-control @error('position_monitoring_interval') is-invalid @enderror" 
                                                                   id="position_monitoring_interval" 
                                                                   name="position_monitoring_interval" 
                                                                   value="{{ old('position_monitoring_interval', isset($bot) && $bot ? $bot->position_monitoring_interval : 5) }}"
                                                                   min="1"
                                                                   step="1">
                                                            @error('position_monitoring_interval')
                                                                <div class="invalid-feedback">{{ $message }}</div>
                                                            @enderror
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Strategy Filters --}}
                                <div class="col-md-6 mb-4">
                                    <div class="card border-info">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0">
                                                <i class="fa fa-chart-line text-info"></i> Technical Filter (Optional)
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="form-group mb-3">
                                                <label for="filter_strategy_id">
                                                    Filter Strategy
                                                </label>
                                                <select class="form-control @error('filter_strategy_id') is-invalid @enderror" 
                                                        id="filter_strategy_id" 
                                                        name="filter_strategy_id">
                                                    <option value="">-- No Filter --</option>
                                                    @foreach($filterStrategies as $strategy)
                                                        <option value="{{ $strategy->id }}" 
                                                                {{ old('filter_strategy_id', $bot->filter_strategy_id ?? '') == $strategy->id ? 'selected' : '' }}>
                                                            {{ $strategy->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('filter_strategy_id')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <small class="form-text text-muted mt-2">
                                                    Apply technical indicator filters before executing trades
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- AI Confirmation --}}
                                <div class="col-md-6 mb-4">
                                    <div class="card border-warning">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0">
                                                <i class="fa fa-brain text-warning"></i> AI Confirmation (Optional)
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="form-group mb-3">
                                                <label for="ai_model_profile_id" class="form-label">
                                                    AI Model Profile
                                                </label>
                                                @if($aiProfiles->isEmpty())
                                                    <div class="alert alert-info mb-2">
                                                        <i class="fa fa-info-circle"></i> No AI model profiles available.
                                                    </div>
                                                    @if(Route::has('admin.ai-model-profiles.create'))
                                                        <a href="{{ route('admin.ai-model-profiles.create') }}" 
                                                           class="btn btn-primary btn-sm" 
                                                           target="_blank">
                                                            <i class="fa fa-plus"></i> Create AI Profile
                                                        </a>
                                                    @elseif(Route::has('admin.trading-management.strategy.ai-models.create'))
                                                        <a href="{{ route('admin.trading-management.strategy.ai-models.create') }}" 
                                                           class="btn btn-primary btn-sm" 
                                                           target="_blank">
                                                            <i class="fa fa-plus"></i> Create AI Profile
                                                        </a>
                                                    @endif
                                                    <input type="hidden" name="ai_model_profile_id" value="">
                                                @else
                                                    <select class="form-control @error('ai_model_profile_id') is-invalid @enderror" 
                                                            id="ai_model_profile_id" 
                                                            name="ai_model_profile_id">
                                                        <option value="">-- No AI Confirmation --</option>
                                                        @foreach($aiProfiles as $profile)
                                                            <option value="{{ $profile->id }}" 
                                                                    {{ old('ai_model_profile_id', isset($bot) && $bot ? $bot->ai_model_profile_id : '') == $profile->id ? 'selected' : '' }}>
                                                                {{ $profile->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    @error('ai_model_profile_id')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                    <small class="form-text text-muted mt-2">
                                                        Use AI to confirm market conditions before trading
                                                    </small>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Expert Advisor (Optional) --}}
                                @if(isset($expertAdvisors) && $expertAdvisors->isNotEmpty())
                                <div class="col-md-12 mb-4">
                                    <div class="card border-secondary">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0">
                                                <i class="fa fa-code text-secondary"></i> Expert Advisor (Optional)
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="form-group mb-3">
                                                <label for="expert_advisor_id">
                                                    MT4/MT5 Expert Advisor
                                                </label>
                                                <select class="form-control @error('expert_advisor_id') is-invalid @enderror" 
                                                        id="expert_advisor_id" 
                                                        name="expert_advisor_id">
                                                    <option value="">-- No Expert Advisor --</option>
                                                    @foreach($expertAdvisors as $ea)
                                                        <option value="{{ $ea->id }}" 
                                                                {{ old('expert_advisor_id', isset($bot) && $bot ? $bot->expert_advisor_id : '') == $ea->id ? 'selected' : '' }}>
                                                            {{ $ea->name }} ({{ strtoupper($ea->ea_type) }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('expert_advisor_id')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <small class="form-text text-muted mt-2">
                                                    Attach an MT4/MT5 Expert Advisor file for advanced trading logic
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>

                        {{-- Tab 4: Settings --}}
                        <div class="tab-pane fade" id="settings" role="tabpanel">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="card border-success">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0">
                                                <i class="fa fa-toggle-on text-success"></i> Bot Status & Mode
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" 
                                                       type="checkbox" 
                                                       id="is_active" 
                                                       name="is_active" 
                                                       value="1" 
                                                       {{ old('is_active', isset($bot) && $bot ? $bot->is_active : true) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="is_active">
                                                    <strong>Active</strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        Bot will execute trades when signals are published or market conditions are met
                                                    </small>
                                                </label>
                                            </div>

                                            <div class="form-check">
                                                <input class="form-check-input" 
                                                       type="checkbox" 
                                                       id="is_paper_trading" 
                                                       name="is_paper_trading" 
                                                       value="1" 
                                                       {{ old('is_paper_trading', isset($bot) && $bot ? $bot->is_paper_trading : true) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="is_paper_trading">
                                                    <strong>Paper Trading Mode (Demo)</strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        Simulate trades without using real money. Perfect for testing strategies.
                                                    </small>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Form Actions --}}
                    <div class="d-flex justify-content-between align-items-center mt-4 pt-4 border-top">
                        <a href="{{ route('admin.trading-management.trading-bots.index') }}" class="btn btn-secondary">
                            <i class="fa fa-times"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary btn-lg" id="submit-btn">
                            <i class="fa fa-save"></i> Create Trading Bot
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form progress tracking
    const form = document.getElementById('trading-bot-form');
    const progressBar = document.getElementById('form-progress');
    const progressText = document.getElementById('progress-text');
    const submitBtn = document.getElementById('submit-btn');
    
    // Required fields
    const requiredFields = ['name', 'exchange_connection_id', 'trading_preset_id', 'trading_mode'];
    
    function updateProgress() {
        let filled = 0;
        requiredFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field && field.value && field.value.trim() !== '') {
                filled++;
            }
        });
        
        const progress = Math.round((filled / requiredFields.length) * 100);
        progressBar.style.width = progress + '%';
        progressBar.setAttribute('aria-valuenow', progress);
        progressText.textContent = progress + '% Complete';
        
        // Enable/disable submit button
        if (progress === 100) {
            submitBtn.disabled = false;
            submitBtn.classList.remove('btn-secondary');
            submitBtn.classList.add('btn-primary');
        } else {
            submitBtn.disabled = false; // Keep enabled, but show progress
        }
    }
    
    // Track field changes
    requiredFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('change', updateProgress);
            field.addEventListener('input', updateProgress);
        }
    });
    
    // Initial progress
    updateProgress();
    
    // Form submission loading state
    form.addEventListener('submit', function() {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Creating...';
    });
    
    // Tab change tracking for progress (Bootstrap 4)
    $('a[data-toggle="tab"]').on('shown.bs.tab', function() {
        updateProgress();
    });
});

// Trading mode toggle
function toggleMarketStreamFields() {
    const tradingMode = document.getElementById('trading_mode').value;
    const marketStreamConfig = document.getElementById('market-stream-config');
    const dataConnectionField = document.getElementById('data_connection_id');
    
    if (tradingMode === 'MARKET_STREAM_BASED') {
        marketStreamConfig.style.display = 'block';
        if (dataConnectionField) {
            dataConnectionField.required = true;
        }
        // Filter data connections when exchange connection is selected
        filterDataConnections();
    } else {
        marketStreamConfig.style.display = 'none';
        if (dataConnectionField) {
            dataConnectionField.required = false;
        }
    }
}

// Filter data connections based on selected exchange connection type
function filterDataConnections() {
    const exchangeConnectionSelect = document.getElementById('exchange_connection_id');
    const dataConnectionSelect = document.getElementById('data_connection_id');
    
    if (!exchangeConnectionSelect || !dataConnectionSelect) return;
    
    const selectedExchangeId = exchangeConnectionSelect.value;
    if (!selectedExchangeId) {
        // Show all data connections if no exchange selected
        Array.from(dataConnectionSelect.options).forEach(option => {
            if (option.value === '') return; // Keep the placeholder
            option.style.display = '';
        });
        return;
    }
    
    // Get selected exchange connection's type from the option text or data attribute
    const selectedOption = exchangeConnectionSelect.options[exchangeConnectionSelect.selectedIndex];
    const exchangeText = selectedOption.text;
    
    // Find the connection object from the connections list
    // We'll need to get connection type from the server or store it in data attributes
    // For now, show all connections - they should be filtered by type on the server
    // The server already filters by data_fetching_enabled, so we just need to match types
    
    // Since connections are unified, we can show all data connections
    // The backend validation will ensure type matching
    Array.from(dataConnectionSelect.options).forEach(option => {
        if (option.value === '') return; // Keep the placeholder
        option.style.display = '';
    });
}

// Toggle manual entry visibility
function toggleManualEntry(showManual) {
    const streamingSymbolsSelect = document.getElementById('streaming_symbols');
    const streamingSymbolsManual = document.getElementById('streaming_symbols_manual');
    const symbolsManualHint = document.getElementById('symbols-manual-hint');
    const symbolsAutoHint = document.getElementById('symbols-auto-hint');
    
    if (!streamingSymbolsSelect || !streamingSymbolsManual) return;
    
    if (showManual) {
        // Show manual entry, hide select
        streamingSymbolsSelect.style.display = 'none';
        streamingSymbolsManual.style.display = 'block';
        if (symbolsManualHint) symbolsManualHint.style.display = 'inline';
        if (symbolsAutoHint) symbolsAutoHint.style.display = 'none';
        streamingSymbolsManual.required = true;
        streamingSymbolsSelect.required = false;
    } else {
        // Show select, hide manual entry
        streamingSymbolsSelect.style.display = 'block';
        streamingSymbolsManual.style.display = 'none';
        if (symbolsManualHint) symbolsManualHint.style.display = 'none';
        if (symbolsAutoHint) symbolsAutoHint.style.display = 'inline';
        streamingSymbolsManual.required = false;
        streamingSymbolsSelect.required = true;
    }
}

// Parse manual entry and populate select (for form submission)
function parseManualEntry() {
    const streamingSymbolsSelect = document.getElementById('streaming_symbols');
    const streamingSymbolsManual = document.getElementById('streaming_symbols_manual');
    
    if (!streamingSymbolsSelect || !streamingSymbolsManual) return;
    
    const manualText = streamingSymbolsManual.value.trim();
    if (!manualText) return;
    
    // Parse: split by newline or comma, trim each
    const symbols = manualText
        .split(/[\n,]+/)
        .map(s => s.trim())
        .filter(s => s.length > 0);
    
    // Clear select and add parsed symbols
    streamingSymbolsSelect.innerHTML = '';
    symbols.forEach(symbol => {
        const option = document.createElement('option');
        option.value = symbol;
        option.textContent = symbol;
        option.selected = true;
        streamingSymbolsSelect.appendChild(option);
    });
}

// Load symbols from exchange connection
function loadSymbols(connectionId) {
    const streamingSymbolsSelect = document.getElementById('streaming_symbols');
    const symbolsLoading = document.getElementById('symbols-loading');
    const symbolsCount = document.getElementById('symbols-count');
    
    if (!streamingSymbolsSelect || !connectionId) {
        toggleManualEntry(true);
        return;
    }

    // Show loading
    if (symbolsLoading) symbolsLoading.style.display = 'inline';
    if (symbolsCount) symbolsCount.style.display = 'none';
    streamingSymbolsSelect.disabled = true;
    streamingSymbolsSelect.innerHTML = '<option>Loading symbols...</option>';
    toggleManualEntry(false);

    // Fetch symbols via AJAX
    fetch(`{{ route('admin.trading-management.trading-bots.exchange-symbols') }}?connection_id=${connectionId}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (symbolsLoading) symbolsLoading.style.display = 'none';
        streamingSymbolsSelect.disabled = false;
        streamingSymbolsSelect.innerHTML = '';

        if (data.success && data.symbols && data.symbols.length > 0) {
            // Populate select with symbols
            data.symbols.forEach(symbol => {
                const option = document.createElement('option');
                option.value = symbol;
                option.textContent = symbol;
                streamingSymbolsSelect.appendChild(option);
            });

            // Show count
            if (symbolsCount) {
                symbolsCount.textContent = `(${data.count} symbols available)`;
                symbolsCount.style.display = 'inline';
            }
            toggleManualEntry(false);
        } else {
            // No symbols available - show manual entry
            streamingSymbolsSelect.innerHTML = '<option value="">No symbols available</option>';
            if (symbolsCount) symbolsCount.style.display = 'none';
            toggleManualEntry(true);
            
            if (data.message) {
                console.warn('Failed to load symbols:', data.message);
            }
        }
    })
    .catch(error => {
        if (symbolsLoading) symbolsLoading.style.display = 'none';
        streamingSymbolsSelect.disabled = false;
        streamingSymbolsSelect.innerHTML = '<option value="">Error loading symbols</option>';
        if (symbolsCount) symbolsCount.style.display = 'none';
        toggleManualEntry(true);
        console.error('Error loading symbols:', error);
    });
}

// Listen for exchange connection changes
document.addEventListener('DOMContentLoaded', function() {
    const exchangeConnectionSelect = document.getElementById('exchange_connection_id');
    const form = document.querySelector('form');
    
    // Parse manual entry before form submission
    if (form) {
        form.addEventListener('submit', function(e) {
            const streamingSymbolsManual = document.getElementById('streaming_symbols_manual');
            if (streamingSymbolsManual && streamingSymbolsManual.style.display !== 'none' && streamingSymbolsManual.value.trim()) {
                parseManualEntry();
            }
        });
    }
    
    if (exchangeConnectionSelect) {
        exchangeConnectionSelect.addEventListener('change', function() {
            const connectionId = this.value;
            const tradingMode = document.getElementById('trading_mode').value;
            
            // If market stream mode is selected, filter data connections and load symbols
            if (tradingMode === 'MARKET_STREAM_BASED') {
                filterDataConnections();
                if (connectionId) {
                    loadSymbols(connectionId);
                } else {
                    toggleManualEntry(true);
                }
            }
        });
    }
    
    // Also reload symbols when trading mode changes to MARKET_STREAM_BASED
    const tradingModeSelect = document.getElementById('trading_mode');
    if (tradingModeSelect) {
        tradingModeSelect.addEventListener('change', function() {
            if (this.value === 'MARKET_STREAM_BASED') {
                const connectionId = document.getElementById('exchange_connection_id')?.value;
                if (connectionId) {
                    loadSymbols(connectionId);
                }
            }
        });
    }
});
</script>
@endpush
@endsection
