@extends('backend.layout.master')

@section('content')
<div class="card">
    <div class="card-header">
        <h4>AI Insights</h4>
    </div>
    <div class="card-body">
        <ul>
            <li><a href="{{ route('admin.dex-analytics.ai-insights.clustering') }}">Clustering</a></li>
            <li><a href="{{ route('admin.dex-analytics.ai-insights.crowded-trades') }}">Crowded Trades</a></li>
            <li><a href="{{ route('admin.dex-analytics.ai-insights.regime') }}">Regime</a></li>
        </ul>
    </div>
</div>
@endsection
