@extends(Config::theme() . 'layout.auth')

@section('title')
    {{ $title }}
@endsection

@section('content')
    <div class="sp_site_card">
        <div class="card-header">
            <h4>{{ __($title) }} - {{ $subscription->trader->username ?? $subscription->trader->email }}</h4>
        </div>
        <div class="card-body">
                        <form action="{{ route('user.copy-trading.subscriptions.update', $subscription->id) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="form-group">
                                <label for="connection_id">Select Connection *</label>
                                <select class="form-control" name="connection_id" id="connection_id" required>
                                    @foreach($connections as $connection)
                                        <option value="{{ $connection->id }}" 
                                            {{ $subscription->connection_id == $connection->id ? 'selected' : '' }}>
                                            {{ $connection->name }} ({{ $connection->exchange_name }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="copy_mode">Copy Mode *</label>
                                <select class="form-control" name="copy_mode" id="copy_mode" required>
                                    <option value="easy" {{ $subscription->copy_mode === 'easy' ? 'selected' : '' }}>Easy Copy</option>
                                    <option value="advanced" {{ $subscription->copy_mode === 'advanced' ? 'selected' : '' }}>Advanced Copy</option>
                                </select>
                            </div>

                            <div id="easy-mode-settings" style="{{ $subscription->copy_mode === 'easy' ? '' : 'display: none;' }}">
                                <div class="form-group">
                                    <label for="risk_multiplier">Risk Multiplier *</label>
                                    <input type="number" class="form-control" name="risk_multiplier" 
                                        id="risk_multiplier" value="{{ $subscription->risk_multiplier }}" 
                                        step="0.1" min="0.1" max="10" required>
                                </div>
                            </div>

                            <div id="advanced-mode-settings" style="{{ $subscription->copy_mode === 'advanced' ? '' : 'display: none;' }}">
                                <div class="form-group">
                                    <label for="method">Position Sizing Method *</label>
                                    <select class="form-control" name="method" id="method">
                                        <option value="percentage" {{ $subscription->getCopyMethod() === 'percentage' ? 'selected' : '' }}>Percentage of Balance</option>
                                        <option value="fixed_quantity" {{ $subscription->getCopyMethod() === 'fixed_quantity' ? 'selected' : '' }}>Fixed Quantity</option>
                                    </select>
                                </div>

                                <div class="form-group" id="percentage-field" style="{{ $subscription->getCopyMethod() === 'percentage' ? '' : 'display: none;' }}">
                                    <label for="percentage">Percentage (%) *</label>
                                    <input type="number" class="form-control" name="percentage" 
                                        id="percentage" value="{{ $subscription->getCopyPercentage() }}" 
                                        step="0.01" min="0.01" max="100">
                                </div>

                                <div class="form-group" id="fixed-quantity-field" style="{{ $subscription->getCopyMethod() === 'fixed_quantity' ? '' : 'display: none;' }}">
                                    <label for="fixed_quantity">Fixed Quantity *</label>
                                    <input type="number" class="form-control" name="fixed_quantity" 
                                        id="fixed_quantity" value="{{ $subscription->getFixedQuantity() }}" 
                                        step="0.00000001" min="0.00000001">
                                </div>

                                <div class="form-group">
                                    <label for="min_quantity">Min Quantity (Optional)</label>
                                    <input type="number" class="form-control" name="min_quantity" 
                                        id="min_quantity" value="{{ $subscription->getMinQuantity() }}" 
                                        step="0.00000001" min="0">
                                </div>

                                <div class="form-group">
                                    <label for="max_quantity">Max Quantity (Optional)</label>
                                    <input type="number" class="form-control" name="max_quantity" 
                                        id="max_quantity" value="{{ $subscription->getMaxQuantity() }}" 
                                        step="0.00000001" min="0">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="max_position_size">Max Position Size (USD, Optional)</label>
                                <input type="number" class="form-control" name="max_position_size" 
                                    id="max_position_size" value="{{ $subscription->max_position_size }}" 
                                    step="0.01" min="0">
                            </div>

                            <button type="submit" class="btn btn-primary">Update Subscription</button>
                            <a href="{{ route('user.copy-trading.subscriptions.index') }}" class="btn btn-secondary">Cancel</a>
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
            } else {
                easySettings.style.display = 'none';
                advancedSettings.style.display = 'block';
            }
        });

        document.getElementById('method').addEventListener('change', function() {
            const method = this.value;
            const percentageField = document.getElementById('percentage-field');
            const fixedQuantityField = document.getElementById('fixed-quantity-field');
            
            if (method === 'percentage') {
                percentageField.style.display = 'block';
                fixedQuantityField.style.display = 'none';
            } else {
                percentageField.style.display = 'none';
                fixedQuantityField.style.display = 'block';
            }
        });
    </script>
@endsection

