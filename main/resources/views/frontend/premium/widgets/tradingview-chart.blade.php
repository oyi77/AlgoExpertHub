{{-- TradingView Chart Widget --}}
@props(['symbol' => 'BTCUSD', 'interval' => 'D', 'theme' => 'light'])

<div class="tradingview-widget">
    <div class="tradingview-widget-container" id="tradingview_{{ Str::random(8) }}">
        <div id="tradingview_chart_{{ $symbol }}"></div>
    </div>
</div>

@push('scripts')
<script src="https://s3.tradingview.com/tv.js"></script>
<script>
    new TradingView.widget({
        "autosize": true,
        "symbol": "{{ $symbol }}",
        "interval": "{{ $interval }}",
        "timezone": "Etc/UTC",
        "theme": "{{ $theme }}",
        "style": "1",
        "locale": "en",
        "toolbar_bg": "#f1f3f6",
        "enable_publishing": false,
        "allow_symbol_change": true,
        "container_id": "tradingview_chart_{{ $symbol }}"
    });
</script>
@endpush

