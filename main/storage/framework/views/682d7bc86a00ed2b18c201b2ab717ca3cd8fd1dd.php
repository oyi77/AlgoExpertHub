<?php $__env->startSection('title'); ?>
    <?php echo e($title); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('element'); ?>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4><?php echo e($title); ?></h4>
                        <div class="btn-group">
                            <a href="<?php echo e(route('admin.copy-trading.traders.index', ['type' => 'user'])); ?>" 
                                class="btn btn-sm btn-outline-primary">Users</a>
                            <a href="<?php echo e(route('admin.copy-trading.traders.index', ['type' => 'admin'])); ?>" 
                                class="btn btn-sm btn-outline-primary">Admins</a>
                            <a href="<?php echo e(route('admin.copy-trading.traders.index')); ?>" 
                                class="btn btn-sm btn-outline-secondary">All</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if(isset($error)): ?>
                            <div class="alert alert-danger">
                                <?php echo e($error); ?>

                            </div>
                        <?php endif; ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Trader</th>
                                        <th>Type</th>
                                        <th>Win Rate</th>
                                        <th>Total PnL</th>
                                        <th>Followers</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $__empty_1 = true; $__currentLoopData = $traders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $trader): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                        <tr>
                                            <td><?php echo e($trader->name ?? ($trader->is_admin_owned ? 'Admin #' . ($trader->admin_id ?? 'N/A') : 'User #' . ($trader->user_id ?? 'N/A'))); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo e(($trader->type ?? ($trader->is_admin_owned ? 'admin' : 'user')) === 'admin' ? 'info' : 'success'); ?>">
                                                    <?php echo e(ucfirst($trader->type ?? ($trader->is_admin_owned ? 'admin' : 'user'))); ?>

                                                </span>
                                            </td>
                                            <td><?php echo e(number_format(($trader->stats['win_rate'] ?? $trader->stats['winRate'] ?? 0), 2)); ?>%</td>
                                            <td class="<?php echo e((($trader->stats['total_pnl'] ?? $trader->stats['totalPnL'] ?? 0) >= 0 ? 'text-success' : 'text-danger')); ?>">
                                                $<?php echo e(number_format(($trader->stats['total_pnl'] ?? $trader->stats['totalPnL'] ?? 0), 2)); ?>

                                            </td>
                                            <td><?php echo e($trader->stats['follower_count'] ?? $trader->stats['followerCount'] ?? 0); ?></td>
                                            <td>
                                                <?php if(isset($trader->is_enabled) && $trader->is_enabled): ?>
                                                    <span class="badge badge-success">Enabled</span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary">Disabled</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="<?php echo e(route('admin.copy-trading.traders.show', $trader->id)); ?>" 
                                                    class="btn btn-sm btn-info">View</a>
                                                <form action="<?php echo e(route('admin.copy-trading.traders.toggle', $trader->id)); ?>" 
                                                    method="POST" class="d-inline">
                                                    <?php echo csrf_field(); ?>
                                                    <button type="submit" class="btn btn-sm btn-<?php echo e((isset($trader->is_enabled) && $trader->is_enabled) ? 'warning' : 'success'); ?>">
                                                        <?php echo e((isset($trader->is_enabled) && $trader->is_enabled) ? 'Disable' : 'Enable'); ?>

                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No traders found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if(method_exists($traders, 'links')): ?>
                            <?php echo e($traders->links()); ?>

                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('backend.layout.master', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home1/algotrad/public_html/main/addons/copy-trading-addon/resources/views/backend/traders/index.blade.php ENDPATH**/ ?>