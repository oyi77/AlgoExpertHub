<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Worker Status</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Worker Type</th>
                                <th>Status</th>
                                <th>Active Workers</th>
                                <th>Metrics</th>
                                <th>Health</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Queue Workers -->
                            <tr>
                                <td>
                                    <strong>Queue Workers</strong>
                                    <br><small class="text-muted">Laravel Queue</small>
                                </td>
                                <td>
                                    <span class="badge badge-success" id="queue-status">healthy</span>
                                </td>
                                <td id="queue-workers-count">0</td>
                                <td>
                                    <small>
                                        Total: <span id="queue-jobs-total">0</span><br>
                                        Pending: <span id="queue-jobs-pending">0</span><br>
                                        Failed: <span id="queue-jobs-failed">0</span>
                                    </small>
                                </td>
                                <td>
                                    <i class="fas fa-heartbeat text-success"></i>
                                </td>
                            </tr>

                            <!-- Bot Workers -->
                            <tr id="bot-workers-row">
                                <td>
                                    <strong>Bot Workers</strong>
                                    <br><small class="text-muted">Trading Bots</small>
                                </td>
                                <td>
                                    <span class="badge badge-secondary" id="bot-status">stopped</span>
                                </td>
                                <td id="bot-workers-count">0</td>
                                <td>
                                    <small>
                                        Active: <span id="bot-active">0</span><br>
                                        Total: <span id="bot-total">0</span>
                                    </small>
                                </td>
                                <td>
                                    <i class="fas fa-robot text-info"></i>
                                </td>
                            </tr>

                            <!-- Octane Workers -->
                            <tr id="octane-workers-row">
                                <td>
                                    <strong>Octane Workers</strong>
                                    <br><small class="text-muted">Laravel Octane</small>
                                </td>
                                <td>
                                    <span class="badge badge-secondary" id="octane-status">not_running</span>
                                </td>
                                <td id="octane-workers-count">0</td>
                                <td>
                                    <small>
                                        Workers: <span id="octane-workers">0</span><br>
                                        Memory: <span id="octane-memory">0</span> MB
                                    </small>
                                </td>
                                <td>
                                    <i class="fas fa-server text-primary"></i>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

