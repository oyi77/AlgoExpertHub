@extends('trading-management::backend.trading-management.layout')

@section('submenu-content')
<div class="card">
    <div class="card-header">
        <h4>👥 Browse Traders</h4>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <i class="fas fa-users"></i> <strong>Social Trading</strong><br>
            Discover and copy successful traders' strategies automatically!
        </div>
        
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <h5>Pro Trader #1</h5>
                        <p class="text-muted">Example Trader</p>
                        <div class="mb-2">
                            <span class="badge badge-success">Win Rate: 75%</span>
                            <span class="badge badge-primary">ROI: +45%</span>
                        </div>
                        <button class="btn btn-primary btn-sm" disabled>Copy (Coming Soon)</button>
                    </div>
                </div>
            </div>
        </div>
        
        <p class="text-center text-muted mt-4">Copy trading features coming soon!</p>
    </div>
</div>
@endsection

