<?php $__env->startSection('element'); ?>
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header site-card-header justify-content-between">
                    <h5 class="mb-0"><?php echo e(__('Upload Addon Package')); ?></h5>
                </div>
                <div class="card-body">
                    <form action="<?php echo e(route('admin.addons.upload')); ?>" method="POST" enctype="multipart/form-data" class="row gy-3 align-items-end">
                        <?php echo csrf_field(); ?>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><?php echo e(__('Addon Package (ZIP)')); ?></label>
                            <input type="file" name="package" class="form-control" required accept=".zip">
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <button type="submit" class="btn btn-primary w-100"><?php echo e(__('Upload')); ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header site-card-header justify-content-between">
                    <h5 class="mb-0"><?php echo e(__('Installed Addons')); ?></h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th><?php echo e(__('Addon')); ?></th>
                                    <th><?php echo e(__('Description')); ?></th>
                                    <th><?php echo e(__('Version')); ?></th>
                                    <th><?php echo e(__('Namespace')); ?></th>
                                    <th><?php echo e(__('Status')); ?></th>
                                    <th><?php echo e(__('Modules')); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $addons; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $addon): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo e($addon['title']); ?></strong>
                                            <div class="small text-muted"><?php echo e($addon['slug']); ?></div>
                                        </td>
                                        <td><?php echo e($addon['description'] ?? __('No description provided')); ?></td>
                                        <td><?php echo e($addon['version'] ?? __('N/A')); ?></td>
                                        <td><code><?php echo e($addon['namespace'] ?? __('N/A')); ?></code></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="badge <?php echo e($addon['status'] === 'active' ? 'badge-success' : 'badge-secondary'); ?> mr-2">
                                                    <?php echo e(ucfirst($addon['status'])); ?>

                                                </span>
                                                <form action="<?php echo e(route('admin.addons.status', $addon['slug'])); ?>" method="POST" class="mb-0">
                                                    <?php echo csrf_field(); ?>
                                                    <input type="hidden" name="action" value="<?php echo e($addon['status'] === 'active' ? 'disable' : 'enable'); ?>">
                                                    <button type="submit" class="btn btn-sm <?php echo e($addon['status'] === 'active' ? 'btn-outline-danger' : 'btn-outline-success'); ?>">
                                                        <?php echo e($addon['status'] === 'active' ? __('Disable') : __('Enable')); ?>

                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if(count($addon['modules'])): ?>
                                                <div class="d-flex align-items-center">
                                                    <span class="badge badge-info mr-2"><?php echo e(count($addon['modules'])); ?> <?php echo e(__('Modules')); ?></span>
                                                    <a href="<?php echo e(route('admin.addons.modules', $addon['slug'])); ?>" class="btn btn-sm btn-outline-primary">
                                                        <?php echo e(__('Manage Modules')); ?>

                                                    </a>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted"><?php echo e(__('No modules declared')); ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <?php echo e(__('No addons detected. Upload a package to get started.')); ?>

                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('backend.layout.master', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home1/algotrad/public_html/main/resources/views/backend/addons/index.blade.php ENDPATH**/ ?>