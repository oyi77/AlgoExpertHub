<div class="card">
    <div class="card-header">
        <h4 class="m-0">{{ __('Performance Settings') }}</h4>
    </div>
    <div class="card-body">
        <!-- System Information -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0"><i class="las la-info-circle"></i> {{ __('System Information') }}</h5>
                    <small class="text-muted" id="system-info-last-update">{{ __('Last updated: Loading...') }}</small>
                </div>
                <div class="table-responsive" id="system-info-table">
                    <table class="table table-bordered table-sm">
                        <tbody>
                            <tr>
                                <td><strong>{{ __('PHP Version') }}</strong></td>
                                <td id="sys-php-version">{{ PHP_VERSION }}</td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('Laravel Version') }}</strong></td>
                                <td id="sys-laravel-version">{{ app()->version() }}</td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('PHP Binary Path') }}</strong></td>
                                <td><code id="sys-php-binary">{{ defined('PHP_BINARY') ? PHP_BINARY : __('Not available') }}</code></td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('Application Path') }}</strong></td>
                                <td><code id="sys-app-path">{{ base_path() }}</code></td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('Environment') }}</strong></td>
                                <td><span class="badge badge-{{ app()->environment() === 'production' ? 'success' : 'warning' }}" id="sys-environment">{{ app()->environment() }}</span></td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('Timezone') }}</strong></td>
                                <td id="sys-timezone">{{ config('app.timezone') }}</td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('Locale') }}</strong></td>
                                <td id="sys-locale">{{ app()->getLocale() }}</td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('Server Software') }}</strong></td>
                                <td id="sys-server-software">{{ $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' }}</td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('Memory Usage') }}</strong></td>
                                <td id="sys-memory-usage">
                                    @if(function_exists('memory_get_usage'))
                                        {{ number_format(memory_get_usage(true) / 1024 / 1024, 2) }} MB / {{ ini_get('memory_limit') }}
                                    @else
                                        {{ __('Not available') }}
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('Memory Peak') }}</strong></td>
                                <td id="sys-memory-peak">
                                    @if(function_exists('memory_get_peak_usage'))
                                        {{ number_format(memory_get_peak_usage(true) / 1024 / 1024, 2) }} MB
                                    @else
                                        {{ __('Not available') }}
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('PHP Extensions') }}</strong></td>
                                <td id="sys-extensions">
                                    {{ count(get_loaded_extensions()) }} {{ __('loaded') }}
                                    <small class="text-muted d-block">
                                        PDO: {{ extension_loaded('pdo') ? '✓' : '✗' }} | 
                                        MBString: {{ extension_loaded('mbstring') ? '✓' : '✗' }} | 
                                        OpenSSL: {{ extension_loaded('openssl') ? '✓' : '✗' }} | 
                                        cURL: {{ extension_loaded('curl') ? '✓' : '✗' }}
                                    </small>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('Disk Space') }}</strong></td>
                                <td id="sys-disk-space">
                                    @if(function_exists('disk_free_space') && function_exists('disk_total_space'))
                                        @php
                                            $free = disk_free_space(base_path());
                                            $total = disk_total_space(base_path());
                                            $used = $total - $free;
                                            $percent = $total > 0 ? ($used / $total) * 100 : 0;
                                        @endphp
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar {{ $percent > 90 ? 'bg-danger' : ($percent > 75 ? 'bg-warning' : 'bg-success') }}" 
                                                 style="width: {{ $percent }}%">
                                                {{ number_format($percent, 1) }}%
                                            </div>
                                        </div>
                                        <small class="text-muted">
                                            {{ number_format($free / 1024 / 1024 / 1024, 2) }} GB free / 
                                            {{ number_format($total / 1024 / 1024 / 1024, 2) }} GB total
                                        </small>
                                    @else
                                        {{ __('Not available') }}
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('PHP Processes') }}</strong></td>
                                <td id="sys-php-processes">{{ __('Loading...') }}</td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('System Load') }}</strong></td>
                                <td id="sys-load-average">{{ __('Loading...') }}</td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('Server Uptime') }}</strong></td>
                                <td id="sys-uptime">{{ __('Loading...') }}</td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('Shell Exec Available') }}</strong></td>
                                <td id="sys-shell-exec">
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
                                <td id="sys-opcache-available">
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

        <!-- System Monitoring -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0"><i class="las la-memory"></i> {{ __('System Monitoring') }}</h5>
                    <small class="text-muted" id="opcache-last-update">{{ __('Last updated: Loading...') }}</small>
                </div>
                <div class="table-responsive" id="opcache-status-table">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>{{ __('Metric') }}</th>
                                <th>{{ __('Status/Visual') }}</th>
                                <th>{{ __('Value') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>{{ __('OPcache Status') }}</strong></td>
                                <td id="opcache-enabled-status">
                                    @if(function_exists('opcache_get_status') && opcache_get_status() !== false)
                                        <span class="badge badge-success">{{ __('Enabled') }}</span>
                                    @else
                                        <span class="badge badge-danger">{{ __('Disabled') }}</span>
                                    @endif
                                </td>
                                <td id="opcache-scripts-cached">
                                    @if(function_exists('opcache_get_status'))
                                        @php
                                            $opcache = opcache_get_status();
                                        @endphp
                                        @if($opcache && isset($opcache['opcache_statistics']))
                                            {{ number_format($opcache['opcache_statistics']['num_cached_scripts'] ?? 0) }} {{ __('scripts cached') }}
                                        @else
                                            {{ __('Not Available') }}
                                        @endif
                                    @else
                                        {{ __('OPcache extension not installed') }}
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('Memory Usage') }}</strong></td>
                                <td id="opcache-memory-progress">
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-info" role="progressbar" style="width: 0%">0%</div>
                                    </div>
                                </td>
                                <td id="opcache-memory-value">{{ __('Loading...') }}</td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('Hit Rate') }}</strong></td>
                                <td id="opcache-hitrate-progress">
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-info" role="progressbar" style="width: 0%">0%</div>
                                    </div>
                                </td>
                                <td id="opcache-hitrate-value">{{ __('Loading...') }}</td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('Cached Keys') }}</strong></td>
                                <td>
                                    <span class="badge badge-info" id="opcache-keys-badge">-</span>
                                </td>
                                <td id="opcache-keys-value">{{ __('Loading...') }}</td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('Wasted Memory') }}</strong></td>
                                <td id="opcache-wasted-progress">
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-warning" role="progressbar" style="width: 0%">0%</div>
                                    </div>
                                </td>
                                <td id="opcache-wasted-value">{{ __('Loading...') }}</td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('Interned Strings') }}</strong></td>
                                <td>
                                    <span class="badge badge-secondary" id="opcache-strings-badge">-</span>
                                </td>
                                <td id="opcache-strings-value">{{ __('Loading...') }}</td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('Max Accelerated Files') }}</strong></td>
                                <td>
                                    <span class="badge badge-primary" id="opcache-max-files-badge">-</span>
                                </td>
                                <td id="opcache-max-files-value">{{ __('Loading...') }}</td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('Opening/Running Processes') }}</strong></td>
                                <td>
                                    <span class="badge badge-info" id="processes-php-badge">-</span>
                                </td>
                                <td id="processes-php-value">{{ __('Loading...') }}</td>
                            </tr>
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
                    <div class="col-md-6 mb-3">
                        <div class="card border border-warning">
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
                        <div class="card border border-danger">
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

                    <!-- Optimize All Button -->
                    <div class="col-md-6 mb-3">
                        <div class="card border border-success">
                            <div class="card-body">
                                <h6 class="card-title text-success">
                                    <i class="las la-magic"></i> {{ __('Optimize All') }}
                                </h6>
                                <p class="card-text text-muted small">
                                    {{ __('Automatically clear all caches, then optimize everything: Laravel caches, Composer autoloader, and OPcache.') }}
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
                                                            <button type="submit" class="btn btn-warning btn-sm" title="{{ __('Set as factory default') }}">
                                                                <i class="las la-star"></i> {{ __('Set Default') }}
                                                            </button>
                                                        </form>

                                                        <!-- Delete Backup -->
                                                        <form action="{{ route('admin.general.backup-delete') }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <input type="hidden" name="backup_file" value="{{ $backup['filename'] }}">
                                                            <button type="submit" class="btn btn-danger btn-sm" title="{{ __('Delete backup') }}">
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
                                        <li>✅ {{ __('Uses DatabaseSeeder (:count seeders)', ['count' => $seederCount ?? 0]) }}</li>
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

        // Helper function to show loading state
        function showLoadingState(btn) {
            const originalText = btn.html();
            btn.data('original-text', originalText);
            
            // Disable button and show loading
            btn.prop('disabled', true)
               .html('<i class="las la-spinner la-spin"></i> {{ __('Processing...') }}')
               .addClass('processing');
            
            // Add processing overlay to card
            const card = btn.closest('.card');
            if (card.length) {
                card.addClass('processing');
            }
        }

        // Helper function to restore button state
        function restoreButtonState(btn) {
            const originalText = btn.data('original-text') || btn.html();
            btn.prop('disabled', false)
               .html(originalText)
               .removeClass('processing');
            
            const card = btn.closest('.card');
            if (card.length) {
                card.removeClass('processing');
            }
        }

        // Helper function to show toastr notification
        function showNotification(success, message) {
            if (typeof toastr !== 'undefined') {
                if (success) {
                    toastr.success(message, '', {
                        positionClass: "toast-top-right",
                        timeOut: 5000
                    });
                } else {
                    toastr.error(message, '', {
                        positionClass: "toast-top-right",
                        timeOut: 5000
                    });
                }
            } else {
                // Fallback to alert if toastr not available
                alert(message);
            }
        }

        // Helper function to handle AJAX form submission
        function handleAjaxForm(e, form, confirmMessage) {
            // CRITICAL: Prevent default FIRST, before anything else
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            
            // Show confirmation if needed - this is blocking, so execution stops here if cancelled
            if (confirmMessage) {
                if (!confirm(confirmMessage)) {
                    return false;
                }
            }
            
            // Only proceed if confirmed (or no confirmation needed)
            const btn = form.find('button[type="submit"]');
            showLoadingState(btn);
            
            const formData = new FormData(form[0]);
            const url = form.attr('action');
            
            // Set longer timeout for long-running operations (like seeding)
            const isLongOperation = form.hasClass('reseed-form') || form.hasClass('reset-form') || form.hasClass('factory-restore-form') || form.hasClass('backup-restore-form');
            const timeout = isLongOperation ? 300000 : 30000; // 5 minutes for long ops, 30s for others
            
            $.ajax({
                url: url,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                timeout: timeout,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': form.find('input[name="_token"]').val() || $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    restoreButtonState(btn);
                    
                    if (response.success) {
                        showNotification(true, response.message);
                        
                        // Handle redirect if needed
                        if (response.redirect) {
                            setTimeout(function() {
                                window.location.href = response.redirect;
                            }, 1500);
                            return;
                        }
                        
                        // Refresh backup list if needed
                        if (response.refresh) {
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        }
                    } else {
                        showNotification(false, response.message || '{{ __("Operation failed") }}');
                    }
                },
                error: function(xhr) {
                    restoreButtonState(btn);
                    
                    let errorMessage = '{{ __("An error occurred") }}';
                    if (xhr.status === 0 || xhr.statusText === 'timeout') {
                        errorMessage = '{{ __("Request timed out. The operation may still be processing. Please wait and refresh the page.") }}';
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.statusText) {
                        errorMessage = xhr.statusText;
                    }
                    
                    showNotification(false, errorMessage);
                }
            });
            
            return false;
        }

        // Performance optimization forms (AJAX)
        $('form[action*="performance"]').off('submit').on('submit', function(e) {
            // CRITICAL: Prevent default FIRST
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            
            const form = $(this);
            const btn = form.find('button[type="submit"]');
            showLoadingState(btn);
            
            const formData = new FormData(form[0]);
            const url = form.attr('action');
            
            $.ajax({
                url: url,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': form.find('input[name="_token"]').val() || $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    restoreButtonState(btn);
                    if (response.success) {
                        showNotification(true, response.message);
                    } else {
                        showNotification(false, response.message || '{{ __("Operation failed") }}');
                    }
                },
                error: function(xhr) {
                    restoreButtonState(btn);
                    let errorMessage = '{{ __("An error occurred") }}';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    showNotification(false, errorMessage);
                }
            });
            
            return false;
        });

        // Reseed form (AJAX)
        $('.reseed-form').off('submit').on('submit', function(e) {
            return handleAjaxForm(e, $(this), '{{ __("Add demo data to database? This is safe and won\'t delete existing data.") }}');
        });

        // Reset form (AJAX)
        $('.reset-form').off('submit').on('submit', function(e) {
            return handleAjaxForm(e, $(this), '{{ __("⚠️ FINAL WARNING: This will DELETE ALL DATA! Are you absolutely sure?") }}');
        });

        // Factory restore form (AJAX)
        $('.factory-restore-form').off('submit').on('submit', function(e) {
            return handleAjaxForm(e, $(this), '{{ __("⚠️ WARNING: This will WIPE current database and restore factory state. Continue?") }}');
        });

        // Backup restore form (AJAX)
        $('.backup-restore-form').off('submit').on('submit', function(e) {
            // CRITICAL: Prevent default FIRST
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            
            const form = $(this);
            const backupName = form.find('select[name="backup_file"]').val();
            
            if (!backupName) {
                alert('{{ __("Please select a backup to restore") }}');
                return false;
            }
            
            return handleAjaxForm(e, form, '{{ __("⚠️ WARNING: This will WIPE current database and restore from backup. Continue?") }}\n\n{{ __("Backup:") }} ' + backupName);
        });

        // Load backup form in table (AJAX)
        $('.load-backup-form').off('submit').on('submit', function(e) {
            // CRITICAL: Prevent default FIRST
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            
            const form = $(this);
            const backupName = form.data('name');
            return handleAjaxForm(e, form, '{{ __("⚠️ Restore from this backup? Current data will be WIPED.") }}\n\n{{ __("Backup:") }} ' + backupName);
        });

        // Create backup form (AJAX)
        $('form[action*="backup-create"]').off('submit').on('submit', function(e) {
            // CRITICAL: Prevent default FIRST
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            
            const form = $(this);
            const btn = form.find('button[type="submit"]');
            showLoadingState(btn);
            
            const formData = new FormData(form[0]);
            const url = form.attr('action');
            
            $.ajax({
                url: url,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': form.find('input[name="_token"]').val() || $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    restoreButtonState(btn);
                    if (response.success) {
                        showNotification(true, response.message);
                        if (response.refresh) {
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        }
                    } else {
                        showNotification(false, response.message || '{{ __("Operation failed") }}');
                    }
                },
                error: function(xhr) {
                    restoreButtonState(btn);
                    let errorMessage = '{{ __("An error occurred") }}';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    showNotification(false, errorMessage);
                }
            });
            
            return false;
        });

        // Delete backup and set factory buttons (AJAX with inline confirm)
        $('form[action*="backup-delete"], form[action*="backup-save-factory"]').off('submit').on('submit', function(e) {
            // CRITICAL: Prevent default FIRST
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            
            const form = $(this);
            const btn = form.find('button[type="submit"]');
            const action = form.attr('action');
            
            // Get confirmation message from button onclick or default
            let confirmMsg = btn.attr('onclick');
            if (confirmMsg) {
                // Extract message from onclick="return confirm('message')"
                const match = confirmMsg.match(/confirm\(['"]([^'"]+)['"]\)/);
                if (match) {
                    confirmMsg = match[1];
                } else {
                    confirmMsg = action.includes('delete') 
                        ? '{{ __("Delete this backup? This cannot be undone.") }}'
                        : '{{ __("Set this backup as factory default state?") }}';
                }
            } else {
                confirmMsg = action.includes('delete') 
                    ? '{{ __("Delete this backup? This cannot be undone.") }}'
                    : '{{ __("Set this backup as factory default state?") }}';
            }
            
            // Show confirmation - this is blocking, execution stops here if cancelled
            if (!confirm(confirmMsg)) {
                return false;
            }
            
            // Only proceed if confirmed
            showLoadingState(btn);
            
            const formData = new FormData(form[0]);
            
            $.ajax({
                url: action,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': form.find('input[name="_token"]').val() || $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    restoreButtonState(btn);
                    if (response.success) {
                        showNotification(true, response.message);
                        if (response.refresh) {
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        }
                    } else {
                        showNotification(false, response.message || '{{ __("Operation failed") }}');
                    }
                },
                error: function(xhr) {
                    restoreButtonState(btn);
                    let errorMessage = '{{ __("An error occurred") }}';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    showNotification(false, errorMessage);
                }
            });
            
            return false;
        });
        
        // Real-time system monitoring using Server-Sent Events (SSE)
        let eventSource = null;
        let reconnectTimeout = null;
        
        function connectSystemMonitoring() {
            // Close existing connection
            if (eventSource) {
                eventSource.close();
                eventSource = null;
            }
            
            // Clear any pending reconnects
            if (reconnectTimeout) {
                clearTimeout(reconnectTimeout);
                reconnectTimeout = null;
            }
            
            try {
                const url = "{{ route('admin.general.performance.stream') }}";
                eventSource = new EventSource(url);
                
                eventSource.onopen = function() {
                    console.log('System monitoring SSE connected');
                };
                
                eventSource.onmessage = function(event) {
                    // Ignore keepalive comments
                    if (event.data.trim() === '' || event.data.startsWith(':')) {
                        return;
                    }
                    
                    try {
                        const data = JSON.parse(event.data);
                        
                        if (data.type === 'connected') {
                            console.log('System monitoring connected');
                        } else if (data.type === 'status') {
                            updateSystemStatusUI(data);
                        } else if (data.type === 'error') {
                            console.error('SSE error:', data.message);
                        }
                    } catch (e) {
                        if (!event.data.startsWith(':')) {
                            console.error('Error parsing SSE data:', e, event.data);
                        }
                    }
                };
                
                eventSource.onerror = function(error) {
                    if (eventSource && eventSource.readyState === EventSource.CLOSED) {
                        console.warn('SSE connection closed, reconnecting...');
                        reconnectTimeout = setTimeout(function() {
                            connectSystemMonitoring();
                        }, 3000);
                    }
                };
            } catch (e) {
                console.error('Error creating SSE connection:', e);
                // Fallback to polling if SSE fails
                fallbackToPolling();
            }
        }
        
        function updateSystemStatusUI(data) {
            const timestamp = new Date(data.timestamp).toLocaleTimeString();
            $('#system-info-last-update, #opcache-last-update').text('{{ __("Last updated:") }} ' + timestamp);
            
            // Update system info
            if (data.system) {
                const sys = data.system;
                $('#sys-php-version').text(sys.php_version);
                $('#sys-laravel-version').text(sys.laravel_version || '{{ app()->version() }}');
                $('#sys-php-binary').text(sys.php_binary || '{{ __("Not available") }}');
                $('#sys-app-path').text(sys.application_path);
                $('#sys-environment').text(sys.environment).removeClass('badge-success badge-warning').addClass(sys.environment === 'production' ? 'badge-success' : 'badge-warning');
                $('#sys-timezone').text(sys.timezone);
                $('#sys-locale').text(sys.locale);
                $('#sys-server-software').text(sys.server_software || 'Unknown');
                
                if (sys.memory_usage) {
                    $('#sys-memory-usage').text(
                        (sys.memory_usage / 1024 / 1024).toFixed(2) + ' MB / ' + (sys.memory_limit || 'N/A')
                    );
                }
                if (sys.memory_peak) {
                    $('#sys-memory-peak').text((sys.memory_peak / 1024 / 1024).toFixed(2) + ' MB');
                }
                
                if (sys.disk_free && sys.disk_total) {
                    const diskUsed = sys.disk_total - sys.disk_free;
                    const diskPercent = (diskUsed / sys.disk_total) * 100;
                    $('#sys-disk-space').html(
                        '<div class="progress" style="height: 20px;">' +
                        '<div class="progress-bar ' + (diskPercent > 90 ? 'bg-danger' : diskPercent > 75 ? 'bg-warning' : 'bg-success') + '" ' +
                        'style="width: ' + diskPercent.toFixed(1) + '%">' + diskPercent.toFixed(1) + '%</div></div>' +
                        '<small class="text-muted">' +
                        (sys.disk_free / 1024 / 1024 / 1024).toFixed(2) + ' GB free / ' +
                        (sys.disk_total / 1024 / 1024 / 1024).toFixed(2) + ' GB total</small>'
                    );
                }
                
                if (sys.loaded_extensions) {
                    const ext = sys.important_extensions || {};
                    $('#sys-extensions').html(
                        sys.loaded_extensions + ' {{ __("loaded") }}' +
                        '<small class="text-muted d-block">' +
                        'PDO: ' + (ext.pdo ? '✓' : '✗') + ' | ' +
                        'MBString: ' + (ext.mbstring ? '✓' : '✗') + ' | ' +
                        'OpenSSL: ' + (ext.openssl ? '✓' : '✗') + ' | ' +
                        'cURL: ' + (ext.curl ? '✓' : '✗') +
                        '</small>'
                    );
                }
            }
            
            // Update process info
            if (data.processes) {
                const proc = data.processes;
                if (proc.php_processes !== null) {
                    $('#sys-php-processes').text(proc.php_processes + ' {{ __("processes") }}');
                    $('#processes-php-badge').text(proc.php_processes);
                    $('#processes-php-value').text(proc.php_processes + ' {{ __("PHP processes running") }}');
                }
                if (proc.system_load) {
                    const load = Array.isArray(proc.system_load) ? proc.system_load.join(', ') : proc.system_load;
                    $('#sys-load-average').text(load);
                }
                if (proc.uptime) {
                    $('#sys-uptime').text(proc.uptime);
                }
            }
            
            // Update OPcache status
            if (data.opcache) {
                const op = data.opcache;
                
                if (!op.enabled) {
                    $('#opcache-enabled-status').html('<span class="badge badge-danger">{{ __("Disabled") }}</span>');
                    return;
                }
                
                $('#opcache-enabled-status').html('<span class="badge badge-success">{{ __("Enabled") }}</span>');
                
                // Memory usage
                if (op.memory) {
                    const memPercent = op.memory.percent || 0;
                    const memClass = memPercent > 80 ? 'bg-danger' : memPercent > 60 ? 'bg-warning' : 'bg-success';
                    $('#opcache-memory-progress').html(
                        '<div class="progress" style="height: 20px;">' +
                        '<div class="progress-bar ' + memClass + '" style="width: ' + memPercent + '%">' +
                        memPercent.toFixed(1) + '%</div></div>'
                    );
                    $('#opcache-memory-value').text(
                        op.memory.used_mb + ' MB / ' + op.memory.total_mb + ' MB'
                    );
                }
                
                // Hit rate
                if (op.statistics) {
                    const hitRate = op.statistics.hit_rate || 0;
                    const hitClass = hitRate > 90 ? 'bg-success' : hitRate > 70 ? 'bg-warning' : 'bg-danger';
                    $('#opcache-hitrate-progress').html(
                        '<div class="progress" style="height: 20px;">' +
                        '<div class="progress-bar ' + hitClass + '" style="width: ' + hitRate + '%">' +
                        hitRate.toFixed(1) + '%</div></div>'
                    );
                    $('#opcache-hitrate-value').text(
                        op.statistics.hits.toLocaleString() + ' {{ __("hits") }} / ' +
                        op.statistics.total_requests.toLocaleString() + ' {{ __("requests") }}'
                    );
                    
                    // Scripts cached
                    $('#opcache-scripts-cached').text(
                        op.statistics.num_cached_scripts.toLocaleString() + ' {{ __("scripts cached") }}'
                    );
                    
                    // Keys
                    $('#opcache-keys-badge').text(op.statistics.num_cached_keys || 0);
                    $('#opcache-keys-value').text(
                        (op.statistics.num_cached_keys || 0).toLocaleString() + ' / ' +
                        (op.statistics.max_cached_keys || 0).toLocaleString() + ' {{ __("max") }}'
                    );
                }
                
                // Wasted memory
                if (op.memory && op.configuration) {
                    const wasted = op.memory.wasted || 0;
                    const maxWasted = op.configuration.max_wasted_percentage || 5;
                    const wastedPercent = op.memory.total > 0 ? (wasted / op.memory.total) * 100 : 0;
                    const wastedClass = wastedPercent > maxWasted ? 'bg-danger' : wastedPercent > (maxWasted * 0.7) ? 'bg-warning' : 'bg-success';
                    $('#opcache-wasted-progress').html(
                        '<div class="progress" style="height: 20px;">' +
                        '<div class="progress-bar ' + wastedClass + '" style="width: ' + Math.min(wastedPercent * 2, 100) + '%">' +
                        wastedPercent.toFixed(2) + '%</div></div>'
                    );
                    $('#opcache-wasted-value').text(
                        (wasted / 1024 / 1024).toFixed(2) + ' MB ({{ __("max") }}: ' + maxWasted + '%)'
                    );
                }
                
                // Interned strings
                if (op.interned_strings) {
                    const strings = op.interned_strings;
                    $('#opcache-strings-badge').text(strings.number_of_strings || 0);
                    $('#opcache-strings-value').text(
                        (strings.number_of_strings || 0).toLocaleString() + ' {{ __("strings") }} | ' +
                        (strings.used_memory / 1024 / 1024).toFixed(2) + ' MB {{ __("used") }}'
                    );
                }
                
                // Max files
                if (op.configuration) {
                    $('#opcache-max-files-badge').text(op.configuration.max_accelerated_files || 0);
                    $('#opcache-max-files-value').text(
                        (op.configuration.max_accelerated_files || 0).toLocaleString() + ' {{ __("files") }}'
                    );
                }
            }
        }
        
        // Fallback to polling if SSE not available
        function fallbackToPolling() {
            console.warn('SSE not available, falling back to polling');
            let pollingInterval = setInterval(function() {
                $.ajax({
                    url: "{{ route('admin.general.performance.status') }}",
                    method: 'GET',
                    success: function(response) {
                        if (response.success && response.data) {
                            updateSystemStatusUI(response.data);
                        }
                    }
                });
            }, 5000);
            
            // Cleanup on unload
            $(window).on('beforeunload', function() {
                clearInterval(pollingInterval);
            });
        }
        
        // Connect to SSE stream
        if (typeof EventSource !== 'undefined') {
            connectSystemMonitoring();
        } else {
            fallbackToPolling();
        }
        
        // Cleanup on page unload
        $(window).on('beforeunload', function() {
            if (eventSource) {
                eventSource.close();
            }
            if (reconnectTimeout) {
                clearTimeout(reconnectTimeout);
            }
        });
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
