<?php $__env->startSection('element'); ?>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header site-card-header justify-content-between flex-wrap gap-2">
                    <div class="card-header-left">
                        <h4 class="card-title"><?php echo e(__('AI Configuration')); ?></h4>
                    </div>
                    <div class="card-header-right">
                        <a href="<?php echo e(route('admin.ai-configuration.create')); ?>" class="btn btn-sm btn-primary">
                            <i class="fa fa-plus"></i> <?php echo e(__('Add AI Provider')); ?>

                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <?php if(session('success')): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo e(session('success')); ?>

                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if(session('error')): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo e(session('error')); ?>

                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th><?php echo e(__('Provider')); ?></th>
                                    <th><?php echo e(__('Name')); ?></th>
                                    <th><?php echo e(__('Model')); ?></th>
                                    <th><?php echo e(__('Priority')); ?></th>
                                    <th><?php echo e(__('Status')); ?></th>
                                    <th class="text-end"><?php echo e(__('Actions')); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $configurations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $config): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-info"><?php echo e(ucfirst($config->provider)); ?></span>
                                        </td>
                                        <td><?php echo e($config->name); ?></td>
                                        <td><?php echo e($config->model ?? 'N/A'); ?></td>
                                        <td><?php echo e($config->priority); ?></td>
                                        <td>
                                            <?php if($config->enabled): ?>
                                                <span class="badge bg-success"><?php echo e(__('Enabled')); ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><?php echo e(__('Disabled')); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-info test-connection-btn" 
                                                        data-id="<?php echo e($config->id); ?>" 
                                                        data-name="<?php echo e($config->name); ?>">
                                                    <i class="fa fa-plug"></i> <?php echo e(__('Test')); ?>

                                                </button>
                                                <a href="<?php echo e(route('admin.ai-configuration.edit', $config->id)); ?>" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="fa fa-edit"></i> <?php echo e(__('Edit')); ?>

                                                </a>
                                                <form action="<?php echo e(route('admin.ai-configuration.destroy', $config->id)); ?>" 
                                                      method="POST" 
                                                      class="d-inline"
                                                      onsubmit="return confirm('<?php echo e(__('Are you sure you want to delete this configuration?')); ?>');">
                                                    <?php echo csrf_field(); ?>
                                                    <?php echo method_field('DELETE'); ?>
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fa fa-trash"></i> <?php echo e(__('Delete')); ?>

                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">
                                            <p class="text-muted mb-0"><?php echo e(__('No AI configurations found.')); ?></p>
                                            <a href="<?php echo e(route('admin.ai-configuration.create')); ?>" class="btn btn-sm btn-primary mt-2">
                                                <i class="fa fa-plus"></i> <?php echo e(__('Create First Configuration')); ?>

                                            </a>
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.test-connection-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const name = this.getAttribute('data-name');
                    const originalText = this.innerHTML;
                    
                    this.disabled = true;
                    this.innerHTML = '<i class="fa fa-spinner fa-spin"></i> <?php echo e(__('Testing...')); ?>';
                    
                    fetch(`<?php echo e(url('admin/ai-configuration')); ?>/${id}/test-connection`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('<?php echo e(__('Connection successful!')); ?>');
                        } else {
                            alert('<?php echo e(__('Connection failed:')); ?> ' + (data.message || '<?php echo e(__('Unknown error')); ?>'));
                        }
                    })
                    .catch(error => {
                        alert('<?php echo e(__('Error testing connection:')); ?> ' + error.message);
                    })
                    .finally(() => {
                        this.disabled = false;
                        this.innerHTML = originalText;
                    });
                });
            });
        });
    </script>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('backend.layout.master', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home1/algotrad/public_html/main/addons/multi-channel-signal-addon/resources/views/backend/ai-configuration/index.blade.php ENDPATH**/ ?>