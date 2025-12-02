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
                    </div>
                    <div class="card-body">
                        <?php if(isset($error)): ?>
                            <div class="alert alert-danger">
                                <?php echo e($error); ?>

                            </div>
                        <?php endif; ?>
                        
                        <?php if(!$setting): ?>
                            <div class="alert alert-warning">
                                Unable to load settings. Please ensure the trading execution engine is enabled.
                            </div>
                        <?php else: ?>
                        <form action="<?php echo e(route('admin.copy-trading.settings.update')); ?>" method="POST">
                            <?php echo csrf_field(); ?>
                            
                            <div class="form-group">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_enabled" id="is_enabled" 
                                        value="1" <?php echo e(($setting && $setting->is_enabled) ? 'checked' : ''); ?>>
                                    <label class="form-check-label" for="is_enabled">
                                        Enable Copy Trading
                                    </label>
                                </div>
                                <small class="form-text text-muted">
                                    When enabled, other users can copy your trades
                                </small>
                            </div>

                            <div class="form-group">
                                <label for="min_followers_balance">Minimum Follower Balance</label>
                                <input type="number" class="form-control" name="min_followers_balance" 
                                    id="min_followers_balance" value="<?php echo e($setting ? ($setting->min_followers_balance ?? '') : ''); ?>" 
                                    step="0.01" min="0">
                                <small class="form-text text-muted">
                                    Minimum balance required for users to follow you (optional)
                                </small>
                            </div>

                            <div class="form-group">
                                <label for="max_copiers">Maximum Followers</label>
                                <input type="number" class="form-control" name="max_copiers" 
                                    id="max_copiers" value="<?php echo e($setting ? ($setting->max_copiers ?? '') : ''); ?>" min="1">
                                <small class="form-text text-muted">
                                    Maximum number of users who can copy your trades (optional)
                                </small>
                            </div>

                            <div class="form-group">
                                <label for="risk_multiplier_default">Default Risk Multiplier</label>
                                <input type="number" class="form-control" name="risk_multiplier_default" 
                                    id="risk_multiplier_default" value="<?php echo e($setting ? ($setting->risk_multiplier_default ?? 1.0) : 1.0); ?>" 
                                    step="0.1" min="0.1" max="10">
                                <small class="form-text text-muted">
                                    Default risk multiplier for followers using Easy Copy mode (0.1 to 10.0)
                                </small>
                            </div>

                            <div class="form-group">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="allow_manual_trades" 
                                        id="allow_manual_trades" value="1" 
                                        <?php echo e(($setting && $setting->allow_manual_trades) ? 'checked' : ''); ?>>
                                    <label class="form-check-label" for="allow_manual_trades">
                                        Allow Copying Manual Trades
                                    </label>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="allow_auto_trades" 
                                        id="allow_auto_trades" value="1" 
                                        <?php echo e(($setting && $setting->allow_auto_trades) ? 'checked' : ''); ?>>
                                    <label class="form-check-label" for="allow_auto_trades">
                                        Allow Copying Auto Trades (Signal-based)
                                    </label>
                                </div>
                            </div>

                            <div class="mt-4">
                                <h5>Statistics</h5>
                                <p>Active Followers: <strong><?php echo e($stats['follower_count'] ?? 0); ?></strong></p>
                                <p>Total Copied Trades: <strong><?php echo e($stats['total_copied_trades'] ?? 0); ?></strong></p>
                            </div>

                            <button type="submit" class="btn btn-primary mt-3">Save Settings</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('backend.layout.master', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home1/algotrad/public_html/main/addons/copy-trading-addon/resources/views/backend/settings.blade.php ENDPATH**/ ?>