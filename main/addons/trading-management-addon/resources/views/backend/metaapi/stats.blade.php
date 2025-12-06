@extends('backend.layout.master')

@section('element')
<div class="row">
    <div class="col-12">
        <!-- Page Header -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3><i class="fas fa-chart-line"></i> {{ $title }}</h3>
                        <p class="text-muted mb-0">MetaApi.cloud account statistics and billing information</p>
                    </div>
                    <div>
                        <button type="button" class="btn btn-primary" id="refreshStatsBtn">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                        <a href="{{ route('admin.trading-management.config.exchange-connections.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Connections
                        </a>
                    </div>
                </div>
            </div>
        </div>

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
                                <h2 class="mb-0" id="spendingCreditsValue">
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
                            <small class="text-white-50">{{ $billingInfo['message'] ?? 'Unable to fetch trial amount' }}</small>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-white-50 mb-1">Total Available</h6>
                                <h2 class="mb-0" id="totalAvailableValue">
                                    @if($billingInfo['success'] ?? false)
                                        ${{ number_format($billingInfo['total_available'] ?? (($billingInfo['balance'] ?? 0) + ($billingInfo['trial_amount'] ?? $billingInfo['spending_credits'] ?? 0)), 2) }}
                                    @else
                                        <small class="text-white-50">N/A</small>
                                    @endif
                                </h2>
                            </div>
                            <div class="text-right">
                                <i class="fas fa-dollar-sign fa-3x opacity-50"></i>
                            </div>
                        </div>
                        <small class="text-white-50">Balance + Trial Amount</small>
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
                            @if(isset($status['amountPastDue']) && $status['amountPastDue'] > 0)
                                <div class="mb-2">
                                    <strong>Amount Past Due:</strong> 
                                    <span class="badge badge-warning">${{ number_format($status['amountPastDue'], 2) }}</span>
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

        <!-- Account Statistics -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted mb-1">Total Accounts</h6>
                        <h3 class="mb-0" id="totalAccountsValue">
                            {{ $accountStats['total_accounts'] ?? 0 }}
                        </h3>
                        <small class="text-muted">All MetaApi accounts</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted mb-1">Active Accounts</h6>
                        <h3 class="mb-0 text-success" id="activeAccountsValue">
                            {{ $accountStats['active_accounts'] ?? 0 }}
                        </h3>
                        <small class="text-muted">Currently connected</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted mb-1">Inactive Accounts</h6>
                        <h3 class="mb-0 text-warning" id="inactiveAccountsValue">
                            {{ $accountStats['inactive_accounts'] ?? 0 }}
                        </h3>
                        <small class="text-muted">Not connected</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Local Connections Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-list"></i> MetaApi Connections in System</h5>
            </div>
            <div class="card-body">
                @if($localConnections->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Connection Name</th>
                                    <th>MetaApi Account ID</th>
                                    <th>Owner</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($localConnections as $connection)
                                <tr>
                                    <td>
                                        <strong>{{ $connection->name }}</strong>
                                    </td>
                                    <td>
                                        <code>{{ $connection->credentials['account_id'] ?? 'N/A' }}</code>
                                    </td>
                                    <td>
                                        @if($connection->admin)
                                            <span class="badge badge-info">Admin: {{ $connection->admin->username }}</span>
                                        @elseif($connection->user)
                                            <span class="badge badge-primary">User: {{ $connection->user->username }}</span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($connection->status === 'connected')
                                            <span class="badge badge-success">Connected</span>
                                        @elseif($connection->status === 'pending')
                                            <span class="badge badge-warning">Pending</span>
                                        @else
                                            <span class="badge badge-danger">{{ ucfirst($connection->status) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small>{{ $connection->created_at->format('M d, Y H:i') }}</small>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.trading-management.config.exchange-connections.show', $connection) }}" 
                                           class="btn btn-sm btn-info" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No MetaApi connections found in the system.
                        <a href="{{ route('admin.trading-management.config.exchange-connections.create') }}">Create one now</a>
                    </div>
                @endif
            </div>
        </div>

        <!-- API Status Info -->
        <div class="card mt-3">
            <div class="card-body">
                <h6><i class="fas fa-info-circle"></i> API Status</h6>
                <div class="row">
                    <div class="col-md-6">
                        <strong>Billing API:</strong> 
                        <span class="badge {{ ($billingInfo['success'] ?? false) ? 'badge-success' : 'badge-danger' }}">
                            {{ ($billingInfo['success'] ?? false) ? 'Connected' : 'Failed' }}
                        </span>
                        @if(!($billingInfo['success'] ?? false))
                            <br><small class="text-muted">{{ $billingInfo['message'] ?? 'Unable to connect' }}</small>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <strong>Account Stats API:</strong> 
                        <span class="badge {{ ($accountStats['success'] ?? false) ? 'badge-success' : 'badge-warning' }}">
                            {{ ($accountStats['success'] ?? false) ? 'Connected' : 'Using Local Data' }}
                        </span>
                        @if(isset($accountStats['note']))
                            <br><small class="text-muted">{{ $accountStats['note'] }}</small>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('refreshStatsBtn').addEventListener('click', function() {
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';
    
    fetch('{{ route("admin.trading-management.config.metaapi-stats.refresh") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update billing info
            if (data.billing.success) {
                document.getElementById('balanceValue').textContent = '$' + parseFloat(data.billing.balance || 0).toFixed(2);
                const trialAmount = data.billing.trial_amount || data.billing.spending_credits || 0;
                document.getElementById('spendingCreditsValue').textContent = '$' + parseFloat(trialAmount).toFixed(2);
                const total = data.billing.total_available || (parseFloat(data.billing.balance || 0) + parseFloat(trialAmount));
                document.getElementById('totalAvailableValue').textContent = '$' + total.toFixed(2);
            }
            
            // Update account stats
            if (data.accounts.success) {
                document.getElementById('totalAccountsValue').textContent = data.accounts.total_accounts || 0;
                document.getElementById('activeAccountsValue').textContent = data.accounts.active_accounts || 0;
                document.getElementById('inactiveAccountsValue').textContent = data.accounts.inactive_accounts || 0;
            }
            
            // Show success message
            const alert = document.createElement('div');
            alert.className = 'alert alert-success alert-dismissible fade show';
            alert.innerHTML = '<i class="fas fa-check-circle"></i> Statistics refreshed successfully!<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>';
            document.querySelector('.card-body').prepend(alert);
            
            setTimeout(() => alert.remove(), 3000);
        } else {
            alert('Failed to refresh: ' + (data.message || 'Unknown error'));
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh';
    })
    .catch(error => {
        alert('Error: ' + error.message);
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh';
    });

    // Deposit form handler
    const depositForm = document.getElementById('depositForm');
    if (depositForm) {
        depositForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const btn = document.getElementById('depositBtn');
            const resultDiv = document.getElementById('depositResult');
            const originalBtnText = btn.innerHTML;
            
            // Disable button
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            resultDiv.style.display = 'none';
            resultDiv.innerHTML = '';
            
            // Get form data
            const formData = {
                amount: parseFloat(document.getElementById('depositAmount').value),
                terms_agreement: document.getElementById('termsAgreement').checked ? 1 : 0,
                refund_agreement: document.getElementById('refundAgreement').checked ? 1 : 0,
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
                    resultDiv.innerHTML = 
                        '<div class="alert alert-success">' +
                        '<i class="fas fa-check-circle"></i> ' + data.message +
                        '</div>';
                    resultDiv.style.display = 'block';
                    
                    // Reset form
                    depositForm.reset();
                    
                    // Refresh stats to show updated balance
                    setTimeout(() => {
                        document.getElementById('refreshStatsBtn')?.click();
                    }, 2000);
                } else {
                    resultDiv.innerHTML = 
                        '<div class="alert alert-danger">' +
                        '<i class="fas fa-exclamation-circle"></i> ' + (data.message || 'Deposit failed') +
                        '</div>';
                    resultDiv.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Deposit error:', error);
                resultDiv.innerHTML = 
                    '<div class="alert alert-danger">' +
                    '<i class="fas fa-exclamation-circle"></i> Failed to process deposit. Please try again.' +
                    '</div>';
                resultDiv.style.display = 'block';
            })
            .finally(() => {
                // Re-enable button
                btn.disabled = false;
                btn.innerHTML = originalBtnText;
            });
        });
    }
});
</script>
@endsection
