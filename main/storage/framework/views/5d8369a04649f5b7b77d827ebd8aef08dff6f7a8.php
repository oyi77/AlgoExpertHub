<?php $__env->startSection('element'); ?>
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header site-card-header justify-content-between">
                    <h5 class="mb-0"><?php echo e(__('Manage Modules: :addon', ['addon' => $addon['title']])); ?></h5>
                    <a href="<?php echo e(route('admin.addons.index')); ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> <?php echo e(__('Back to Addons')); ?>

                    </a>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="mb-2">
                                <strong><?php echo e(__('Addon:')); ?></strong> <?php echo e($addon['title']); ?>

                            </div>
                            <div class="mb-2">
                                <strong><?php echo e(__('Version:')); ?></strong> <?php echo e($addon['version'] ?? __('N/A')); ?>

                            </div>
                            <div class="mb-2">
                                <strong><?php echo e(__('Namespace:')); ?></strong> <code><?php echo e($addon['namespace'] ?? __('N/A')); ?></code>
                            </div>
                            <div class="mb-2">
                                <strong><?php echo e(__('Status:')); ?></strong>
                                <span class="badge <?php echo e($addon['status'] === 'active' ? 'badge-success' : 'badge-secondary'); ?>">
                                    <?php echo e(ucfirst($addon['status'])); ?>

                                </span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <?php if(!empty($addon['description'])): ?>
                                <div>
                                    <strong><?php echo e(__('Description:')); ?></strong>
                                    <p class="text-muted mb-0"><?php echo e($addon['description']); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header site-card-header">
                    <h5 class="mb-0"><?php echo e(__('Modules')); ?></h5>
                </div>
                <div class="card-body">
                    <?php if(count($addon['modules'])): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th><?php echo e(__('Module Name')); ?></th>
                                        <th><?php echo e(__('Description')); ?></th>
                                        <th><?php echo e(__('Targets')); ?></th>
                                        <th><?php echo e(__('Status')); ?></th>
                                        <th><?php echo e(__('Action')); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $__currentLoopData = $addon['modules']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $module): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo e($module['name']); ?></strong>
                                                <?php if(!empty($module['key'])): ?>
                                                    <div class="small text-muted">
                                                        <code><?php echo e($module['key']); ?></code>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if(!empty($module['description'])): ?>
                                                    <p class="mb-0 text-muted"><?php echo e($module['description']); ?></p>
                                                <?php else: ?>
                                                    <span class="text-muted"><?php echo e(__('No description provided')); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if(!empty($module['targets']) && is_array($module['targets'])): ?>
                                                    <div class="d-flex flex-wrap">
                                                        <?php $__currentLoopData = $module['targets']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $target): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                            <span class="badge badge-secondary mr-1 mb-1"><?php echo e($target); ?></span>
                                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted"><?php echo e(__('N/A')); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo e($module['enabled'] ? 'badge-success' : 'badge-secondary'); ?>">
                                                    <?php echo e($module['enabled'] ? __('Enabled') : __('Disabled')); ?>

                                                </span>
                                            </td>
                                            <td>
                                                <form action="<?php echo e(route('admin.addons.modules.update', [$addon['slug'], $module['key']])); ?>" method="POST" class="d-inline">
                                                    <?php echo csrf_field(); ?>
                                                    <input type="hidden" name="action" value="<?php echo e($module['enabled'] ? 'disable' : 'enable'); ?>">
                                                    <button type="submit" class="btn btn-sm <?php echo e($module['enabled'] ? 'btn-outline-danger' : 'btn-outline-success'); ?>">
                                                        <i class="fas <?php echo e($module['enabled'] ? 'fa-times' : 'fa-check'); ?>"></i>
                                                        <?php echo e($module['enabled'] ? __('Disable') : __('Enable')); ?>

                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <p class="text-muted mb-0"><?php echo e(__('No modules declared for this addon.')); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('backend.layout.master', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home1/algotrad/public_html/main/resources/views/backend/addons/modules.blade.php ENDPATH**/ ?>