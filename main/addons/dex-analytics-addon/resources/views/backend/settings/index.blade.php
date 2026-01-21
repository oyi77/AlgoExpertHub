@extends('backend.layout.master')

@section('content')
<div class="card">
    <div class="card-header">
        <h4>Settings</h4>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.dex-analytics.settings.update') }}">
            @csrf
            <div class="form-group">
                <label>Polling Interval (seconds)</label>
                <input type="number" name="polling[interval_seconds]" class="form-control" value="{{ $config['polling']['interval_seconds'] ?? 60 }}">
            </div>
            <div class="form-group">
                <label>Refresh Interval (seconds)</label>
                <input type="number" name="polling[refresh_interval_seconds]" class="form-control" value="{{ $config['polling']['refresh_interval_seconds'] ?? 300 }}">
            </div>
            <button type="submit" class="btn btn-primary">Save</button>
        </form>
    </div>
</div>
@endsection
