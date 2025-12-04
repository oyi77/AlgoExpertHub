<!-- Sidebar start -->
<div class="quixnav">
    <div class="quixnav-scroll">
        <ul class="metismenu" id="menu">
            <li>
                <a href="{{ route('admin.home') }}" aria-expanded="false">
                    <i data-feather="home"></i>
                    <span class="nav-text">{{ __('Dashboard') }}</span>
                </a>
            </li>

            @php
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
                $aiConnectionAdminModuleEnabled = \App\Support\AddonRegistry::active('ai-connection-addon') && \App\Support\AddonRegistry::moduleEnabled('ai-connection-addon', 'admin_ui');
                $openRouterAdminModuleEnabled = \App\Support\AddonRegistry::active('openrouter-integration-addon') && \App\Support\AddonRegistry::moduleEnabled('openrouter-integration-addon', 'admin_ui');
                $srmAdminModuleEnabled = \App\Support\AddonRegistry::active('smart-risk-management-addon') && \App\Support\AddonRegistry::moduleEnabled('smart-risk-management-addon', 'admin_ui');
                
                // NEW: Trading Management Addon (Unified)
                $tradingManagementEnabled = \App\Support\AddonRegistry::active('trading-management-addon');
            @endphp

            @if ($adminUser && $adminUser->can('manage-plan'))
                <li><a href="{{ route('admin.plan.index') }}" aria-expanded="false"><i data-feather="box"></i><span
                            class="nav-text">{{ __('Manage Plans') }}</span></a>
                </li>
            @endif

            @if ($adminUser && $adminUser->can('signal'))
                {{-- Core Signal Management --}}
                <li><a class="has-arrow" href="javascript:void(0)" aria-expanded="false"><i
                            data-feather="activity"></i><span class="nav-text">{{ __('Signal Tools') }}</span></a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('admin.markets.index') }}"
                                aria-expanded="false">{{ __('Markets Type') }}</a>
                        </li>
                        <li><a href="{{ route('admin.currency-pair.index') }}"
                                aria-expanded="false">{{ __('Currency Pair') }}</a>
                        </li>
                        <li><a href="{{ route('admin.frames.index') }}"
                                aria-expanded="false">{{ __('Time Frames') }}</a>
                        </li>
                        <li><a href="{{ route('admin.signals.index') }}" aria-expanded="false">{{ __('Signals') }}</a>
                        </li>
                    </ul>
                </li>
            @endif

            {{-- Multi-Channel Signal Addon (Separate Section) --}}
            @if ($adminUser && $adminUser->can('signal') && $multiChannelAdminModuleEnabled)
                <li><a class="has-arrow" href="javascript:void(0)" aria-expanded="false"><i
                            data-feather="rss"></i><span class="nav-text">{{ __('Multi-Channel Signals') }}</span></a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('admin.channel-signals.index') }}" aria-expanded="false">{{ __('Channel Signals Review') }}</a>
                        </li>
                        <li><a href="{{ route('admin.signal-analytics.index') }}" aria-expanded="false">{{ __('Signal Analytics') }}</a>
                        </li>
                        @if ($adminUser && ($adminUser->type === 'super' || $adminUser->hasRole('Super Admin')))
                            <li class="nav-label">{{ __('Configuration') }}</li>
                            @if (Route::has('admin.multi-channel.global-config.index'))
                            <li><a href="{{ route('admin.multi-channel.global-config.index') }}" aria-expanded="false">{{ __('Global Settings') }}</a>
                            </li>
                            @endif
                            <li><a href="{{ route('admin.signal-sources.index') }}" aria-expanded="false">{{ __('Signal Sources') }}</a>
                            </li>
                            <li><a href="{{ route('admin.channel-forwarding.index') }}" aria-expanded="false">{{ __('Channel Forwarding') }}</a>
                            </li>
                            <li><a href="{{ route('admin.pattern-templates.index') }}" aria-expanded="false">{{ __('Pattern Templates') }}</a>
                            </li>
                        @endif
                    </ul>
                </li>
            @endif

            {{-- Trading Management Addon (Parent Menu) --}}
            @if ($adminUser && $tradingManagementEnabled)
                <li><a class="has-arrow" href="javascript:void(0)" aria-expanded="false"><i
                            data-feather="trending-up"></i><span class="nav-text">{{ __('Trading Management') }}</span></a>
                    <ul aria-expanded="false">
                        @if (Route::has('admin.trading-management.config.index'))
                        <li><a href="{{ route('admin.trading-management.config.index') }}" aria-expanded="false">{{ __('Trading Configuration') }}</a></li>
                        @endif
                        @if (Route::has('admin.trading-management.strategy.index'))
                        <li><a href="{{ route('admin.trading-management.strategy.index') }}" aria-expanded="false">{{ __('Strategy Management') }}</a></li>
                        @endif
                        @if (Route::has('admin.trading-management.operations.index'))
                        <li><a href="{{ route('admin.trading-management.operations.index') }}" aria-expanded="false">{{ __('Trading Operations') }}</a></li>
                        @endif
                        @if (Route::has('admin.trading-management.copy-trading.index'))
                        <li><a href="{{ route('admin.trading-management.copy-trading.index') }}" aria-expanded="false">{{ __('Copy Trading') }}</a></li>
                        @endif
                        @if (Route::has('admin.trading-management.test.index'))
                        <li><a href="{{ route('admin.trading-management.test.index') }}" aria-expanded="false">{{ __('Backtesting') }}</a></li>
                        @endif
                        @if (Route::has('admin.trading-management.trading-bots.index'))
                        <li><a href="{{ route('admin.trading-management.trading-bots.index') }}" aria-expanded="false">{{ __('Trading Bots') }}</a></li>
                        @endif
                    </ul>
                </li>
            @endif

            {{-- OLD: Keep for backward compatibility during migration (can be hidden if trading-management-addon active) --}}
            @if ($adminUser && $executionEngineAdminModuleEnabled && !$tradingManagementEnabled)
                <li><a class="has-arrow" href="javascript:void(0)" aria-expanded="false"><i
                            data-feather="trending-up"></i><span class="nav-text">{{ __('Trading Execution') }}</span></a>
                    <ul aria-expanded="false">
                        @if (Route::has('admin.execution-connections.index'))
                        <li><a href="{{ route('admin.execution-connections.index') }}" aria-expanded="false">{{ __('My Connections') }}</a></li>
                        @endif
                        @if (Route::has('admin.execution-executions.index'))
                        <li><a href="{{ route('admin.execution-executions.index') }}" aria-expanded="false">{{ __('Executions') }}</a></li>
                        @endif
                        @if (Route::has('admin.execution-positions.index'))
                        <li><a href="{{ route('admin.execution-positions.index') }}" aria-expanded="false">{{ __('Open Positions') }}</a></li>
                        @endif
                        @if (Route::has('admin.execution-positions.closed'))
                        <li><a href="{{ route('admin.execution-positions.closed') }}" aria-expanded="false">{{ __('Closed Positions') }}</a></li>
                        @endif
                        @if (Route::has('admin.execution-analytics.index'))
                        <li><a href="{{ route('admin.execution-analytics.index') }}" aria-expanded="false">{{ __('Analytics') }}</a></li>
                        @endif
                    </ul>
                </li>
            @endif

            @if ($adminUser && $tradingPresetAdminModuleEnabled && !$tradingManagementEnabled)
                <li><a href="{{ route('admin.trading-presets.index') }}" aria-expanded="false"><i
                            data-feather="settings"></i><span class="nav-text">{{ __('Trading Presets') }}</span></a>
                </li>
            @endif

            @if ($adminUser && $filterStrategyAdminModuleEnabled && !$tradingManagementEnabled)
                @if (Route::has('admin.filter-strategies.index'))
                <li><a href="{{ route('admin.filter-strategies.index') }}" aria-expanded="false"><i
                            data-feather="filter"></i><span class="nav-text">{{ __('Filter Strategies') }}</span></a>
                </li>
                @endif
            @endif

            @if ($adminUser && $aiTradingAdminModuleEnabled && !$tradingManagementEnabled)
                <li><a class="has-arrow" href="javascript:void(0)" aria-expanded="false"><i
                            data-feather="cpu"></i><span class="nav-text">{{ __('AI Trading') }}</span></a>
                    <ul aria-expanded="false">
                        @if (Route::has('admin.ai-model-profiles.index'))
                        <li><a href="{{ route('admin.ai-model-profiles.index') }}" aria-expanded="false">{{ __('AI Model Profiles') }}</a></li>
                        @endif
                        @if (Route::has('admin.ai-decision-logs.index'))
                        <li><a href="{{ route('admin.ai-decision-logs.index') }}" aria-expanded="false">{{ __('Decision Logs') }}</a></li>
                        @endif
                    </ul>
                </li>
            @endif

            @if ($adminUser && $aiConnectionAdminModuleEnabled)
                <li><a class="has-arrow" href="javascript:void(0)" aria-expanded="false"><i
                            data-feather="cpu"></i><span class="nav-text">{{ __('AI Manager') }}</span></a>
                    <ul aria-expanded="false">
                        @if (Route::has('admin.ai-connections.providers.index'))
                        <li><a href="{{ route('admin.ai-connections.providers.index') }}" aria-expanded="false">{{ __('AI Providers') }}</a></li>
                        @endif
                        @if (Route::has('admin.ai-connections.connections.index'))
                        <li><a href="{{ route('admin.ai-connections.connections.index') }}" aria-expanded="false">{{ __('AI Connections') }}</a></li>
                        @endif
                        @if (Route::has('admin.ai-connections.usage-analytics.index'))
                        <li><a href="{{ route('admin.ai-connections.usage-analytics.index') }}" aria-expanded="false">{{ __('Usage Analytics') }}</a></li>
                        @endif
                        @if ($openRouterAdminModuleEnabled && Route::has('admin.openrouter.models.index'))
                        <li><a href="{{ route('admin.openrouter.models.index') }}" aria-expanded="false">{{ __('Model Marketplace') }}</a></li>
                        @endif
                    </ul>
                </li>
            @endif

            {{-- OLD: Hidden when new Trading Management active --}}
            @if ($adminUser && $copyTradingAdminModuleEnabled && !$tradingManagementEnabled)
                <li><a class="has-arrow" href="javascript:void(0)" aria-expanded="false"><i
                            data-feather="copy"></i><span class="nav-text">{{ __('Copy Trading') }}</span></a>
                    <ul aria-expanded="false">
                        @if (Route::has('admin.copy-trading.settings'))
                        <li><a href="{{ route('admin.copy-trading.settings') }}" aria-expanded="false">{{ __('My Settings') }}</a></li>
                        @endif
                        @if (Route::has('admin.copy-trading.traders.index'))
                        <li><a href="{{ route('admin.copy-trading.traders.index') }}" aria-expanded="false">{{ __('Manage Traders') }}</a></li>
                        @endif
                    </ul>
                </li>
            @endif

            @if ($adminUser && $srmAdminModuleEnabled && !$tradingManagementEnabled)
                <li><a class="has-arrow" href="javascript:void(0)" aria-expanded="false"><i
                            data-feather="shield"></i><span class="nav-text">{{ __('Smart Risk Management') }}</span></a>
                    <ul aria-expanded="false">
                        @if (Route::has('admin.srm.signal-providers.index'))
                        <li><a href="{{ route('admin.srm.signal-providers.index') }}" aria-expanded="false">{{ __('Signal Providers') }}</a></li>
                        @endif
                        @if (Route::has('admin.srm.predictions.index'))
                        <li><a href="{{ route('admin.srm.predictions.index') }}" aria-expanded="false">{{ __('Predictions') }}</a></li>
                        @endif
                        @if (Route::has('admin.srm.models.index'))
                        <li><a href="{{ route('admin.srm.models.index') }}" aria-expanded="false">{{ __('ML Models') }}</a></li>
                        @endif
                        @if (Route::has('admin.srm.ab-tests.index'))
                        <li><a href="{{ route('admin.srm.ab-tests.index') }}" aria-expanded="false">{{ __('A/B Tests') }}</a></li>
                        @endif
                        @if (Route::has('admin.srm.settings.index'))
                        <li><a href="{{ route('admin.srm.settings.index') }}" aria-expanded="false">{{ __('Settings') }}</a></li>
                        @endif
                    </ul>
                </li>
            @endif

            @if ($adminUser && $adminUser->can('manage-referral'))
                <li><a href="{{ route('admin.refferal.index') }}" aria-expanded="false"><i
                            data-feather="link"></i><span class="nav-text">{{ __('Manage Affiliates') }}</span></a>
                </li>
            @endif


            

            @if ($adminUser && $adminUser->can('payments'))
                <li><a class="has-arrow" href="javascript:void(0)" aria-expanded="false"><i data-feather="list"></i><span
                            class="nav-text">{{ __('Manage Payments') }}</span></a>
                    <ul aria-expanded="false">

                        <li><a href="{{ route('admin.payments.index', 'online') }}">{{ __('Online payments') }}</a>
                        </li>

                        <li><a href="{{ route('admin.payments.index', 'offline') }}">{{ __('Offline payments') }}</a>
                        </li>

                    </ul>
                </li>
            @endif

            @if ($adminUser && $adminUser->can('manage-deposit'))
                <li><a class="has-arrow" href="javascript:void(0)" aria-expanded="false"><i
                            data-feather="credit-card"></i><span class="nav-text">{{ __('Manage Deposit') }}</span></a>
                    <ul aria-expanded="false">

                        <li><a href="{{ route('admin.deposit', 'online') }}">{{ __('Online Deposit') }}</a></li>
                        <li><a href="{{ route('admin.deposit', 'offline') }}">{{ __('Offline Deposit') }}</a></li>
                    </ul>
                </li>
            @endif

            @if ($adminUser && $adminUser->can('manage-withdraw'))
                <li><a class="has-arrow" href="javascript:void(0)" aria-expanded="false"><i
                            data-feather="package"></i><span class="nav-text">{{ __('Manage Withdraw') }}</span></a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('admin.withdraw.index') }}">{{ __('Withdraw Methods') }}</a></li>
                        <li><a href="{{ route('admin.withdraw.filter') }}">{{ __('All Withdraw') }}</a></li>
                        <li><a href="{{ route('admin.withdraw.filter', 'pending') }}">{{ __('Pending Withdraw') }}
                                <span class="noti-count">{{Config::sidebarData()['pendingWithdraw'] }}</span></a></li>
                        <li><a
                                href="{{ route('admin.withdraw.filter', 'accepted') }}">{{ __('Accepted Withdraw') }}</a>
                        </li>
                        <li><a
                                href="{{ route('admin.withdraw.filter', 'rejected') }}">{{ __('Rejected Withdraw') }}</a>
                        </li>
                    </ul>
                </li>
            @endif

            @if ($adminUser && $adminUser->can('manage-user'))
                <li><a href="{{ route('admin.user.index') }}"><i data-feather="user"></i><span
                            class="nav-text">{{ __('Manage Users') }}</span></a>
                </li>
            @endif

            @if ($adminUser && ($adminUser->can('manage-setting') ||
                    $adminUser->can('manage-email') ||
                    $adminUser->can('manage-theme') ||
                    $adminUser->can('manage-gateway') ||
                    $adminUser->can('manage-addon')))
                <li class="nav-label">{{ __('Application Settings') }}</li>
            @endif

            @if ($adminUser && ($adminUser->type === 'super' || $adminUser->hasRole('Super Admin') || $adminUser->can('manage-addon')))
                <li>
                    <a href="{{ route('admin.addons.index') }}" aria-expanded="false">
                        <i data-feather="layers"></i>
                        <span class="nav-text">{{ __('Manage Addons') }}</span>
                    </a>
                </li>
            @endif

            @if ($adminUser && $adminUser->can('manage-gateway'))
                <li><a class="has-arrow" href="javascript:void(0)" aria-expanded="false"><i data-feather="tool"></i><span
                            class="nav-text">{{ __('Payment Gateways') }}</span></a>
                    <ul aria-expanded="false">

                        <li><a href="{{ route('admin.payment.index') }}">{{ __('Online Gateway') }}</a>
                        </li>
                        <li><a href="{{ route('admin.payment.offline') }}">{{ __('Offline Gateway') }}</a>
                        </li>

                    </ul>
                </li>
            @endif

            @if ($adminUser && $adminUser->can('manage-email'))
                <li><a class="has-arrow" href="javascript:void(0)" aria-expanded="false"><i data-feather="mail"></i><span
                            class="nav-text">{{ __('Email Config') }}</span></a>
                    <ul aria-expanded="false">

                        <li><a href="{{ route('admin.email.config') }}">{{ __('Email Configure') }}</a></li>

                        <li><a href="{{ route('admin.email.templates') }}">{{ __('Email Templates') }}</a></li>
                    </ul>
                </li>
            @endif


            @if ($adminUser && $adminUser->can('manage-setting'))
                <li><a href="{{ route('admin.general.index') }}" aria-expanded="false"><i
                            data-feather="settings"></i><span class="nav-text">{{ __('Manage Settings') }}</span></a>
                </li>
            @endif

            @if ($adminUser && ($adminUser->can('manage-frontend') ||
                    $adminUser->can('manage-language')))
                <li class="nav-label">{{ __('Theme Settings') }}</li>
            @endif

            @if ($adminUser && $adminUser->can('manage-theme'))
                <li><a href="{{ route('admin.manage.theme') }}" aria-expanded="false"><i
                            data-feather="layers"></i><span class="nav-text">{{ __('Manage Theme') }}</span></a>
                </li>
            @endif

            @if ($adminUser && $adminUser->can('manage-frontend'))
                <li><a href="{{ route('admin.frontend.pages') }}" aria-expanded="false"><i
                            data-feather="book-open"></i><span class="nav-text">{{ __('Manage Pages') }}</span></a>
                </li>

                

                <li><a href="{{ route('admin.frontend.section.manage', 'banner') }}" aria-expanded="false"><i
                            data-feather="layout"></i><span class="nav-text">{{ __('Manage Frontend') }}</span></a>
                </li>
            @endif

            @if ($adminUser && $adminUser->can('manage-language'))
                <li><a href="{{ route('admin.language.index') }}" aria-expanded="false"><i
                            data-feather="globe"></i><span class="nav-text">{{ __('Manage Language') }}</span></a>
                </li>
            @endif

            @if ($adminUser && ($adminUser->can('manage-role') ||
                    $adminUser->can('manage-admin')))
                <li class="nav-label">{{ __('Administration') }}</li>
            @endif

            @if ($adminUser && $adminUser->can('manage-role'))
                <li>
                    <a href="{{ route('admin.roles.index') }}" aria-expanded="false">
                        <i data-feather="users"></i>
                        <span class="nav-text">{{ __('Manage Roles') }}</span>
                    </a>
                </li>
            @endif

            @if ($adminUser && $adminUser->can('manage-admin'))
                <li>
                    <a href="{{ route('admin.admins.index') }}" aria-expanded="false">
                        <i data-feather="user-check"></i>
                        <span class="nav-text">{{ __('Manage Admins') }}</span>
                    </a>
                </li>
            @endif



            <li class="nav-label">{{ __('Others') }}</li>
            @if ($adminUser && $adminUser->can('manage-logs'))
                <li>
                    <a href="{{ route('admin.transaction') }}" aria-expanded="false">
                        <i data-feather="file-text"></i>
                        <span class="nav-text">{{ __('Manage Logs') }}</span>
                    </a>
                </li>
            @endif

            @if ($adminUser && $adminUser->can('manage-ticket'))
                <li><a class="has-arrow" href="javascript:void(0)" aria-expanded="false"><i
                            data-feather="inbox"></i><span class="nav-text">{{ __('Support Ticket') }}</span></a>
                    <ul aria-expanded="false">

                        <li><a href="{{ route('admin.ticket.index') }}">{{ __('All Tickets') }}</a></li>

                        <li><a href="{{ route('admin.ticket.status', 'pending') }}">{{ __('Pending Ticket') }}
                                @if (Config::sidebarData()['pendingTicket'] > 0)
                                    <span class="noti-count">{{ Config::sidebarData()['pendingTicket'] }}</span>
                                @endif
                            </a></li>

                        <li><a href="{{ route('admin.ticket.status', 'answered') }}">{{ __('Answered Ticket') }}</a>
                        </li>

                        <li><a href="{{ route('admin.ticket.status', 'closed') }}">{{ __('Closed Ticket') }}</a>
                        </li>


                    </ul>
                </li>
            @endif

            @if ($adminUser && $adminUser->can('manage-subscriber'))
                <li><a href="{{ route('admin.subscribers') }}" aria-expanded="false"><i
                            data-feather="at-sign"></i><span class="nav-text">{{ __('Subscribers') }}</span></a>
                </li>
            @endif

            <li><a href="{{ route('admin.notifications') }}" aria-expanded="false"><i
                        data-feather="feather"></i><span class="nav-text">{{ __('All Notification') }}</span></a>
            </li>

            <li class="nav-label">{{__('Current Version') .' - '. Config::APP_VERSION }}</li>
        </ul>
    </div>
</div>
<!-- Sidebar end -->
