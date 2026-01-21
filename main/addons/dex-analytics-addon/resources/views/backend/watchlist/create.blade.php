@extends('backend.layout.master')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4>Add Trader</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.dex-analytics.watchlist.store') }}">
                    @csrf
                    <div class="form-group">
                        <label>Wallet Address</label>
                        <input type="text" name="wallet_address" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Platform</label>
                        <input type="text" name="platform" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <input type="text" name="status" class="form-control" value="active">
                    </div>
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea name="notes" class="form-control"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Save</button>
                    <a href="{{ route('admin.dex-analytics.watchlist.index') }}" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
