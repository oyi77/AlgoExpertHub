@extends('backend.layout.master')

@section('element')
    <div class="row">
        <div class="col-md-12">


            <div class="card">
                <div class="card-body">
                    <ul class="nav nav-tabs page-link-list border-0" role="tablist">
                        <li>
                            <a class="{{ request()->query('notifications') || request()->query->count() == 0 ? 'active' : '' }}"
                                data-toggle="tab" href="#general">
                                <span class="text-uppercase">
                                    <i class="las la-home"></i>
                                    {{ __('All Notifications') }}
                                </span>
                            </a>
                        </li>

                        <li>
                            <a class="{{ request()->query('depositNotifications') ? 'active' : '' }}" data-toggle="tab"
                                href="#deposit">
                                <span class="text-uppercase">
                                    <i class="las la-home"></i>
                                    {{ __('Deposit Notifications') }}
                                </span>
                            </a>
                        </li>
                        <li>
                            <a class="{{ request()->query('subscriptionNotifications') ? 'active' : '' }}" data-toggle="tab"
                                href="#subscription">
                                <span class="text-uppercase">
                                    <i class="las la-cog"></i>
                                    {{ __('Subscription Notification') }}
                                </span>
                            </a>
                        </li>
                        <li>
                            <a class="{{ request()->query('withdrawNotifications') ? 'active' : '' }}" data-toggle="tab"
                                href="#withdraw">
                                <span class="text-uppercase">
                                    <i class="las la-cog"></i>
                                    {{ __('Withdraw Notifications') }}
                                </span>
                            </a>
                        </li>
                        <li>
                            <a class="{{ request()->query('supportNotifications') ? 'active' : '' }}" data-toggle="tab"
                                href="#support">
                                <span class="text-uppercase">
                                    <i class="las la-cookie-bite"></i>
                                    {{ __('Support Notifications') }}
                                </span>
                            </a>
                        </li>


                        <li>
                            <a class="{{ request()->query('kycNotifications') ? 'active' : '' }}" data-toggle="tab"
                                href="#kyc">
                                <span class="text-uppercase">
                                    <i class="las la-cookie-bite"></i>
                                    {{ __('KYC Notifications') }}
                                </span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>


            <div class="tab-content tabcontent-border">
                <div class="tab-pane fade {{ request()->query('notifications') || request()->query->count() == 0 ? 'show active' : '' }}"
                    id="general" role="tabpanel">
                    <div class="card">
                        <div class="card-body">
                            <div class="pt-4">
                                <div class="notification-ui_dd-content">

                                    @forelse ($notifications as $notification)
                                        <div class="notification-list {{ $notification->read_at == null ? 'notification-list--unread' : 'notification-list--read' }}"
                                            id="notification-{{ $notification->id }}">
                                            <div class="notification-list_content">
                                                <div class="notification-list_detail">
                                                    <p class="text-muted">{{ $notification->data['message'] }}</p>
                                                    <p class="text-primary">
                                                        <i class="las la-clock"></i>
                                                        <small>{{ $notification->created_at->diffforhumans() }}</small>
                                                    </p>
                                                </div>
                                            </div>
                                            <label class="toggle" title="{{ $notification->read_at == null ? 'Mark as read' : 'Mark as unread' }}">
                                                <input type="checkbox"
                                                    data-url="{{ route('admin.markNotification.single', $notification->id) }}"
                                                    class="check" {{ $notification->read_at != null ? 'checked' : '' }}
                                                    data-id="notification-{{ $notification->id }}">
                                                <span></span>
                                            </label>
                                        </div>
                                    @empty
                                        <div class="text-center py-5">
                                            <i class="las la-bell-slash" style="font-size: 48px; color: #ccc;"></i>
                                            <p class="text-muted mt-3">{{ __('No notifications yet in this category') }}</p>
                                        </div>
                                    @endforelse

                                    @if ($notifications->hasPages())
                                        <div class="card">
                                            <div class="card-footer">
                                                {{ $notifications->links() }}
                                            </div>
                                        </div>
                                    @endif


                                </div>
                            </div>
                        </div>
                    </div>
                </div>


                <div class="tab-pane {{ request()->query('depositNotifications') ? 'show active' : '' }}" id="deposit"
                    role="tabpanel">
                    <div class="card">
                        <div class="card-body">
                            <div class="pt-4">
                                <div class="notification-ui_dd-content">

                                    @forelse ($depositNotifications as $notification)
                                        <div class="notification-list {{ $notification->read_at == null ? 'notification-list--unread' : 'notification-list--read' }}"
                                            id="notification-{{ $notification->id }}">
                                            <div class="notification-list_content">
                                                <div class="notification-list_detail">
                                                    <p class="text-muted">{{ $notification->data['message'] }}</p>
                                                    <p class="text-primary">
                                                        <i class="las la-clock"></i>
                                                        <small>{{ $notification->created_at->diffforhumans() }}</small>
                                                    </p>
                                                </div>
                                            </div>
                                            <label class="toggle" title="{{ $notification->read_at == null ? 'Mark as read' : 'Mark as unread' }}">
                                                <input type="checkbox"
                                                    data-url="{{ route('admin.markNotification.single', $notification->id) }}"
                                                    class="check" {{ $notification->read_at != null ? 'checked' : '' }}
                                                    data-id="notification-{{ $notification->id }}">
                                                <span></span>
                                            </label>
                                        </div>
                                    @empty
                                        <div class="text-center py-5">
                                            <i class="las la-bell-slash" style="font-size: 48px; color: #ccc;"></i>
                                            <p class="text-muted mt-3">{{ __('No deposit notifications yet') }}</p>
                                        </div>
                                    @endforelse

                                    @if ($depositNotifications->hasPages())
                                        <div class="card">
                                            <div class="card-footer">
                                                {{ $depositNotifications->links() }}
                                            </div>
                                        </div>
                                    @endif


                                </div>
                            </div>
                        </div>
                    </div>
                </div>


                <div class="tab-pane fade {{ request()->query('subscriptionNotifications') ? 'show active' : '' }}"
                    id="subscription" role="tabpanel">
                    <div class="card">
                        <div class="card-body">
                            <div class="pt-4">
                                <div class="notification-ui_dd-content">

                                    @forelse ($subscriptionNotifications as $notification)
                                        <div class="notification-list {{ $notification->read_at == null ? 'notification-list--unread' : 'notification-list--read' }}"
                                            id="notification-{{ $notification->id }}">
                                            <div class="notification-list_content">
                                                <div class="notification-list_detail">
                                                    <p class="text-muted">{{ $notification->data['message'] }}</p>
                                                    <p class="text-primary">
                                                        <i class="las la-clock"></i>
                                                        <small>{{ $notification->created_at->diffforhumans() }}</small>
                                                    </p>
                                                </div>
                                            </div>
                                            <label class="toggle" title="{{ $notification->read_at == null ? 'Mark as read' : 'Mark as unread' }}">
                                                <input type="checkbox"
                                                    data-url="{{ route('admin.markNotification.single', $notification->id) }}"
                                                    class="check" {{ $notification->read_at != null ? 'checked' : '' }}
                                                    data-id="notification-{{ $notification->id }}">
                                                <span></span>
                                            </label>
                                        </div>
                                    @empty
                                        <div class="text-center py-5">
                                            <i class="las la-bell-slash" style="font-size: 48px; color: #ccc;"></i>
                                            <p class="text-muted mt-3">{{ __('No subscription notifications yet') }}</p>
                                        </div>
                                    @endforelse

                                    @if ($subscriptionNotifications->hasPages())
                                        <div class="card">
                                            <div class="card-footer">
                                                {{ $subscriptionNotifications->links() }}
                                            </div>
                                        </div>
                                    @endif


                                </div>
                            </div>
                        </div>
                    </div>
                </div>


                <div class="tab-pane fade {{ request()->query('withdrawNotifications') ? 'show active' : '' }}"
                    id="withdraw" role="tabpanel">
                    <div class="card">
                        <div class="card-body">
                            <div class="pt-4">
                                <div class="notification-ui_dd-content">

                                    @forelse ($withdrawNotifications as $notification)
                                        <div class="notification-list {{ $notification->read_at == null ? 'notification-list--unread' : 'notification-list--read' }}"
                                            id="notification-{{ $notification->id }}">
                                            <div class="notification-list_content">
                                                <div class="notification-list_detail">
                                                    <p class="text-muted">{{ $notification->data['message'] }}</p>
                                                    <p class="text-primary">
                                                        <i class="las la-clock"></i>
                                                        <small>{{ $notification->created_at->diffforhumans() }}</small>
                                                    </p>
                                                </div>
                                            </div>
                                            <label class="toggle" title="{{ $notification->read_at == null ? 'Mark as read' : 'Mark as unread' }}">
                                                <input type="checkbox"
                                                    data-url="{{ route('admin.markNotification.single', $notification->id) }}"
                                                    class="check" {{ $notification->read_at != null ? 'checked' : '' }}
                                                    data-id="notification-{{ $notification->id }}">
                                                <span></span>
                                            </label>
                                        </div>
                                    @empty
                                        <div class="text-center py-5">
                                            <i class="las la-bell-slash" style="font-size: 48px; color: #ccc;"></i>
                                            <p class="text-muted mt-3">{{ __('No withdraw notifications yet') }}</p>
                                        </div>
                                    @endforelse

                                    @if ($withdrawNotifications->hasPages())
                                        <div class="card">
                                            <div class="card-footer">
                                                {{ $withdrawNotifications->links() }}
                                            </div>
                                        </div>
                                    @endif


                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade {{ request()->query('supportNotifications') ? 'show active' : '' }}"
                    id="support" role="tabpanel">
                    <div class="card">
                        <div class="card-body">
                            <div class="pt-4">
                                <div class="notification-ui_dd-content">

                                    @forelse ($ticketNotifications as $notification)
                                        <div class="notification-list {{ $notification->read_at == null ? 'notification-list--unread' : 'notification-list--read' }}"
                                            id="notification-{{ $notification->id }}">
                                            <div class="notification-list_content">
                                                <div class="notification-list_detail">
                                                    <p class="text-muted">{{ $notification->data['message'] }}</p>
                                                    <p class="text-primary">
                                                        <i class="las la-clock"></i>
                                                        <small>{{ $notification->created_at->diffforhumans() }}</small>
                                                    </p>
                                                </div>
                                            </div>
                                            <label class="toggle" title="{{ $notification->read_at == null ? 'Mark as read' : 'Mark as unread' }}">
                                                <input type="checkbox"
                                                    data-url="{{ route('admin.markNotification.single', $notification->id) }}"
                                                    class="check" {{ $notification->read_at != null ? 'checked' : '' }}
                                                    data-id="notification-{{ $notification->id }}">
                                                <span></span>
                                            </label>
                                        </div>
                                    @empty
                                        <div class="text-center py-5">
                                            <i class="las la-bell-slash" style="font-size: 48px; color: #ccc;"></i>
                                            <p class="text-muted mt-3">{{ __('No support notifications yet') }}</p>
                                        </div>
                                    @endforelse

                                    @if ($ticketNotifications->hasPages())
                                        <div class="card">
                                            <div class="card-footer">
                                                {{ $ticketNotifications->links() }}
                                            </div>
                                        </div>
                                    @endif


                                </div>
                            </div>
                        </div>
                    </div>
                </div>


                <div class="tab-pane fade {{ request()->query('kycNotifications') ? 'show active' : '' }}" id="kyc"
                    role="tabpanel">
                    <div class="card">
                        <div class="card-body">
                            <div class="pt-4">
                                <div class="notification-ui_dd-content">

                                    @forelse ($kycNotifications as $notification)
                                        <div class="notification-list {{ $notification->read_at == null ? 'notification-list--unread' : 'notification-list--read' }}"
                                            id="notification-{{ $notification->id }}">
                                            <div class="notification-list_content">
                                                <div class="notification-list_detail">
                                                    <p class="text-muted">{{ $notification->data['message'] }}</p>
                                                    <p class="text-primary">
                                                        <i class="las la-clock"></i>
                                                        <small>{{ $notification->created_at->diffforhumans() }}</small>
                                                    </p>
                                                </div>
                                            </div>
                                            <label class="toggle" title="{{ $notification->read_at == null ? 'Mark as read' : 'Mark as unread' }}">
                                                <input type="checkbox"
                                                    data-url="{{ route('admin.markNotification.single', $notification->id) }}"
                                                    class="check" {{ $notification->read_at != null ? 'checked' : '' }}
                                                    data-id="notification-{{ $notification->id }}">
                                                <span></span>
                                            </label>
                                        </div>
                                    @empty
                                        <div class="text-center py-5">
                                            <i class="las la-bell-slash" style="font-size: 48px; color: #ccc;"></i>
                                            <p class="text-muted mt-3">{{ __('No KYC notifications yet') }}</p>
                                        </div>
                                    @endforelse

                                    @if ($kycNotifications->hasPages())
                                        <div class="card">
                                            <div class="card-footer">
                                                {{ $kycNotifications->links() }}
                                            </div>
                                        </div>
                                    @endif


                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('style')
    <style>
        .toggle span {
            display: block;
            width: 40px;
            height: 24px;
            border-radius: 99em;
            background-color: #e9ecf4;
            box-shadow: inset 1px 1px 1px 0 rgba(0, 0, 0, 0.05);
            position: relative;
            transition: 0.15s ease;
        }

        .toggle span:before {
            content: "";
            display: block;
            position: absolute;
            left: 3px;
            top: 3px;
            height: 18px;
            width: 18px;
            background-color: #ffffff;
            border-radius: 50%;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.15);
            transition: 0.15s ease;
        }

        .toggle input {
            clip: rect(0 0 0 0);
            -webkit-clip-path: inset(50%);
            clip-path: inset(50%);
            height: 1px;
            overflow: hidden;
            position: absolute;
            white-space: nowrap;
            width: 1px;
        }

        .toggle input:checked+span {
            background-color: #434ce8;
        }

        .toggle input:checked+span:before {
            transform: translateX(calc(100% - 2px));
        }

        .toggle input:focus+span {
            box-shadow: 0 0 0 4px #ecf3fe;
        }



        .notification-ui_dd-content {
            margin-bottom: 30px;
        }

        .notification-list {
            display: -webkit-box;
            display: -ms-flexbox;
            display: flex;
            -webkit-box-pack: justify;
            -ms-flex-pack: justify;
            justify-content: space-between;
            align-items: center;
            padding: 10px 20px;
            margin-bottom: 7px;
            background: #fff;
            -webkit-box-shadow: 0 3px 10px rgba(0, 0, 0, 0.06);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.06);
        }

        .notification-list--unread {
            border-left: 2px solid #ea8c08;
        }

        .notification-list--read {
            border-left: 2px solid #03ae30;
        }

        .notification-list .notification-list_content {
            display: -webkit-box;
            display: -ms-flexbox;
            display: flex;
        }

        .notification-list .notification-list_content .notification-list_img img {
            height: 48px;
            width: 48px;
            border-radius: 50px;
            margin-right: 20px;
        }

        .notification-list .notification-list_content .notification-list_detail p {
            margin-bottom: 5px;
            line-height: 1.2;
        }

        .notification-list .notification-list_feature-img img {
            height: 48px;
            width: 48px;
            border-radius: 5px;
            margin-left: 20px;
        }
    </style>
@endpush

@push('scripts')
    <script>
        // Helper function to show notifications consistently
        function showNotification(success, message, title) {
            if (typeof notify !== 'undefined') {
                try {
                    if (success) {
                        notify()
                            .success()
                            .title(title || 'Success')
                            .message(message)
                            .send();
                    } else {
                        notify()
                            .error()
                            .title(title || 'Error')
                            .message(message)
                            .send();
                    }
                } catch(e) {
                    console.error('Error showing notification:', e);
                    alert(message);
                }
            } else {
                alert(message);
            }
        }
        
        $(function() {
            'use strict'

            $('.check').on('change', function() {
                var $checkbox = $(this);
                var $notification = $('#' + $checkbox.data('id'));
                var isChecked = $checkbox.is(':checked');
                
                $.ajax({
                    url: $checkbox.data('url'),
                    method: "POST",
                    data: {
                        "_token": "{{ csrf_token() }}",
                        "id": $checkbox.data('id').replace('notification-', '')
                    },
                    success: function(response) {
                        if (response.success) {
                            // Update the notification border color based on read/unread status
                            if (response.isRead) {
                                $notification.removeClass('notification-list--unread').addClass('notification-list--read');
                            } else {
                                $notification.removeClass('notification-list--read').addClass('notification-list--unread');
                            }
                            
                            // Update toggle title
                            $checkbox.closest('label').attr('title', response.isRead ? 'Mark as unread' : 'Mark as read');
                            
                            showNotification(true, response.message || (response.isRead ? 'Notification marked as read' : 'Notification marked as unread'));
                        } else {
                            // Revert checkbox state on error
                            $checkbox.prop('checked', !isChecked);
                            showNotification(false, response.message || 'Something went wrong');
                        }
                    },
                    error: function(xhr) {
                        // Revert checkbox state on error
                        $checkbox.prop('checked', !isChecked);
                        var errorMessage = xhr.responseJSON && xhr.responseJSON.message 
                            ? xhr.responseJSON.message 
                            : 'Something went wrong';
                        showNotification(false, errorMessage);
                    }
                })
            })
        })
    </script>
@endpush
