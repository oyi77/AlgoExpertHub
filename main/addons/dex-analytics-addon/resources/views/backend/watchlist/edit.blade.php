@extends('backend.layout.master')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4>Edit Trader</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.dex-analytics.watchlist.update', $watchlist->id) }}">
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <label>Wallet Address</label>
                        <input type="text" name="wallet_address" class="form-control" value="{{ $watchlist->wallet_address }}" required>
                    </div>
                    <div class="form-group">
                        <label>Platform</label>
                        <input type="text" name="platform" class="form-control" value="{{ $watchlist->platform }}" required>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <input type="text" name="status" class="form-control" value="{{ $watchlist->status }}">
                    </div>
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea name="notes" class="form-control">{{ $watchlist->notes }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Update</button>
                    <a href="{{ route('admin.dex-analytics.watchlist.index') }}" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
