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
                                        <th>Reason</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $__empty_1 = true; $__currentLoopData = $positions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $position): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                        <tr>
                                            <td><?php echo e($position->symbol); ?></td>
                                            <td><?php echo e(strtoupper($position->direction)); ?></td>
                                            <td><?php echo e($position->entry_price); ?></td>
                                            <td><?php echo e($position->current_price ?? 'N/A'); ?></td>
                                            <td class="<?php echo e($position->pnl >= 0 ? 'text-success' : 'text-danger'); ?>">
                                                <?php echo e($position->pnl); ?> (<?php echo e($position->pnl_percentage); ?>%)
                                            </td>
                                            <td><?php echo e($position->closed_at ? $position->closed_at->format('Y-m-d H:i') : 'N/A'); ?></td>
                                            <td><?php echo e($position->closed_reason ?? 'N/A'); ?></td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No closed positions</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php echo e($positions->links()); ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('backend.layout.master', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home1/algotrad/public_html/main/addons/trading-execution-engine-addon/resources/views/backend/positions/closed.blade.php ENDPATH**/ ?>