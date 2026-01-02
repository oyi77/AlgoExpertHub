<!-- Billing Statistics -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50 mb-1">Account Balance</h6>
                        <h2 class="mb-0" id="balanceValue">
                            @if($billingInfo['success'] ?? false)
                                ${{ number_format($billingInfo['balance'] ?? 0, 2) }}
                            @else
                                <small class="text-white-50">N/A</small>
                            @endif
                        </h2>
                    </div>
                    <div class="text-right">
                        <i class="fas fa-wallet fa-3x opacity-50"></i>
                    </div>
                </div>
                @if(!($billingInfo['success'] ?? false))
                    <small class="text-white-50">{{ $billingInfo['message'] ?? 'Unable to fetch balance' }}</small>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50 mb-1">Trial Amount</h6>
                        <h2 class="mb-0" id="spendingPowerValue">
                            @if($billingInfo['success'] ?? false)
                                ${{ number_format($billingInfo['trial_amount'] ?? $billingInfo['spending_credits'] ?? 0, 2) }}
                            @else
                                <small class="text-white-50">N/A</small>
                            @endif
                        </h2>
                    </div>
                    <div class="text-right">
                        <i class="fas fa-credit-card fa-3x opacity-50"></i>
                    </div>
                </div>
                @if(!($billingInfo['success'] ?? false))
                    <small class="text-white-50">{{ $billingInfo['message'] ?? 'Unable to fetch spending power' }}</small>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50 mb-1">Total Accounts</h6>
                        <h2 class="mb-0" id="totalAccountsValue">
                            @if($accountStats['success'] ?? false)
                                {{ $accountStats['total_accounts'] ?? 0 }}
                            @else
                                <small class="text-white-50">N/A</small>
                            @endif
                        </h2>
                    </div>
                    <div class="text-right">
                        <i class="fas fa-users fa-3x opacity-50"></i>
                    </div>
                </div>
                @if($accountStats['success'] ?? false)
                    <small class="text-white-50">
                        {{ $accountStats['active_accounts'] ?? 0 }} active, 
                        {{ $accountStats['inactive_accounts'] ?? 0 }} inactive
                    </small>
                @else
                    <small class="text-white-50">{{ $accountStats['note'] ?? 'Using local database' }}</small>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Additional Billing Information -->
@if(($billingInfo['success'] ?? false) && (isset($billingInfo['subscription']) || isset($billingInfo['usage'])))
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-light">
                <h6 class="mb-0"><i class="fas fa-crown"></i> Subscription Information</h6>
            </div>
            <div class="card-body">
                        @if(isset($billingInfo['billing_statuses']) && !empty($billingInfo['billing_statuses']))
                            @php $status = $billingInfo['billing_statuses'][0] ?? []; @endphp
                            @if(isset($status['planId']))
                                <div class="mb-2">
                                    <strong>Plan ID:</strong> 
                                    <span class="badge badge-primary">{{ $status['planId'] }}</span>
                                </div>
                            @endif
                            @if(isset($status['accessTerminated']))
                                <div class="mb-2">
                                    <strong>Access Status:</strong> 
                                    <span class="badge badge-{{ $status['accessTerminated'] ? 'danger' : 'success' }}">
                                        {{ $status['accessTerminated'] ? 'Terminated' : 'Active' }}
                                    </span>
                                </div>
                            @endif
                            @if(isset($status['thresholdCost']))
                                <div class="mb-2">
                                    <strong>Threshold Cost:</strong> ${{ number_format($status['thresholdCost'], 2) }}
                                </div>
                            @endif
                        @elseif(isset($billingInfo['subscription']))
                            @php $sub = $billingInfo['subscription']; @endphp
                            @if(isset($sub['plan_id']))
                                <div class="mb-2">
                                    <strong>Plan ID:</strong> 
                                    <span class="badge badge-primary">{{ $sub['plan_id'] }}</span>
                                </div>
                            @endif
                        @else
                            <p class="text-muted mb-0">No subscription information available</p>
                        @endif
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-light">
                <h6 class="mb-0"><i class="fas fa-chart-bar"></i> Usage Information</h6>
            </div>
            <div class="card-body">
                        @if($billingInfo['success'] ?? false)
                            @if(isset($billingInfo['trial_amount']))
                                <div class="mb-2">
                                    <strong>Trial Amount:</strong> 
                                    <span class="badge badge-success">${{ number_format($billingInfo['trial_amount'], 2) }}</span>
                                </div>
                            @endif
                            @if(isset($billingInfo['total_available']))
                                <div class="mb-2">
                                    <strong>Total Available:</strong> 
                                    <span class="badge badge-info">${{ number_format($billingInfo['total_available'], 2) }}</span>
                                </div>
                            @endif
                            @if(isset($billingInfo['billing_statuses']) && !empty($billingInfo['billing_statuses']))
                                @php $status = $billingInfo['billing_statuses'][0] ?? []; @endphp
                                @if(isset($status['amountPastDue']) && $status['amountPastDue'] > 0)
                                    <div class="mb-2">
                                        <strong>Amount Past Due:</strong> 
                                        <span class="badge badge-warning">${{ number_format($status['amountPastDue'], 2) }}</span>
                                    </div>
                                @endif
                            @endif
                        @else
                            <p class="text-muted mb-0">No usage information available</p>
                        @endif
            </div>
        </div>
    </div>
</div>
@endif

<!-- API Status -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-info-circle"></i> API Status</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>Billing API</span>
                    <span class="badge badge-{{ ($billingInfo['success'] ?? false) ? 'success' : 'danger' }}">
                        {{ ($billingInfo['success'] ?? false) ? 'Connected' : 'Error' }}
                    </span>
                </div>
                @if(!($billingInfo['success'] ?? false))
                    <small class="text-danger">{{ $billingInfo['message'] ?? 'Unable to connect' }}</small>
                @endif
            </div>
            <div class="col-md-6">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>Account Stats</span>
                    <span class="badge badge-{{ ($accountStats['success'] ?? false) ? 'success' : 'warning' }}">
                        {{ ($accountStats['success'] ?? false) ? 'API' : 'Local DB' }}
                    </span>
                </div>
                <small class="text-muted">{{ $accountStats['source'] ?? 'local_database' }}</small>
            </div>
        </div>
    </div>
</div>

<!-- Deposit Section -->
@if($billingInfo['success'] ?? false)
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="fas fa-plus-circle"></i> Deposit to MetaApi Account</h5>
            </div>
            <div class="card-body">
                <form id="depositForm">
                    @csrf
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="depositAmount">Deposit Amount (USD)</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">$</span>
                                    </div>
                                    <input type="number" 
                                           class="form-control" 
                                           id="depositAmount" 
                                           name="amount" 
                                           step="0.01" 
                                           min="0.01" 
                                           placeholder="0.00" 
                                           required>
                                </div>
                                <small class="form-text text-muted">Minimum deposit: $0.01</small>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           id="termsAgreement" 
                                           name="terms_agreement" 
                                           required>
                                    <label class="form-check-label" for="termsAgreement">
                                        I agree to the <a href="https://metaapi.cloud/terms" target="_blank">Terms and Conditions</a> and <a href="https://metaapi.cloud/privacy" target="_blank">Privacy Policy</a>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           id="refundAgreement" 
                                           name="refund_agreement" 
                                           required>
                                    <label class="form-check-label" for="refundAgreement">
                                        I agree to the <a href="https://metaapi.cloud/refund-policy" target="_blank">Refund Policy</a>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary" id="depositBtn">
                            <i class="fas fa-credit-card"></i> Deposit to MetaApi
                        </button>
                        <small class="form-text text-muted d-block mt-2">
                            <i class="fas fa-info-circle"></i> You need to have a payment method set up in your MetaApi account first. 
                            <a href="https://app.metaapi.cloud" target="_blank">Manage payment methods</a>
                        </small>
                    </div>
                </form>
                <div id="depositResult" class="mt-3" style="display: none;"></div>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Local MetaApi Connections -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-list"></i> Local MetaApi Connections</h5>
        <button type="button" class="btn btn-sm btn-primary" id="refreshStatsBtn">
            <i class="fas fa-sync-alt"></i> Refresh
        </button>
    </div>
    <div class="card-body">
        @if($localConnections->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Account ID</th>
                            <th>Owner</th>
                            <th>Status</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($localConnections as $connection)
                            <tr>
                                <td>{{ $connection->name }}</td>
                                <td><code>{{ $connection->credentials['account_id'] ?? 'N/A' }}</code></td>
                                <td>
                                    @if($connection->admin)
                                        <span class="badge badge-info">Admin: {{ $connection->admin->username }}</span>
                                    @elseif($connection->user)
                                        <span class="badge badge-secondary">User: {{ $connection->user->username }}</span>
                                    @else
                                        <span class="badge badge-light">System</span>
                                    @endif
                                </td>
                                <td>
                                    @if($connection->is_active)
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>{{ $connection->created_at->format('Y-m-d H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No MetaApi connections found. 
                <a href="{{ route('admin.trading-management.config.exchange-connections.create') }}" class="alert-link">Create one now</a>
            </div>
        @endif
    </div>
</div>

<script>
$(function() {
    'use strict'
    
    // Refresh stats button
    $('#refreshStatsBtn').on('click', function() {
        const btn = $(this);
        const originalHtml = btn.html();
        
        btn.prop('disabled', true);
        btn.html('<i class="fas fa-spinner fa-spin"></i> Refreshing...');
        
        fetch('{{ route("admin.trading-management.config.metaapi-stats.refresh") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update balance
                if (data.billing.success) {
                    $('#balanceValue').html('$' + parseFloat(data.billing.balance || 0).toFixed(2));
                }
                
                // Update spending power
                if (data.billing.success) {
                    const spendingCredits = data.billing.trial_amount || data.billing.spending_credits || 0;
                    $('#spendingPowerValue').html('$' + parseFloat(spendingCredits).toFixed(2));
                }
                
                // Update total accounts
                if (data.accounts.success) {
                    $('#totalAccountsValue').html(data.accounts.total_accounts || 0);
                }
                
                // Show success message
                toastr.success('Statistics refreshed successfully');
            } else {
                toastr.error('Failed to refresh statistics');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            toastr.error('Error refreshing statistics');
        })
        .finally(() => {
            btn.prop('disabled', false);
            btn.html(originalHtml);
        });
    });

    // Deposit form handler
    $('#depositForm').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const btn = $('#depositBtn');
        const resultDiv = $('#depositResult');
        const originalBtnText = btn.html();
        
        // Disable button
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
        resultDiv.hide().empty();
        
        // Get form data
        const formData = {
            amount: parseFloat($('#depositAmount').val()),
            terms_agreement: $('#termsAgreement').is(':checked') ? 1 : 0,
            refund_agreement: $('#refundAgreement').is(':checked') ? 1 : 0,
            _token: '{{ csrf_token() }}'
        };
        
        // Send deposit request
        fetch('{{ route("admin.trading-management.config.metaapi-stats.deposit") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                resultDiv.html(
                    '<div class="alert alert-success">' +
                    '<i class="fas fa-check-circle"></i> ' + data.message +
                    '</div>'
                ).show();
                
                // Reset form
                form[0].reset();
                
                // Refresh stats to show updated balance
                setTimeout(() => {
                    $('#refreshStatsBtn').click();
                }, 2000);
            } else {
                resultDiv.html(
                    '<div class="alert alert-danger">' +
                    '<i class="fas fa-exclamation-circle"></i> ' + (data.message || 'Deposit failed') +
                    '</div>'
                ).show();
            }
        })
        .catch(error => {
            console.error('Deposit error:', error);
            resultDiv.html(
                '<div class="alert alert-danger">' +
                '<i class="fas fa-exclamation-circle"></i> Failed to process deposit. Please try again.' +
                '</div>'
            ).show();
        })
        .finally(() => {
            // Re-enable button
            btn.prop('disabled', false).html(originalBtnText);
        });
    });
});
</script>
