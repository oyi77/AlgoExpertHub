@extends('trading-management::backend.trading-management.layout')

@section('submenu-content')
<div class="card">
    <div class="card-header">
        <h4>▶️ Create Backtest</h4>
    </div>
    <div class="card-body">
        <div class="alert alert-warning">
            <i class="fas fa-flask"></i> <strong>Backtest Your Strategies</strong><br>
            Test trading strategies on historical data before going live!
        </div>
        
        <form>
            <div class="form-group">
                <label>Strategy Name</label>
                <input type="text" class="form-control" placeholder="My Backtest Strategy">
            </div>
            
            <div class="form-group">
                <label>Symbol</label>
                <input type="text" class="form-control" placeholder="EUR/USD">
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="date" class="form-control">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>End Date</label>
                        <input type="date" class="form-control">
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label>Trading Preset</label>
                <select class="form-control">
                    <option>Select preset...</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Filter Strategy (Optional)</label>
                <select class="form-control">
                    <option>None</option>
                </select>
            </div>
            
            <button type="button" class="btn btn-primary" disabled>
                <i class="fas fa-play"></i> Run Backtest (Coming Soon)
            </button>
        </form>
    </div>
</div>
@endsection

