<?php $__env->startSection('element'); ?>
    <div class="row">
        <!-- Statistics Cards -->
        <div class="col-12">
            <div class="row g-3">
                <div class="col-sm-6 col-lg-2">
                    <div class="sp_site_card text-center">
                        <h5 class="mb-1"><?php echo e(__('Total')); ?></h5>
                        <span class="fw-semibold fs-4"><?php echo e($stats['total']); ?></span>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-2">
                    <div class="sp_site_card text-center">
                        <h5 class="mb-1 text-info"><?php echo e(__('Default')); ?></h5>
                        <span class="fw-semibold fs-4 text-info"><?php echo e($stats['default']); ?></span>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-2">
                    <div class="sp_site_card text-center">
                        <h5 class="mb-1 text-primary"><?php echo e(__('Public')); ?></h5>
                        <span class="fw-semibold fs-4 text-primary"><?php echo e($stats['public']); ?></span>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-2">
                    <div class="sp_site_card text-center">
                        <h5 class="mb-1 text-secondary"><?php echo e(__('Private')); ?></h5>
                        <span class="fw-semibold fs-4 text-secondary"><?php echo e($stats['private']); ?></span>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-2">
                    <div class="sp_site_card text-center">
                        <h5 class="mb-1 text-success"><?php echo e(__('Enabled')); ?></h5>
                        <span class="fw-semibold fs-4 text-success"><?php echo e($stats['enabled']); ?></span>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-2">
                    <div class="sp_site_card text-center">
                        <h5 class="mb-1 text-danger"><?php echo e(__('Disabled')); ?></h5>
                        <span class="fw-semibold fs-4 text-danger"><?php echo e($stats['disabled']); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Presets Table -->
        <div class="col-12">
            <div class="card">
                <div class="card-header site-card-header align-items-center justify-content-between">
                    <div class="card-header-left">
                        <form action="<?php echo e(route('admin.trading-presets.index')); ?>" method="get">
                            <div class="row g-2">
                                <div class="col-auto">
                                    <div class="input-group">
                                        <input type="text" class="form-control form-control-sm" 
                                               placeholder="<?php echo e(__('Search presets...')); ?>" 
                                               name="search" 
                                               value="<?php echo e(request('search')); ?>">
                                        <div class="input-group-append">
                                            <button class="btn btn-sm btn-primary" type="submit">
                                                <i class="fa fa-search"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <select name="visibility" class="form-control form-control-sm" onchange="this.form.submit()">
                                        <option value=""><?php echo e(__('All Visibility')); ?></option>
                                        <option value="PRIVATE" <?php echo e(request('visibility') == 'PRIVATE' ? 'selected' : ''); ?>><?php echo e(__('Private')); ?></option>
                                        <option value="PUBLIC_MARKETPLACE" <?php echo e(request('visibility') == 'PUBLIC_MARKETPLACE' ? 'selected' : ''); ?>><?php echo e(__('Public')); ?></option>
                                    </select>
                                </div>
                                <div class="col-auto">
                                    <select name="enabled" class="form-control form-control-sm" onchange="this.form.submit()">
                                        <option value=""><?php echo e(__('All Status')); ?></option>
                                        <option value="1" <?php echo e(request('enabled') == '1' ? 'selected' : ''); ?>><?php echo e(__('Enabled')); ?></option>
                                        <option value="0" <?php echo e(request('enabled') == '0' ? 'selected' : ''); ?>><?php echo e(__('Disabled')); ?></option>
                                    </select>
                                </div>
                                <div class="col-auto">
                                    <select name="is_default" class="form-control form-control-sm" onchange="this.form.submit()">
                                        <option value=""><?php echo e(__('All Types')); ?></option>
                                        <option value="1" <?php echo e(request('is_default') == '1' ? 'selected' : ''); ?>><?php echo e(__('Default Templates')); ?></option>
                                        <option value="0" <?php echo e(request('is_default') == '0' ? 'selected' : ''); ?>><?php echo e(__('User Presets')); ?></option>
                                    </select>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="card-header-right">
                        <a href="<?php echo e(route('admin.trading-presets.create')); ?>" class="btn btn-primary btn-sm">
                            <i class="fa fa-plus mr-1"></i> <?php echo e(__('Create Preset')); ?>

                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table student-data-table m-t-20">
                            <thead>
                                <tr>
                                    <th><?php echo e(__('SL')); ?>.</th>
                                    <th><?php echo e(__('Name')); ?></th>
                                    <th><?php echo e(__('Description')); ?></th>
                                    <th><?php echo e(__('Symbol')); ?></th>
                                    <th><?php echo e(__('Visibility')); ?></th>
                                    <th><?php echo e(__('Type')); ?></th>
                                    <th><?php echo e(__('Status')); ?></th>
                                    <th><?php echo e(__('Creator')); ?></th>
                                    <th><?php echo e(__('Action')); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $presets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $preset): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr>
                                        <td><?php echo e($loop->iteration + ($presets->currentPage() - 1) * $presets->perPage()); ?></td>
                                        <td>
                                            <strong><?php echo e($preset->name); ?></strong>
                                            <?php if($preset->tags): ?>
                                                <div class="mt-1">
                                                    <?php $__currentLoopData = array_slice($preset->tags, 0, 3); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tag): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <span class="badge badge-secondary badge-sm"><?php echo e($tag); ?></span>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                    <?php if(count($preset->tags) > 3): ?>
                                                        <span class="badge badge-secondary badge-sm">+<?php echo e(count($preset->tags) - 3); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="text-muted">
                                                <?php echo e(Str::limit($preset->description ?? '-', 50)); ?>

                                            </span>
                                        </td>
                                        <td>
                                            <?php if($preset->symbol): ?>
                                                <span class="badge badge-info"><?php echo e($preset->symbol); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted"><?php echo e(__('All')); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($preset->visibility === 'PUBLIC_MARKETPLACE'): ?>
                                                <span class="badge badge-primary"><?php echo e(__('Public')); ?></span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary"><?php echo e(__('Private')); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($preset->is_default_template): ?>
                                                <span class="badge badge-info"><?php echo e(__('Default Template')); ?></span>
                                            <?php else: ?>
                                                <span class="badge badge-success"><?php echo e(__('User Preset')); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" 
                                                       name="enabled" 
                                                       <?php echo e($preset->enabled ? 'checked' : ''); ?>

                                                       class="custom-control-input preset-status" 
                                                       id="status<?php echo e($preset->id); ?>"
                                                       data-id="<?php echo e($preset->id); ?>"
                                                       data-route="<?php echo e(route('admin.trading-presets.toggle-status', $preset)); ?>">
                                                <label class="custom-control-label" for="status<?php echo e($preset->id); ?>"></label>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if($preset->creator): ?>
                                                <?php echo e($preset->creator->username ?? $preset->creator->email); ?>

                                            <?php else: ?>
                                                <span class="text-muted"><?php echo e(__('System')); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="<?php echo e(route('admin.trading-presets.show', $preset)); ?>" 
                                                   class="btn btn-outline-info btn-sm" 
                                                   title="<?php echo e(__('View')); ?>">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                <a href="<?php echo e(route('admin.trading-presets.edit', $preset)); ?>" 
                                                   class="btn btn-outline-primary btn-sm" 
                                                   title="<?php echo e(__('Edit')); ?>">
                                                    <i class="fa fa-pen"></i>
                                                </a>
                                                <form action="<?php echo e(route('admin.trading-presets.clone', $preset)); ?>" 
                                                      method="POST" 
                                                      class="d-inline"
                                                      onsubmit="return confirm('<?php echo e(__('Are you sure you want to clone this preset?')); ?>');">
                                                    <?php echo csrf_field(); ?>
                                                    <button type="submit" 
                                                            class="btn btn-outline-success btn-sm" 
                                                            title="<?php echo e(__('Clone')); ?>">
                                                        <i class="fa fa-copy"></i>
                                                    </button>
                                                </form>
                                                <?php if(!$preset->is_default_template): ?>
                                                    <form action="<?php echo e(route('admin.trading-presets.destroy', $preset)); ?>" 
                                                          method="POST" 
                                                          class="d-inline"
                                                          onsubmit="return confirm('<?php echo e(__('Are you sure you want to delete this preset?')); ?>');">
                                                        <?php echo csrf_field(); ?>
                                                        <?php echo method_field('DELETE'); ?>
                                                        <button type="submit" 
                                                                class="btn btn-outline-danger btn-sm" 
                                                                title="<?php echo e(__('Delete')); ?>">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr>
                                        <td class="text-center" colspan="100%">
                                            <?php echo e(__('No Presets Found')); ?>

                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <?php if($presets->hasPages()): ?>
                    <div class="card-footer">
                        <?php echo e($presets->links()); ?>

                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('script'); ?>
    <script>
        'use strict'

        $(function() {
            $('.preset-status').on('change', function() {
                let id = $(this).data('id');
                let route = $(this).data('route');

                $.ajax({
                    url: route,
                    method: "POST",
                    data: {
                        _token: "<?php echo e(csrf_token()); ?>"
                    },
                    success: function(response) {
                        <?php echo $__env->make('backend.layout.ajax_alert', [
                            'message' => 'Successfully changed preset status',
                            'message_error' => '',
                        ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                    },
                    error: function(xhr) {
                        <?php echo $__env->make('backend.layout.ajax_alert', [
                            'message' => '',
                            'message_error' => 'Failed to change preset status',
                        ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                        // Revert checkbox
                        $('.preset-status[data-id="' + id + '"]').prop('checked', !$('.preset-status[data-id="' + id + '"]').prop('checked'));
                    }
                })
            })
        })
    </script>
<?php $__env->stopPush(); ?>


<?php echo $__env->make('backend.layout.master', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home1/algotrad/public_html/main/addons/trading-preset-addon/resources/views/backend/presets/index.blade.php ENDPATH**/ ?>