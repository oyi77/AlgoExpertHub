<!-- Sidebar start -->
<div class="quixnav">
    <div class="quixnav-scroll">
        <ul class="metismenu" id="menu">
            <li>
                <a href="<?php echo e(route('admin.home')); ?>" aria-expanded="false">
                    <i data-feather="home"></i>
                    <span class="nav-text"><?php echo e(__('Dashboard')); ?></span>
                </a>
            </li>

            <?php
                $adminUser = auth()->guard('admin')->user();
                $multiChannelAdminModuleEnabled = \App\Support\AddonRegistry::active('multi-channel-signal-addon') && \App\Support\AddonRegistry::moduleEnabled('multi-channel-signal-addon', 'admin_ui');
                $executionEngineAdminModuleEnabled = \App\Support\AddonRegistry::active('trading-execution-engine-addon') && \App\Support\AddonRegistry::moduleEnabled('trading-execution-engine-addon', 'admin_ui');
                // Copy trading requires trading execution engine to be active
                $copyTradingAdminModuleEnabled = \App\Support\AddonRegistry::active('copy-trading-addon') 
                    && \App\Support\AddonRegistry::moduleEnabled('copy-trading-addon', 'admin_ui')
                    && \App\Support\AddonRegistry::active('trading-execution-engine-addon');
                $tradingPresetAdminModuleEnabled = \App\Support\AddonRegistry::active('trading-preset-addon') && \App\Support\AddonRegistry::moduleEnabled('trading-preset-addon', 'admin_ui');
                $filterStrategyAdminModuleEnabled = \App\Support\AddonRegistry::active('filter-strategy-addon') && \App\Support\AddonRegistry::moduleEnabled('filter-strategy-addon', 'admin_ui');
                $aiTradingAdminModuleEnabled = \App\Support\AddonRegistry::active('ai-trading-addon') && \App\Support\AddonRegistry::moduleEnabled('ai-trading-addon', 'admin_ui');
            ?>

            <?php if($adminUser && $adminUser->can('manage-plan')): ?>
                <li><a href="<?php echo e(route('admin.plan.index')); ?>" aria-expanded="false"><i data-feather="box"></i><span
                            class="nav-text"><?php echo e(__('Manage Plans')); ?></span></a>
                </li>
            <?php endif; ?>

            <?php if($adminUser && $adminUser->can('signal')): ?>
                <li><a class="has-arrow" href="javascript:void(0)" aria-expanded="false"><i
                            data-feather="activity"></i><span class="nav-text"><?php echo e(__('Signal Tools')); ?></span></a>
                    <ul aria-expanded="false">


                        <li><a href="<?php echo e(route('admin.markets.index')); ?>"
                                aria-expanded="false"><?php echo e(__('Markets Type')); ?></a>
                        </li>

                        <li><a href="<?php echo e(route('admin.currency-pair.index')); ?>"
                                aria-expanded="false"><?php echo e(__('Currency Pair')); ?></a>
                        </li>

                        <li><a href="<?php echo e(route('admin.frames.index')); ?>"
                                aria-expanded="false"><?php echo e(__('Time Frames')); ?></a>
                        </li>

                        <li><a href="<?php echo e(route('admin.signals.index')); ?>" aria-expanded="false"><?php echo e(__('Signals')); ?></a>
                        </li>

                        <?php if($multiChannelAdminModuleEnabled): ?>
                            <li><a href="<?php echo e(route('admin.channel-signals.index')); ?>" aria-expanded="false"><?php echo e(__('Channel Signals Review')); ?></a>
                            </li>
                            <?php if($adminUser && ($adminUser->type === 'super' || $adminUser->hasRole('Super Admin'))): ?>
                                <li><a href="<?php echo e(route('admin.signal-sources.index')); ?>" aria-expanded="false"><?php echo e(__('Signal Sources')); ?></a>
                                </li>
                                <li><a href="<?php echo e(route('admin.channel-forwarding.index')); ?>" aria-expanded="false"><?php echo e(__('Channel Forwarding')); ?></a>
                                </li>
                                <li><a href="<?php echo e(route('admin.pattern-templates.index')); ?>" aria-expanded="false"><?php echo e(__('Pattern Templates')); ?></a>
                                </li>
                                <li><a href="<?php echo e(route('admin.ai-configuration.index')); ?>" aria-expanded="false"><?php echo e(__('AI Configuration')); ?></a>
                                </li>
                            <?php endif; ?>
                            <li><a href="<?php echo e(route('admin.signal-analytics.index')); ?>" aria-expanded="false"><?php echo e(__('Signal Analytics')); ?></a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </li>
            <?php endif; ?>

            <?php if($adminUser && $executionEngineAdminModuleEnabled): ?>
                <li><a class="has-arrow" href="javascript:void(0)" aria-expanded="false"><i
                            data-feather="trending-up"></i><span class="nav-text"><?php echo e(__('Trading Execution')); ?></span></a>
                    <ul aria-expanded="false">
                        <?php if(Route::has('admin.execution-connections.index')): ?>
                        <li><a href="<?php echo e(route('admin.execution-connections.index')); ?>" aria-expanded="false"><?php echo e(__('My Connections')); ?></a></li>
                        <?php endif; ?>
                        <?php if(Route::has('admin.execution-executions.index')): ?>
                        <li><a href="<?php echo e(route('admin.execution-executions.index')); ?>" aria-expanded="false"><?php echo e(__('Executions')); ?></a></li>
                        <?php endif; ?>
                        <?php if(Route::has('admin.execution-positions.index')): ?>
                        <li><a href="<?php echo e(route('admin.execution-positions.index')); ?>" aria-expanded="false"><?php echo e(__('Open Positions')); ?></a></li>
                        <?php endif; ?>
                        <?php if(Route::has('admin.execution-positions.closed')): ?>
                        <li><a href="<?php echo e(route('admin.execution-positions.closed')); ?>" aria-expanded="false"><?php echo e(__('Closed Positions')); ?></a></li>
                        <?php endif; ?>
                        <?php if(Route::has('admin.execution-analytics.index')): ?>
                        <li><a href="<?php echo e(route('admin.execution-analytics.index')); ?>" aria-expanded="false"><?php echo e(__('Analytics')); ?></a></li>
                        <?php endif; ?>
                    </ul>
                </li>
            <?php endif; ?>

            <?php if($adminUser && $tradingPresetAdminModuleEnabled): ?>
                <li><a href="<?php echo e(route('admin.trading-presets.index')); ?>" aria-expanded="false"><i
                            data-feather="settings"></i><span class="nav-text"><?php echo e(__('Trading Presets')); ?></span></a>
                </li>
            <?php endif; ?>

            <?php if($adminUser && $filterStrategyAdminModuleEnabled): ?>
                <?php if(Route::has('admin.filter-strategies.index')): ?>
                <li><a href="<?php echo e(route('admin.filter-strategies.index')); ?>" aria-expanded="false"><i
                            data-feather="filter"></i><span class="nav-text"><?php echo e(__('Filter Strategies')); ?></span></a>
                </li>
                <?php endif; ?>
            <?php endif; ?>

            <?php if($adminUser && $aiTradingAdminModuleEnabled): ?>
                <li><a class="has-arrow" href="javascript:void(0)" aria-expanded="false"><i
                            data-feather="cpu"></i><span class="nav-text"><?php echo e(__('AI Trading')); ?></span></a>
                    <ul aria-expanded="false">
                        <?php if(Route::has('admin.ai-model-profiles.index')): ?>
                        <li><a href="<?php echo e(route('admin.ai-model-profiles.index')); ?>" aria-expanded="false"><?php echo e(__('AI Model Profiles')); ?></a></li>
                        <?php endif; ?>
                        <?php if(Route::has('admin.ai-decision-logs.index')): ?>
                        <li><a href="<?php echo e(route('admin.ai-decision-logs.index')); ?>" aria-expanded="false"><?php echo e(__('Decision Logs')); ?></a></li>
                        <?php endif; ?>
                    </ul>
                </li>
            <?php endif; ?>

            <?php if($adminUser && $copyTradingAdminModuleEnabled): ?>
                <li><a class="has-arrow" href="javascript:void(0)" aria-expanded="false"><i
                            data-feather="copy"></i><span class="nav-text"><?php echo e(__('Copy Trading')); ?></span></a>
                    <ul aria-expanded="false">
                        <?php if(Route::has('admin.copy-trading.settings')): ?>
                        <li><a href="<?php echo e(route('admin.copy-trading.settings')); ?>" aria-expanded="false"><?php echo e(__('My Settings')); ?></a></li>
                        <?php endif; ?>
                        <?php if(Route::has('admin.copy-trading.traders.index')): ?>
                        <li><a href="<?php echo e(route('admin.copy-trading.traders.index')); ?>" aria-expanded="false"><?php echo e(__('Manage Traders')); ?></a></li>
                        <?php endif; ?>
                    </ul>
                </li>
            <?php endif; ?>

            <?php if($adminUser && $adminUser->can('manage-referral')): ?>
                <li><a href="<?php echo e(route('admin.refferal.index')); ?>" aria-expanded="false"><i
                            data-feather="link"></i><span class="nav-text"><?php echo e(__('Manage Affiliates')); ?></span></a>
                </li>
            <?php endif; ?>


            

            <?php if($adminUser && $adminUser->can('payments')): ?>
                <li><a class="has-arrow" href="javascript:void(0)" aria-expanded="false"><i data-feather="list"></i><span
                            class="nav-text"><?php echo e(__('Manage Payments')); ?></span></a>
                    <ul aria-expanded="false">

                        <li><a href="<?php echo e(route('admin.payments.index', 'online')); ?>"><?php echo e(__('Online payments')); ?></a>
                        </li>

                        <li><a href="<?php echo e(route('admin.payments.index', 'offline')); ?>"><?php echo e(__('Offline payments')); ?></a>
                        </li>

                    </ul>
                </li>
            <?php endif; ?>

            <?php if($adminUser && $adminUser->can('manage-deposit')): ?>
                <li><a class="has-arrow" href="javascript:void(0)" aria-expanded="false"><i
                            data-feather="credit-card"></i><span class="nav-text"><?php echo e(__('Manage Deposit')); ?></span></a>
                    <ul aria-expanded="false">

                        <li><a href="<?php echo e(route('admin.deposit', 'online')); ?>"><?php echo e(__('Online Deposit')); ?></a></li>
                        <li><a href="<?php echo e(route('admin.deposit', 'offline')); ?>"><?php echo e(__('Offline Deposit')); ?></a></li>
                    </ul>
                </li>
            <?php endif; ?>

            <?php if($adminUser && $adminUser->can('manage-withdraw')): ?>
                <li><a class="has-arrow" href="javascript:void(0)" aria-expanded="false"><i
                            data-feather="package"></i><span class="nav-text"><?php echo e(__('Manage Withdraw')); ?></span></a>
                    <ul aria-expanded="false">
                        <li><a href="<?php echo e(route('admin.withdraw.index')); ?>"><?php echo e(__('Withdraw Methods')); ?></a></li>
                        <li><a href="<?php echo e(route('admin.withdraw.filter')); ?>"><?php echo e(__('All Withdraw')); ?></a></li>
                        <li><a href="<?php echo e(route('admin.withdraw.filter', 'pending')); ?>"><?php echo e(__('Pending Withdraw')); ?>

                                <span class="noti-count"><?php echo e(Config::sidebarData()['pendingWithdraw']); ?></span></a></li>
                        <li><a
                                href="<?php echo e(route('admin.withdraw.filter', 'accepted')); ?>"><?php echo e(__('Accepted Withdraw')); ?></a>
                        </li>
                        <li><a
                                href="<?php echo e(route('admin.withdraw.filter', 'rejected')); ?>"><?php echo e(__('Rejected Withdraw')); ?></a>
                        </li>
                    </ul>
                </li>
            <?php endif; ?>

            <?php if($adminUser && $adminUser->can('manage-user')): ?>
                <li><a href="<?php echo e(route('admin.user.index')); ?>"><i data-feather="user"></i><span
                            class="nav-text"><?php echo e(__('Manage Users')); ?></span></a>
                </li>
            <?php endif; ?>

            <?php if($adminUser && ($adminUser->can('manage-setting') ||
                    $adminUser->can('manage-email') ||
                    $adminUser->can('manage-theme') ||
                    $adminUser->can('manage-gateway') ||
                    $adminUser->can('manage-addon'))): ?>
                <li class="nav-label"><?php echo e(__('Application Settings')); ?></li>
            <?php endif; ?>

            <?php if($adminUser && ($adminUser->type === 'super' || $adminUser->hasRole('Super Admin') || $adminUser->can('manage-addon'))): ?>
                <li>
                    <a href="<?php echo e(route('admin.addons.index')); ?>" aria-expanded="false">
                        <i data-feather="layers"></i>
                        <span class="nav-text"><?php echo e(__('Manage Addons')); ?></span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if($adminUser && $adminUser->can('manage-gateway')): ?>
                <li><a class="has-arrow" href="javascript:void(0)" aria-expanded="false"><i data-feather="tool"></i><span
                            class="nav-text"><?php echo e(__('Payment Gateways')); ?></span></a>
                    <ul aria-expanded="false">

                        <li><a href="<?php echo e(route('admin.payment.index')); ?>"><?php echo e(__('Online Gateway')); ?></a>
                        </li>
                        <li><a href="<?php echo e(route('admin.payment.offline')); ?>"><?php echo e(__('Offline Gateway')); ?></a>
                        </li>

                    </ul>
                </li>
            <?php endif; ?>


            <?php if($adminUser && $adminUser->can('manage-setting')): ?>
                <li><a href="<?php echo e(route('admin.general.index')); ?>" aria-expanded="false"><i
                            data-feather="settings"></i><span class="nav-text"><?php echo e(__('Manage Settings')); ?></span></a>
                </li>
            <?php endif; ?>

            <?php if($adminUser && $adminUser->can('manage-email')): ?>
                <li><a class="has-arrow" href="javascript:void(0)" aria-expanded="false"><i data-feather="mail"></i><span
                            class="nav-text"><?php echo e(__('Email Config')); ?></span></a>
                    <ul aria-expanded="false">

                        <li><a href="<?php echo e(route('admin.email.config')); ?>"><?php echo e(__('Email Configure')); ?></a></li>

                        <li><a href="<?php echo e(route('admin.email.templates')); ?>"><?php echo e(__('Email Templates')); ?></a></li>
                    </ul>
                </li>
            <?php endif; ?>

            <?php if($adminUser && $adminUser->can('manage-theme')): ?>
                <li><a href="<?php echo e(route('admin.manage.theme')); ?>" aria-expanded="false"><i
                            data-feather="layers"></i><span class="nav-text"><?php echo e(__('Manage Theme')); ?></span></a>
                </li>
            <?php endif; ?>

            <?php if($adminUser && ($adminUser->can('manage-frontend') ||
                    $adminUser->can('manage-language'))): ?>
                <li class="nav-label"><?php echo e(__('Theme Settings')); ?></li>
            <?php endif; ?>

            <?php if($adminUser && $adminUser->can('manage-frontend')): ?>
                <li><a href="<?php echo e(route('admin.frontend.pages')); ?>" aria-expanded="false"><i
                            data-feather="book-open"></i><span class="nav-text"><?php echo e(__('Manage Pages')); ?></span></a>
                </li>

                

                <li><a href="<?php echo e(route('admin.frontend.section.manage', 'banner')); ?>" aria-expanded="false"><i
                            data-feather="layout"></i><span class="nav-text"><?php echo e(__('Manage Frontend')); ?></span></a>
                </li>
            <?php endif; ?>

            <?php if($adminUser && $adminUser->can('manage-language')): ?>
                <li><a href="<?php echo e(route('admin.language.index')); ?>" aria-expanded="false"><i
                            data-feather="globe"></i><span class="nav-text"><?php echo e(__('Manage Language')); ?></span></a>
                </li>
            <?php endif; ?>

            <?php if($adminUser && ($adminUser->can('manage-role') ||
                    $adminUser->can('manage-admin'))): ?>
                <li class="nav-label"><?php echo e(__('Administration')); ?></li>
            <?php endif; ?>

            <?php if($adminUser && $adminUser->can('manage-role')): ?>
                <li>
                    <a href="<?php echo e(route('admin.roles.index')); ?>" aria-expanded="false">
                        <i data-feather="users"></i>
                        <span class="nav-text"><?php echo e(__('Manage Roles')); ?></span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if($adminUser && $adminUser->can('manage-admin')): ?>
                <li>
                    <a href="<?php echo e(route('admin.admins.index')); ?>" aria-expanded="false">
                        <i data-feather="user-check"></i>
                        <span class="nav-text"><?php echo e(__('Manage Admins')); ?></span>
                    </a>
                </li>
            <?php endif; ?>



            <li class="nav-label"><?php echo e(__('Others')); ?></li>
            <?php if($adminUser && $adminUser->can('manage-logs')): ?>
                <li>
                    <a href="<?php echo e(route('admin.transaction')); ?>" aria-expanded="false">
                        <i data-feather="file-text"></i>
                        <span class="nav-text"><?php echo e(__('Manage Logs')); ?></span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if($adminUser && $adminUser->can('manage-ticket')): ?>
                <li><a class="has-arrow" href="javascript:void(0)" aria-expanded="false"><i
                            data-feather="inbox"></i><span class="nav-text"><?php echo e(__('Support Ticket')); ?></span></a>
                    <ul aria-expanded="false">

                        <li><a href="<?php echo e(route('admin.ticket.index')); ?>"><?php echo e(__('All Tickets')); ?></a></li>

                        <li><a href="<?php echo e(route('admin.ticket.status', 'pending')); ?>"><?php echo e(__('Pending Ticket')); ?>

                                <?php if(Config::sidebarData()['pendingTicket'] > 0): ?>
                                    <span class="noti-count"><?php echo e(Config::sidebarData()['pendingTicket']); ?></span>
                                <?php endif; ?>
                            </a></li>

                        <li><a href="<?php echo e(route('admin.ticket.status', 'answered')); ?>"><?php echo e(__('Answered Ticket')); ?></a>
                        </li>

                        <li><a href="<?php echo e(route('admin.ticket.status', 'closed')); ?>"><?php echo e(__('Closed Ticket')); ?></a>
                        </li>


                    </ul>
                </li>
            <?php endif; ?>

            <?php if($adminUser && $adminUser->can('manage-subscriber')): ?>
                <li><a href="<?php echo e(route('admin.subscribers')); ?>" aria-expanded="false"><i
                            data-feather="at-sign"></i><span class="nav-text"><?php echo e(__('Subscribers')); ?></span></a>
                </li>
            <?php endif; ?>

            <li><a href="<?php echo e(route('admin.notifications')); ?>" aria-expanded="false"><i
                        data-feather="feather"></i><span class="nav-text"><?php echo e(__('All Notification')); ?></span></a>
            </li>

            <li><a href="<?php echo e(route('admin.general.cacheclear')); ?>" aria-expanded="false"><i
                        data-feather="feather"></i><span class="nav-text"><?php echo e(__('Clear Cache')); ?></span></a>
            </li>

            <li class="nav-label"><?php echo e(__('Current Version') .' - '. Config::APP_VERSION); ?></li>
        </ul>
    </div>
</div>
<!-- Sidebar end -->
<?php /**PATH /home1/algotrad/public_html/main/resources/views/backend/layout/sidebar.blade.php ENDPATH**/ ?>