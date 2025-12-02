@extends(Config::theme() . 'layout.auth')

@section('title')
    {{ $title }}
@endsection

@section('content')
    <div class="sp_site_card">
        <div class="card-header">
            <h4>{{ __($title) }} - {{ $trader->username ?? $trader->email }}</h4>
        </div>
        <div class="card-body">
                        <form action="{{ route('user.copy-trading.subscriptions.store', $trader->id) }}" method="POST">
                            @csrf

                            <div class="form-group">
                                <label for="connection_id">Select Connection *</label>
                                <select class="form-control" name="connection_id" id="connection_id" required>
                                    <option value="">Choose...</option>
                                    @foreach($connections as $connection)
                                        <option value="{{ $connection->id }}">{{ $connection->name }} ({{ $connection->exchange_name }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="copy_mode">Copy Mode *</label>
                                <select class="form-control" name="copy_mode" id="copy_mode" required>
                                    <option value="easy">Easy Copy</option>
                                    <option value="advanced">Advanced Copy</option>
                                </select>
                                <small class="form-text text-muted">
                                    Easy Copy: Automatically matches trader's position size as % of balance<br>
                                    Advanced Copy: Custom position sizing rules
                                </small>
                            </div>

                            <div id="easy-mode-settings">
                                <div class="form-group">
                                    <label for="risk_multiplier">Risk Multiplier *</label>
                                    <input type="number" class="form-control" name="risk_multiplier" 
                                        id="risk_multiplier" value="1.0" step="0.1" min="0.1" max="10" required>
                                    <small class="form-text text-muted">
                                        Multiplier to adjust the matched percentage (0.1 to 10.0)
                                    </small>
                                </div>
                            </div>

                            <div id="advanced-mode-settings" style="display: none;">
                                <div class="form-group">
                                    <label for="method">Position Sizing Method *</label>
                                    <select class="form-control" name="method" id="method">
                                        <option value="percentage">Percentage of Balance</option>
                                        <option value="fixed_quantity">Fixed Quantity</option>
                                    </select>
                                </div>

                                <div class="form-group" id="percentage-field">
                                    <label for="percentage">Percentage (%) *</label>
                                    <input type="number" class="form-control" name="percentage" 
                                        id="percentage" step="0.01" min="0.01" max="100">
                                    <small class="form-text text-muted">
                                        Percentage of your balance to use per trade
                                    </small>
                                </div>

                                <div class="form-group" id="fixed-quantity-field" style="display: none;">
                                    <label for="fixed_quantity">Fixed Quantity *</label>
                                    <input type="number" class="form-control" name="fixed_quantity" 
                                        id="fixed_quantity" step="0.00000001" min="0.00000001">
                                    <small class="form-text text-muted">
                                        Fixed quantity to copy per trade
                                    </small>
                                </div>

                                <div class="form-group">
                                    <label for="min_quantity">Min Quantity (Optional)</label>
                                    <input type="number" class="form-control" name="min_quantity" 
                                        id="min_quantity" step="0.00000001" min="0">
                                </div>

                                <div class="form-group">
                                    <label for="max_quantity">Max Quantity (Optional)</label>
                                    <input type="number" class="form-control" name="max_quantity" 
                                        id="max_quantity" step="0.00000001" min="0">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="max_position_size">Max Position Size (USD, Optional)</label>
                                <input type="number" class="form-control" name="max_position_size" 
                                    id="max_position_size" step="0.01" min="0">
                                <small class="form-text text-muted">
                                    Maximum USD value per copied trade
                                </small>
                            </div>

                            <button type="submit" class="btn btn-primary">Subscribe</button>
                            <a href="{{ route('user.copy-trading.traders.index') }}" class="btn btn-secondary">Cancel</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('copy_mode').addEventListener('change', function() {
            const mode = this.value;
            const easySettings = document.getElementById('easy-mode-settings');
            const advancedSettings = document.getElementById('advanced-mode-settings');
            
            if (mode === 'easy') {
                easySettings.style.display = 'block';
                advancedSettings.style.display = 'none';
                document.getElementById('risk_multiplier').required = true;
                document.getElementById('method').required = false;
                document.getElementById('percentage').required = false;
                document.getElementById('fixed_quantity').required = false;
            } else {
                easySettings.style.display = 'none';
                advancedSettings.style.display = 'block';
                document.getElementById('risk_multiplier').required = false;
                document.getElementById('method').required = true;
            }
        });

        document.getElementById('method').addEventListener('change', function() {
            const method = this.value;
            const percentageField = document.getElementById('percentage-field');
            const fixedQuantityField = document.getElementById('fixed-quantity-field');
            
            if (method === 'percentage') {
                percentageField.style.display = 'block';
                fixedQuantityField.style.display = 'none';
                document.getElementById('percentage').required = true;
                document.getElementById('fixed_quantity').required = false;
            } else {
                percentageField.style.display = 'none';
                fixedQuantityField.style.display = 'block';
                document.getElementById('percentage').required = false;
                document.getElementById('fixed_quantity').required = true;
            }
        });
    </script>
@endsection

