@extends('backend.layout.master')

@section('content')
<div class="card">
    <div class="card-header">
        <h4>Trader Analytics</h4>
    </div>
    <div class="card-body">
        @if($watchlist)
            <p><strong>Wallet:</strong> {{ $watchlist->wallet_address }}</p>
            <p><strong>Platform:</strong> {{ $watchlist->platform }}</p>
        @endif

        <h5>Metrics</h5>
        <pre>{{ json_encode($metrics, JSON_PRETTY_PRINT) }}</pre>

        <h5>PnL Heatmap</h5>
        <pre>{{ json_encode($heatmap, JSON_PRETTY_PRINT) }}</pre>
    </div>
</div>
@endsection
