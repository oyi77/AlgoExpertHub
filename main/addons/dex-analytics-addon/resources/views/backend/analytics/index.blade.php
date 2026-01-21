@extends('backend.layout.master')

@section('content')
<div class="card">
    <div class="card-header">
        <h4>Analytics</h4>
    </div>
    <div class="card-body">
        <ul>
            <li><a href="{{ route('admin.dex-analytics.analytics.performance') }}">Performance</a></li>
            <li><a href="{{ route('admin.dex-analytics.analytics.pnl') }}">PnL</a></li>
            <li><a href="{{ route('admin.dex-analytics.analytics.positions') }}">Positions</a></li>
            <li><a href="{{ route('admin.dex-analytics.analytics.funding') }}">Funding</a></li>
            <li><a href="{{ route('admin.dex-analytics.analytics.liquidations') }}">Liquidations</a></li>
        </ul>
    </div>
</div>
@endsection
