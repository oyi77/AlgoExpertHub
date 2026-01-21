@extends('frontend.layout.master')

@section('content')
<div class="card">
    <div class="card-header">
        <h4>Trader Analytics</h4>
    </div>
    <div class="card-body">
        <p><strong>Wallet:</strong> {{ $watchlist->wallet_address }}</p>
        <p><strong>Platform:</strong> {{ $watchlist->platform }}</p>
        <pre>{{ json_encode($metrics, JSON_PRETTY_PRINT) }}</pre>
        <pre>{{ json_encode($heatmap, JSON_PRETTY_PRINT) }}</pre>
    </div>
</div>
@endsection
