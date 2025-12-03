{{-- Performance Heatmap Widget --}}
@props(['data' => []])

<div class="analytics-widget">
    <h3 class="widget-title">Performance Heatmap</h3>
    <div class="performance-heatmap">
        @foreach($data as $item)
            <div class="heatmap-cell {{ $item['change'] >= 0 ? 'heatmap-positive' : 'heatmap-negative' }}"
                 title="{{ $item['symbol'] }}: {{ $item['change'] }}%">
                <div class="heatmap-symbol">{{ $item['symbol'] }}</div>
                <div class="heatmap-value">{{ number_format($item['change'], 2) }}%</div>
            </div>
        @endforeach
    </div>
</div>

@if(empty($data))
    @php
        // Sample data for demonstration
        $sampleData = [
            ['symbol' => 'EUR/USD', 'change' => 2.5],
            ['symbol' => 'GBP/USD', 'change' => -1.2],
            ['symbol' => 'BTC/USD', 'change' => 5.8],
            ['symbol' => 'ETH/USD', 'change' => 3.4],
            ['symbol' => 'XAU/USD', 'change' => -0.5],
            ['symbol' => 'USD/JPY', 'change' => 1.8],
        ];
    @endphp
    
    <div class="performance-heatmap">
        @foreach($sampleData as $item)
            <div class="heatmap-cell {{ $item['change'] >= 0 ? 'heatmap-positive' : 'heatmap-negative' }}"
                 title="{{ $item['symbol'] }}: {{ $item['change'] }}%">
                <div class="heatmap-symbol">{{ $item['symbol'] }}</div>
                <div class="heatmap-value">{{ number_format($item['change'], 2) }}%</div>
            </div>
        @endforeach
    </div>
@endif

