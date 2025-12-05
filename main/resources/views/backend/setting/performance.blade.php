<div class="card">
    <div class="card-header">
        <h4 class="m-0">{{ __('Performance Settings') }}</h4>
        <p class="text-muted mb-0">{{ __('Optimize your application performance with these tools') }}</p>
    </div>
    <div class="card-body">
        <!-- PHP OPcache Status -->
        <div class="row mb-4">
            <div class="col-md-12">
                <h5 class="mb-3"><i class="las la-memory"></i> {{ __('PHP OPcache Status') }}</h5>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>{{ __('Setting') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Value') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>{{ __('OPcache Enabled') }}</td>
                                <td>
                                    @if(function_exists('opcache_get_status') && opcache_get_status() !== false)
                                        <span class="badge badge-success">{{ __('Enabled') }}</span>
                                    @else
                                        <span class="badge badge-danger">{{ __('Disabled') }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if(function_exists('opcache_get_status'))
                                        @php
                                            $opcache = opcache_get_status();
                                            $config = opcache_get_configuration();
                                        @endphp
                                        @if($opcache && isset($opcache['opcache_statistics']))
                                            {{ __('Active') }} - {{ number_format($opcache['opcache_statistics']['num_cached_scripts'] ?? 0) }} {{ __('scripts cached') }}
                                        @else
                                            {{ __('Not Available') }}
                                        @endif
                                    @else
                                        {{ __('OPcache extension not installed') }}
                                    @endif
                                </td>
                            </tr>
                            @if(function_exists('opcache_get_status') && opcache_get_status() !== false)
                                @php
                                    $opcache = opcache_get_status();
                                    $config = opcache_get_configuration();
                                @endphp
                                @if($opcache && isset($opcache['memory_usage']))
                                    <tr>
                                        <td>{{ __('Memory Usage') }}</td>
                                        <td>
                                            @php
                                                $used = $opcache['memory_usage']['used_memory'] ?? 0;
                                                $free = $opcache['memory_usage']['free_memory'] ?? 0;
                                                $total = $used + $free;
                                                $percent = $total > 0 ? ($used / $total) * 100 : 0;
                                            @endphp
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar {{ $percent > 80 ? 'bg-danger' : ($percent > 60 ? 'bg-warning' : 'bg-success') }}" 
                                                     role="progressbar" 
                                                     style="width: {{ $percent }}%">
                                                    {{ number_format($percent, 1) }}%
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            {{ number_format($used / 1024 / 1024, 2) }} MB / {{ number_format($total / 1024 / 1024, 2) }} MB
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>{{ __('Hit Rate') }}</td>
                                        <td>
                                            @php
                                                $hits = $opcache['opcache_statistics']['hits'] ?? 0;
                                                $misses = $opcache['opcache_statistics']['misses'] ?? 0;
                                                $total_reqs = $hits + $misses;
                                                $hit_rate = $total_reqs > 0 ? ($hits / $total_reqs) * 100 : 0;
                                            @endphp
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar {{ $hit_rate > 90 ? 'bg-success' : ($hit_rate > 70 ? 'bg-warning' : 'bg-danger') }}" 
                                                     role="progressbar" 
                                                     style="width: {{ $hit_rate }}%">
                                                    {{ number_format($hit_rate, 1) }}%
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            {{ number_format($hits) }} {{ __('hits') }} / {{ number_format($total_reqs) }} {{ __('requests') }}
                                        </td>
                                    </tr>
                                @endif
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Laravel Optimization Commands -->
        <div class="row mb-4">
            <div class="col-md-12">
                <h5 class="mb-3"><i class="las la-rocket"></i> {{ __('Laravel Optimization') }}</h5>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="card border">
                            <div class="card-body">
                                <h6 class="card-title">{{ __('Cache Configuration') }}</h6>
                                <p class="card-text text-muted small">
                                    {{ __('Compile and cache configuration files for faster loading') }}
                                </p>
                                <form action="{{ route('admin.general.performance.optimize') }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="action" value="config:cache">
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <i class="las la-sync"></i> {{ __('Cache Config') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card border">
                            <div class="card-body">
                                <h6 class="card-title">{{ __('Cache Routes') }}</h6>
                                <p class="card-text text-muted small">
                                    {{ __('Compile and cache route files for faster routing') }}
                                </p>
                                <form action="{{ route('admin.general.performance.optimize') }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="action" value="route:cache">
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <i class="las la-sync"></i> {{ __('Cache Routes') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card border">
                            <div class="card-body">
                                <h6 class="card-title">{{ __('Cache Views') }}</h6>
                                <p class="card-text text-muted small">
                                    {{ __('Compile and cache Blade views for faster rendering') }}
                                </p>
                                <form action="{{ route('admin.general.performance.optimize') }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="action" value="view:cache">
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <i class="las la-sync"></i> {{ __('Cache Views') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card border">
                            <div class="card-body">
                                <h6 class="card-title">{{ __('Optimize Composer Autoloader') }}</h6>
                                <p class="card-text text-muted small">
                                    {{ __('Optimize Composer class autoloader for faster class loading') }}
                                </p>
                                <form action="{{ route('admin.general.performance.optimize') }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="action" value="composer:optimize">
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <i class="las la-box"></i> {{ __('Optimize Autoloader') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card border">
                            <div class="card-body">
                                <h6 class="card-title">{{ __('Reset OPcache') }}</h6>
                                <p class="card-text text-muted small">
                                    {{ __('Clear OPcache to reload PHP scripts (if enabled)') }}
                                </p>
                                <form action="{{ route('admin.general.performance.optimize') }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="action" value="opcache:reset">
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <i class="las la-sync"></i> {{ __('Reset OPcache') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12 mb-3">
                        <div class="card border border-success">
                            <div class="card-body">
                                <h6 class="card-title text-success">
                                    <i class="las la-magic"></i> {{ __('Optimize All (WordPress-Style)') }}
                                </h6>
                                <p class="card-text text-muted small">
                                    {{ __('Automatically optimize everything: Laravel caches, Composer autoloader, and OPcache. Just like WordPress optimization plugins!') }}
                                </p>
                                <form action="{{ route('admin.general.performance.optimize') }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="action" value="optimize">
                                    <button type="submit" class="btn btn-success">
                                        <i class="las la-rocket"></i> {{ __('Optimize Everything Now') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cache Management -->
        <div class="row mb-4">
            <div class="col-md-12">
                <h5 class="mb-3"><i class="las la-database"></i> {{ __('Cache Management') }}</h5>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="card border">
                            <div class="card-body">
                                <h6 class="card-title">{{ __('Clear Application Cache') }}</h6>
                                <p class="card-text text-muted small">
                                    {{ __('Clear all cached data from application cache') }}
                                </p>
                                <form action="{{ route('admin.general.performance.clear') }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="action" value="cache:clear">
                                    <button type="submit" class="btn btn-sm btn-warning">
                                        <i class="las la-trash"></i> {{ __('Clear Cache') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card border">
                            <div class="card-body">
                                <h6 class="card-title">{{ __('Clear All Caches') }}</h6>
                                <p class="card-text text-muted small">
                                    {{ __('Clear config, route, view, and application cache') }}
                                </p>
                                <form action="{{ route('admin.general.performance.clear') }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="action" value="optimize:clear">
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="las la-broom"></i> {{ __('Clear All') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Information -->
        <div class="row mb-4">
            <div class="col-md-12">
                <h5 class="mb-3"><i class="las la-info-circle"></i> {{ __('System Information') }}</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <tbody>
                            <tr>
                                <td><strong>{{ __('PHP Version') }}</strong></td>
                                <td>{{ PHP_VERSION }}</td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('PHP Binary Path') }}</strong></td>
                                <td><code>{{ defined('PHP_BINARY') ? PHP_BINARY : __('Not available') }}</code></td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('Application Path') }}</strong></td>
                                <td><code>{{ base_path() }}</code></td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('Shell Exec Available') }}</strong></td>
                                <td>
                                    @if(function_exists('shell_exec') && !in_array('shell_exec', explode(',', ini_get('disable_functions'))))
                                        <span class="badge badge-success">{{ __('Yes') }}</span>
                                    @else
                                        <span class="badge badge-warning">{{ __('No') }}</span>
                                        <small class="text-muted d-block">{{ __('Some optimizations may require manual execution') }}</small>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('OPcache Available') }}</strong></td>
                                <td>
                                    @if(function_exists('opcache_reset'))
                                        <span class="badge badge-success">{{ __('Yes') }}</span>
                                    @else
                                        <span class="badge badge-danger">{{ __('No') }}</span>
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Database Management -->
        <div class="row mb-4">
            <div class="col-md-12">
                <h5 class="mb-3"><i class="las la-database"></i> {{ __('Database Management') }}</h5>
                <div class="alert alert-warning">
                    <i class="las la-exclamation-triangle"></i> <strong>{{ __('Warning:') }}</strong>
                    {{ __('Database operations can take time and may affect site availability. Use with caution in production.') }}
                </div>

                <!-- Database Backups List -->
                <div class="card border border-primary mb-4">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="las la-save"></i> {{ __('Database Backups') }}</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>{{ __('Backup Name') }}</th>
                                        <th>{{ __('Size') }}</th>
                                        <th>{{ __('Created At') }}</th>
                                        <th class="text-right">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($backups ?? [] as $backup)
                                        <tr class="{{ $backup['is_factory'] ? 'table-success' : '' }}">
                                            <td>
                                                {{ $backup['filename'] }}
                                                @if($backup['is_factory'])
                                                    <span class="badge badge-success ml-2">{{ __('Factory Default') }}</span>
                                                @endif
                                            </td>
                                            <td>{{ $backup['size_human'] }}</td>
                                            <td>{{ $backup['created_at'] }}</td>
                                            <td class="text-right">
                                                <div class="btn-group btn-group-sm">
                                                    <!-- Load Backup -->
                                                    <form action="{{ route('admin.general.backup-load') }}" method="POST" class="d-inline load-backup-form" data-name="{{ $backup['filename'] }}">
                                                        @csrf
                                                        <input type="hidden" name="backup_file" value="{{ $backup['filename'] }}">
                                                        <button type="submit" class="btn btn-primary btn-sm" title="{{ __('Load this backup') }}">
                                                            <i class="las la-upload"></i> {{ __('Load') }}
                                                        </button>
                                                    </form>

                                                    @if(!$backup['is_factory'])
                                                        <!-- Save as Factory -->
                                                        <form action="{{ route('admin.general.backup-save-factory') }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <input type="hidden" name="backup_file" value="{{ $backup['filename'] }}">
                                                            <button type="submit" class="btn btn-warning btn-sm" title="{{ __('Set as factory default') }}" onclick="return confirm('{{ __('Set this backup as factory default state?') }}')">
                                                                <i class="las la-star"></i> {{ __('Set Default') }}
                                                            </button>
                                                        </form>

                                                        <!-- Delete Backup -->
                                                        <form action="{{ route('admin.general.backup-delete') }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <input type="hidden" name="backup_file" value="{{ $backup['filename'] }}">
                                                            <button type="submit" class="btn btn-danger btn-sm" title="{{ __('Delete backup') }}" onclick="return confirm('{{ __('Delete this backup? This cannot be undone.') }}')">
                                                                <i class="las la-trash"></i> {{ __('Delete') }}
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">
                                                <i class="las la-info-circle"></i> {{ __('No backups found. Create your first backup below.') }}
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Create New Backup -->
                        <div class="border-top pt-3 mt-3">
                            <form action="{{ route('admin.general.backup-create') }}" method="POST" class="form-inline">
                                @csrf
                                <div class="form-group mr-2">
                                    <input type="text" name="backup_name" class="form-control" placeholder="{{ __('Backup name (optional)') }}" value="backup_{{ date('Y-m-d_H-i-s') }}">
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="las la-save"></i> {{ __('Create New Backup') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="card border border-info">
                            <div class="card-body">
                                <h6 class="card-title text-info">
                                    <i class="las la-sync-alt"></i> {{ __('Re-seed / Restore State') }}
                                </h6>
                                <p class="card-text text-muted small">
                                    {{ __('Restore database from factory state or a backup. Choose your source below.') }}
                                </p>
                                
                                <!-- Factory State Option -->
                                <div class="mb-3">
                                    <strong class="d-block mb-2">{{ __('Option 1: Factory State (Seeders)') }}</strong>
                                    <ul class="small text-muted mb-2">
                                        <li>✅ {{ __('Fresh install with demo data') }}</li>
                                        <li>✅ {{ __('Uses DatabaseSeeder (17 seeders)') }}</li>
                                        <li>⚠️ {{ __('Wipes ALL data, re-migrates, seeds') }}</li>
                                    </ul>
                                    <form action="{{ route('admin.general.backup-load-factory') }}" method="POST" class="factory-restore-form">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-info">
                                            <i class="las la-industry"></i> {{ __('Restore Factory State') }}
                                        </button>
                                    </form>
                                </div>

                                <div class="dropdown-divider"></div>

                                <!-- Backup State Option -->
                                <div>
                                    <strong class="d-block mb-2">{{ __('Option 2: Load Backup State') }}</strong>
                                    <ul class="small text-muted mb-2">
                                        <li>✅ {{ __('Restore from specific backup') }}</li>
                                        <li>✅ {{ __('Preserves exact state at backup time') }}</li>
                                        <li>⚠️ {{ __('Wipes current data') }}</li>
                                    </ul>
                                    @if(count($backups ?? []) > 0)
                                        <form action="{{ route('admin.general.backup-load') }}" method="POST" class="backup-restore-form">
                                            @csrf
                                            <div class="form-group">
                                                <select name="backup_file" class="form-control form-control-sm" required>
                                                    <option value="">{{ __('Select backup...') }}</option>
                                                    @foreach($backups ?? [] as $backup)
                                                        <option value="{{ $backup['filename'] }}">
                                                            {{ $backup['filename'] }} ({{ $backup['size_human'] }}) - {{ $backup['created_at'] }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <button type="submit" class="btn btn-sm btn-primary">
                                                <i class="las la-upload"></i> {{ __('Load Selected Backup') }}
                                            </button>
                                        </form>
                                    @else
                                        <p class="text-muted small">{{ __('No backups available. Create one first.') }}</p>
                                    @endif
                                </div>

                                <div class="dropdown-divider"></div>

                                <!-- Safe Reseed (Add Data Only) -->
                                <div>
                                    <strong class="d-block mb-2">{{ __('Option 3: Safe Re-seed (Add Data)') }}</strong>
                                    <ul class="small text-muted mb-2">
                                        <li>✅ {{ __('Adds demo data WITHOUT deleting') }}</li>
                                        <li>✅ {{ __('Idempotent (safe to run multiple times)') }}</li>
                                        <li>✅ {{ __('No data loss') }}</li>
                                    </ul>
                                    <form action="{{ route('admin.general.reseed-database') }}" method="POST" class="reseed-form">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success">
                                            <i class="las la-seedling"></i> {{ __('Add Demo Data (Safe)') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card border border-danger">
                            <div class="card-body">
                                <h6 class="card-title text-danger">
                                    <i class="las la-exclamation-triangle"></i> {{ __('Full Database Reset') }}
                                </h6>
                                <p class="card-text text-muted small">
                                    {{ __('⚠️ DANGEROUS: Completely wipe database, re-run migrations, and seed fresh data. ALL DATA WILL BE LOST!') }}
                                </p>
                                <ul class="small text-muted mb-3">
                                    <li>❌ {{ __('Deletes ALL users, signals, payments, subscriptions') }}</li>
                                    <li>❌ {{ __('Cannot be undone') }}</li>
                                    <li>✅ {{ __('Only use for testing/development') }}</li>
                                </ul>
                                <div class="form-group">
                                    <label class="text-danger">{{ __('Type "RESET" to confirm:') }}</label>
                                    <input type="text" class="form-control reset-confirm" placeholder="RESET" required>
                                </div>
                                <form action="{{ route('admin.general.reset-database') }}" method="POST" class="reset-form">
                                    @csrf
                                    <input type="hidden" name="confirm" value="">
                                    <button type="submit" class="btn btn-danger" disabled>
                                        <i class="las la-trash-restore"></i> {{ __('Reset Entire Database') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Tips (Dynamic) -->
        <div class="row">
            <div class="col-md-12">
                <h5 class="mb-3">
                    <i class="las la-lightbulb"></i> {{ __('Performance Tips') }}
                    <small class="text-muted">({{ __('Based on your application analysis') }})</small>
                </h5>
                <div class="list-group">
                    @if(isset($performanceTips['database']) && !empty($performanceTips['database']))
                        <div class="list-group-item">
                            <h6 class="mb-2"><i class="las la-database text-primary"></i> {{ __('Database Optimization') }}</h6>
                            <ul class="mb-0 small">
                                @foreach($performanceTips['database'] as $tip)
                                    <li class="mb-2">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <strong>{{ $tip['tip'] }}</strong>
                                                @if(isset($tip['example']))
                                                    <br><code class="small text-muted">{{ $tip['example'] }}</code>
                                                @endif
                                            </div>
                                            <div class="ml-2">
                                                @if(isset($tip['detected']) && $tip['detected'] === false)
                                                    <span class="badge badge-warning">{{ __('Action Needed') }}</span>
                                                @elseif(isset($tip['detected']) && $tip['detected'] === true)
                                                    <span class="badge badge-info">{{ __('Detected') }}</span>
                                                @endif
                                                @if(isset($tip['priority']))
                                                    @if($tip['priority'] === 'high')
                                                        <span class="badge badge-danger">{{ __('High Priority') }}</span>
                                                    @elseif($tip['priority'] === 'medium')
                                                        <span class="badge badge-warning">{{ __('Medium Priority') }}</span>
                                                    @else
                                                        <span class="badge badge-success">{{ __('Low Priority') }}</span>
                                                    @endif
                                                @endif
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if(isset($performanceTips['server']) && !empty($performanceTips['server']))
                        <div class="list-group-item">
                            <h6 class="mb-2"><i class="las la-server text-success"></i> {{ __('Server Configuration') }}</h6>
                            <ul class="mb-0 small">
                                @foreach($performanceTips['server'] as $tip)
                                    <li class="mb-2">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <strong>{{ $tip['tip'] }}</strong>
                                                @if(isset($tip['example']))
                                                    <br><code class="small text-muted">{{ $tip['example'] }}</code>
                                                @endif
                                                @if(isset($tip['status']))
                                                    <br><small class="text-info"><i class="las la-info-circle"></i> {{ $tip['status'] }}</small>
                                                @endif
                                            </div>
                                            <div class="ml-2">
                                                @if(isset($tip['detected']) && $tip['detected'] === false)
                                                    <span class="badge badge-warning">{{ __('Not Configured') }}</span>
                                                @elseif(isset($tip['detected']) && $tip['detected'] === true)
                                                    <span class="badge badge-success">{{ __('Configured') }}</span>
                                                @endif
                                                @if(isset($tip['priority']))
                                                    @if($tip['priority'] === 'high')
                                                        <span class="badge badge-danger">{{ __('High Priority') }}</span>
                                                    @elseif($tip['priority'] === 'medium')
                                                        <span class="badge badge-warning">{{ __('Medium Priority') }}</span>
                                                    @else
                                                        <span class="badge badge-success">{{ __('Low Priority') }}</span>
                                                    @endif
                                                @endif
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if(isset($performanceTips['code']) && !empty($performanceTips['code']))
                        <div class="list-group-item">
                            <h6 class="mb-2"><i class="las la-code text-warning"></i> {{ __('Code Optimization') }}</h6>
                            <ul class="mb-0 small">
                                @foreach($performanceTips['code'] as $tip)
                                    <li class="mb-2">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <strong>{{ $tip['tip'] }}</strong>
                                                @if(isset($tip['example']))
                                                    <br><code class="small text-muted">{{ $tip['example'] }}</code>
                                                @endif
                                                @if(isset($tip['status']))
                                                    <br><small class="text-info"><i class="las la-info-circle"></i> {{ $tip['status'] }}</small>
                                                @endif
                                            </div>
                                            <div class="ml-2">
                                                @if(isset($tip['detected']) && $tip['detected'] === false)
                                                    <span class="badge badge-warning">{{ __('Action Needed') }}</span>
                                                @elseif(isset($tip['detected']) && $tip['detected'] === true)
                                                    <span class="badge badge-success">{{ __('In Use') }}</span>
                                                @endif
                                                @if(isset($tip['priority']))
                                                    @if($tip['priority'] === 'high')
                                                        <span class="badge badge-danger">{{ __('High Priority') }}</span>
                                                    @elseif($tip['priority'] === 'medium')
                                                        <span class="badge badge-warning">{{ __('Medium Priority') }}</span>
                                                    @else
                                                        <span class="badge badge-success">{{ __('Low Priority') }}</span>
                                                    @endif
                                                @endif
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('script')
<script>
    $(document).ready(function() {
        // Reset form confirmation
        $('.reset-confirm').on('input', function() {
            const val = $(this).val();
            const form = $(this).closest('.card').find('.reset-form');
            const btn = form.find('button[type="submit"]');
            
            if (val === 'RESET') {
                btn.prop('disabled', false);
                form.find('input[name="confirm"]').val('RESET');
            } else {
                btn.prop('disabled', true);
                form.find('input[name="confirm"]').val('');
            }
        });

        // Reseed form confirmation
        $('.reseed-form').on('submit', function(e) {
            if (!confirm('{{ __("Add demo data to database? This is safe and won\'t delete existing data.") }}')) {
                e.preventDefault();
                return false;
            }
        });

        // Reset form confirmation
        $('.reset-form').on('submit', function(e) {
            if (!confirm('{{ __("⚠️ FINAL WARNING: This will DELETE ALL DATA! Are you absolutely sure?") }}')) {
                e.preventDefault();
                return false;
            }
        });

        // Factory restore confirmation
        $('.factory-restore-form').on('submit', function(e) {
            if (!confirm('{{ __("⚠️ WARNING: This will WIPE current database and restore factory state. Continue?") }}')) {
                e.preventDefault();
                return false;
            }
        });

        // Backup restore confirmation
        $('.backup-restore-form').on('submit', function(e) {
            const backupName = $(this).find('select[name="backup_file"]').val();
            if (!backupName) {
                alert('{{ __("Please select a backup to restore") }}');
                e.preventDefault();
                return false;
            }
            if (!confirm('{{ __("⚠️ WARNING: This will WIPE current database and restore from backup. Continue?") }}\n\n{{ __("Backup:") }} ' + backupName)) {
                e.preventDefault();
                return false;
            }
        });

        // Load backup form in table (individual load buttons)
        $('.load-backup-form').on('submit', function(e) {
            const backupName = $(this).data('name');
            if (!confirm('{{ __("⚠️ Restore from this backup? Current data will be WIPED.") }}\n\n{{ __("Backup:") }} ' + backupName)) {
                e.preventDefault();
                return false;
            }
        });

        // Enhanced loading state with progress feedback (WordPress-style)
        $('form[action*="performance"], .reseed-form, .reset-form').on('submit', function(e) {
            const form = $(this);
            const btn = form.find('button[type="submit"]');
            const originalText = btn.html();
            
            // Disable button and show loading
            btn.prop('disabled', true)
               .html('<i class="las la-spinner la-spin"></i> {{ __('Optimizing...') }}')
               .addClass('processing');
            
            // Add processing overlay to card
            const card = form.closest('.card');
            if (card.length) {
                card.addClass('processing');
            }
            
            // Re-enable after 10 seconds as fallback (optimizations can take time)
            setTimeout(function() {
                if (btn.hasClass('processing')) {
                    btn.prop('disabled', false)
                       .html(originalText)
                       .removeClass('processing');
                    if (card.length) {
                        card.removeClass('processing');
                    }
                }
            }, 10000);
        });
        
        // Auto-refresh OPcache stats every 30 seconds if on performance tab
        @if(request()->has('tab') && request()->get('tab') === 'performance' || !request()->has('tab'))
            setInterval(function() {
                // Only refresh if tab is active
                if ($('#performance').hasClass('active')) {
                    // Could add AJAX refresh here if needed
                }
            }, 30000);
        @endif
    });
</script>
<style>
    .card.processing {
        opacity: 0.7;
        position: relative;
    }
    .card.processing::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.5);
        z-index: 1;
    }
</style>
@endpush
