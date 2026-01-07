<div class="row mb-4">
    <!-- CPU Metric Card -->
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card border" id="cpu-metric-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-muted mb-1">CPU Load</h6>
                        <h4 class="mb-0" id="cpu-load-1m-main">0.00</h4>
                        <small class="text-muted">
                            1m: <span id="cpu-load-1m">0.00</span> | 
                            5m: <span id="cpu-load-5m">0.00</span> | 
                            15m: <span id="cpu-load-15m">0.00</span>
                        </small>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-microchip fa-2x text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Memory Metric Card -->
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card border" id="memory-metric-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-muted mb-1">Memory Usage</h6>
                        <h4 class="mb-0" id="memory-usage">0</h4>
                        <small class="text-muted">MB (Peak: <span id="memory-peak">0</span> MB)</small>
                        <div class="progress mt-2" style="height: 5px;">
                            <div class="progress-bar" id="memory-progress" role="progressbar" style="width: 0%"></div>
                        </div>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-memory fa-2x text-info"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Disk Metric Card -->
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card border" id="disk-metric-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-muted mb-1">Disk Usage</h6>
                        <h4 class="mb-0" id="disk-usage">0.0</h4>
                        <small class="text-muted">%</small>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-hdd fa-2x text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Database Metric Card -->
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card border" id="database-metric-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-muted mb-1">Database</h6>
                        <h4 class="mb-0" id="db-connections">0</h4>
                        <small class="text-muted">Connections | Slow: <span id="db-slow-queries">0</span></small>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-database fa-2x text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <!-- Cache Metric Card -->
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card border" id="cache-metric-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-muted mb-1">Cache Hit Rate</h6>
                        <h4 class="mb-0" id="cache-hit-rate">0.0</h4>
                        <small class="text-muted">%</small>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-bolt fa-2x text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

