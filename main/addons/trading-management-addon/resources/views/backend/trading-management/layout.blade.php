@extends('backend.layout.master')

@section('element')
<div class="row">
    <div class="col-12">
        <!-- Trading Management Header -->
        <div class="card mb-3">
            <div class="card-body">
                <h3><i class="fas fa-chart-line"></i> Trading Management</h3>
                <p class="text-muted mb-0">Unified trading management system</p>
            </div>
        </div>

        <!-- Submenu Navigation -->
        <div class="card mb-3">
            <div class="card-body p-0">
                <ul class="nav nav-pills nav-fill">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('*/config*') ? 'active' : '' }}" 
                           href="{{ route('admin.trading-management.config.data-connections.index') }}">
                            <i class="fas fa-cog"></i><br>
                            <small>Trading Configuration</small>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('*/operations*') ? 'active' : '' }}" 
                           href="{{ route('admin.trading-management.operations.index') }}">
                            <i class="fas fa-bolt"></i><br>
                            <small>Trading Operations</small>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('*/strategy*') ? 'active' : '' }}" 
                           href="{{ route('admin.trading-management.strategy.index') }}">
                            <i class="fas fa-bullseye"></i><br>
                            <small>Trading Strategy</small>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('*/copy-trading*') ? 'active' : '' }}" 
                           href="{{ route('admin.trading-management.copy-trading.index') }}">
                            <i class="fas fa-users"></i><br>
                            <small>Copy Trading</small>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('*/test*') ? 'active' : '' }}" 
                           href="{{ route('admin.trading-management.test.index') }}">
                            <i class="fas fa-flask"></i><br>
                            <small>Trading Test</small>
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Content Area -->
        @yield('submenu-content')
    </div>
</div>
@endsection

