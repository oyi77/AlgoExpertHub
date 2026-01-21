@extends('frontend.layout.master')

@section('content')
<div class="card">
    <div class="card-header">
        <h4>DEX Analytics</h4>
    </div>
    <div class="card-body">
        <p>Total Traders: {{ $stats['total_traders'] ?? 0 }}</p>
        <p>Total PnL: {{ $stats['total_pnl'] ?? 0 }}</p>
    </div>
</div>
@endsection
