@extends('trading-management::backend.trading-management.layout')

@section('submenu-content')
<div class="card">
    <div class="card-header">
        <h4>🎯 Trading Strategy</h4>
    </div>
    <div class="card-body">
        <!-- Tab Navigation -->
        <ul class="nav nav-tabs mb-4" role="tablist">
            <li class="nav-item">
                <a class="nav-link {{ !request()->has('tab') || request()->get('tab') === 'filters' ? 'active' : '' }}" 
                   href="{{ route('admin.trading-management.strategy.index') }}?tab=filters">
                    <i class="fas fa-filter"></i> Filter Strategies
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->get('tab') === 'ai-models' ? 'active' : '' }}" 
                   href="{{ route('admin.trading-management.strategy.index') }}?tab=ai-models">
                    <i class="fas fa-brain"></i> AI Model Profiles
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->get('tab') === 'logs' ? 'active' : '' }}" 
                   href="{{ route('admin.trading-management.strategy.index') }}?tab=logs">
                    <i class="fas fa-list"></i> Decision Logs
                </a>
            </li>
        </ul>

        <!-- Tab Content -->
        @php $currentTab = request()->get('tab', 'filters'); @endphp

        @if($currentTab === 'filters')
            <div class="alert alert-info">
                <h5><i class="fas fa-filter"></i> Filter Strategies</h5>
                <p>Technical indicator-based filtering (EMA, Stochastic, PSAR).</p>
                <p class="mb-0"><strong>Phase 3 Complete</strong>: FilterStrategy model operational.</p>
            </div>
        @elseif($currentTab === 'ai-models')
            <div class="alert alert-primary">
                <h5><i class="fas fa-brain"></i> AI Model Profiles</h5>
                <p>AI-powered market confirmation using OpenAI, Gemini.</p>
                <p class="mb-0"><strong>Phase 3 Complete</strong>: AiModelProfile model operational.</p>
            </div>
        @elseif($currentTab === 'logs')
            <div class="alert alert-secondary">
                <h5><i class="fas fa-list"></i> Decision Logs</h5>
                <p>AI and filter decision history for debugging.</p>
                <p class="mb-0"><strong>Phase 7+</strong>: To be implemented.</p>
            </div>
        @endif
    </div>
</div>
@endsection

