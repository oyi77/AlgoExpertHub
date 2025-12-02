<?php $__env->startSection('title'); ?>
    <?php echo e($title); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('element'); ?>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title"><?php echo e($title); ?></h4>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="<?php echo e(route('admin.execution-analytics.index')); ?>" class="mb-4">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Select Connection <span class="text-danger">*</span></label>
                                        <select name="connection_id" id="connectionSelect" class="form-control" required onchange="this.form.submit()">
                                            <option value="">-- Select Connection --</option>
                                            <?php $__currentLoopData = $connections; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $conn): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($conn->id); ?>" <?php echo e(request('connection_id') == $conn->id ? 'selected' : ''); ?>>
                                                    <?php echo e($conn->name); ?> (<?php echo e(strtoupper($conn->type)); ?> - <?php echo e($conn->exchange_name); ?>)
                                                </option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </select>
                                    </div>
                                </div>
                                <?php if(request('connection_id')): ?>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Time Period</label>
                                        <select name="days" class="form-control" onchange="this.form.submit()">
                                            <option value="7" <?php echo e(request('days', 30) == 7 ? 'selected' : ''); ?>>Last 7 Days</option>
                                            <option value="30" <?php echo e(request('days', 30) == 30 ? 'selected' : ''); ?>>Last 30 Days</option>
                                            <option value="90" <?php echo e(request('days', 30) == 90 ? 'selected' : ''); ?>>Last 90 Days</option>
                                            <option value="365" <?php echo e(request('days', 30) == 365 ? 'selected' : ''); ?>>Last Year</option>
                                        </select>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </form>

                        <?php if(isset($connection) && isset($summary)): ?>
                            <div class="row mb-4">
                                <div class="col-md-3">
                                    <div class="card bg-primary text-white">
                                        <div class="card-body">
                                            <h5>Total Trades</h5>
                                            <h3><?php echo e($summary['total_trades'] ?? 0); ?></h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-success text-white">
                                        <div class="card-body">
                                            <h5>Win Rate</h5>
                                            <h3><?php echo e(number_format($summary['win_rate'] ?? 0, 2)); ?>%</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-info text-white">
                                        <div class="card-body">
                                            <h5>Total PnL</h5>
                                            <h3><?php echo e(number_format($summary['total_pnl'] ?? 0, 2)); ?></h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-warning text-white">
                                        <div class="card-body">
                                            <h5>Profit Factor</h5>
                                            <h3><?php echo e(number_format($summary['profit_factor'] ?? 0, 2)); ?></h3>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php if(isset($recent_positions) && $recent_positions->count() > 0): ?>
                                <div class="row mt-4">
                                    <div class="col-12">
                                        <h5>Recent Closed Positions</h5>
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Symbol</th>
                                                        <th>Direction</th>
                                                        <th>Entry Price</th>
                                                        <th>Close Price</th>
                                                        <th>PnL</th>
                                                        <th>Closed At</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php $__currentLoopData = $recent_positions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $position): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <tr>
                                                            <td><?php echo e($position->symbol); ?></td>
                                                            <td><?php echo e(strtoupper($position->direction)); ?></td>
                                                            <td><?php echo e(number_format($position->entry_price, 4)); ?></td>
                                                            <td><?php echo e($position->current_price ? number_format($position->current_price, 4) : 'N/A'); ?></td>
                                                            <td class="<?php echo e($position->pnl >= 0 ? 'text-success' : 'text-danger'); ?>">
                                                                <?php echo e(number_format($position->pnl, 2)); ?> (<?php echo e(number_format($position->pnl_percentage, 2)); ?>%)
                                                            </td>
                                                            <td><?php echo e($position->closed_at ? $position->closed_at->format('Y-m-d H:i') : 'N/A'); ?></td>
                                                        </tr>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php elseif($connections->count() > 0): ?>
                            <div class="alert alert-info">
                                <i class="fa fa-info-circle"></i> Please select a connection from the dropdown above to view analytics.
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="fa fa-exclamation-triangle"></i> No connections found. <a href="<?php echo e(route('admin.execution-connections.create')); ?>">Create a connection</a> to view analytics.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('backend.layout.master', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home1/algotrad/public_html/main/addons/trading-execution-engine-addon/resources/views/backend/analytics/index.blade.php ENDPATH**/ ?>