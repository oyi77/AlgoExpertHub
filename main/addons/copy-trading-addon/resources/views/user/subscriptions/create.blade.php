@extends(Config::theme() . 'layout.auth')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4>{{ $title }}</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('user.copy-trading.subscriptions.store', $trader->id) }}" method="POST">
                            @csrf

                            <div class="alert alert-info">
                                You are about to follow <strong>{{ $trader->username ?? $trader->email }}</strong>
                            </div>

                            <div class="form-group">
                                <label for="connection_id">Select Trading Connection *</label>
                                <select name="connection_id" id="connection_id" class="form-control" required>
                                    <option value="">-- Select Connection --</option>
                                    @foreach($connections as $connection)
                                    <option value="{{ $connection->id }}">
                                        {{ $connection->name }} ({{ ucfirst($connection->exchange_type) }})
                                    </option>
                                    @endforeach
                                </select>
                                <small class="form-text text-muted">Trades will be copied to this connection</small>
                            </div>

                            <div class="form-group">
                                <label>Copy Mode *</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="copy_mode" id="mode_easy" value="easy" checked onchange="toggleModeFields()">
                                    <label class="form-check-label" for="mode_easy">
                                        <strong>Easy Copy</strong> - Automatically match trader's position size relative to your balance
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="copy_mode" id="mode_advanced" value="advanced" onchange="toggleModeFields()">
                                    <label class="form-check-label" for="mode_advanced">
                                        <strong>Advanced Copy</strong> - Customize position sizing
                                    </label>
                                </div>
                            </div>

                            <!-- Easy Mode Fields -->
                            <div id="easy_fields">
                                <div class="form-group">
                                    <label for="risk_multiplier">Risk Multiplier</label>
                                    <input type="number" class="form-control" name="risk_multiplier" id="risk_multiplier" 
                                           value="{{ $setting->risk_multiplier_default ?? 1.0 }}" step="0.1" min="0.1" max="10">
                                    <small class="form-text text-muted">1.0 = Copy exact percentage, 2.0 = Double risk, 0.5 = Half risk</small>
                                </div>
                            </div>

                            <!-- Advanced Mode Fields -->
                            <div id="advanced_fields" style="display:none;">
                                <div class="form-group">
                                    <label>Copy Method</label>
                                    <select name="copy_method" class="form-control">
                                        <option value="fixed_quantity">Fixed Quantity</option>
                                        <option value="percentage">Percentage of Balance</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Fixed Quantity / Percentage</label>
                                    <input type="number" class="form-control" name="fixed_quantity" step="0.01" min="0.01">
                                    <small class="form-text text-muted">Enter fixed quantity (e.g., 0.1) or percentage (e.g., 5 for 5%)</small>
                                </div>

                                <div class="form-group">
                                    <label>Min Quantity</label>
                                    <input type="number" class="form-control" name="min_quantity" step="0.01" min="0.01">
                                </div>

                                <div class="form-group">
                                    <label>Max Quantity</label>
                                    <input type="number" class="form-control" name="max_quantity" step="0.01">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="max_position_size">Max Position Size (Optional)</label>
                                <input type="number" class="form-control" name="max_position_size" id="max_position_size" step="1" min="0">
                                <small class="form-text text-muted">Maximum value per position in USD (leave blank for unlimited)</small>
                            </div>

                            <button type="submit" class="btn btn-success">Start Following</button>
                            <a href="{{ route('user.copy-trading.traders.show', $trader->id) }}" class="btn btn-secondary">Cancel</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
<script>
function toggleModeFields() {
    const easyMode = document.getElementById('mode_easy').checked;
    document.getElementById('easy_fields').style.display = easyMode ? 'block' : 'none';
    document.getElementById('advanced_fields').style.display = easyMode ? 'none' : 'block';
}
</script>
@endpush
