@extends('backend.layout.master')

@section('content')
<div class="card">
    <div class="card-header">
        <h4>Top Traders</h4>
    </div>
    <div class="card-body">
        <pre>{{ json_encode($leaderboard, JSON_PRETTY_PRINT) }}</pre>
    </div>
</div>
@endsection
