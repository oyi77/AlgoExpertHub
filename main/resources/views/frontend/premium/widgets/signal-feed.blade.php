{{-- Real-time Signal Feed Widget --}}
@props(['signals' => []])

<div class="analytics-widget">
    <h3 class="widget-title">Live Signal Feed</h3>
    <div class="signal-feed">
        @forelse($signals as $signal)
            <div class="signal-feed-item">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="signal-badge-premium {{ strtolower($signal->direction) }}">
                            {{ strtoupper($signal->direction) }}
                        </span>
                        <strong class="ms-2">{{ $signal->pair->name ?? 'N/A' }}</strong>
                    </div>
                    <div class="text-end">
                        <small class="text-muted">{{ $signal->published_date?->diffForHumans() }}</small>
                    </div>
                </div>
                <div class="mt-2">
                    <small>Entry: {{ $signal->open_price }} | SL: {{ $signal->sl }} | TP: {{ $signal->tp }}</small>
                </div>
            </div>
        @empty
            <div class="text-center text-muted py-4">
                <p>No recent signals. Subscribe to a plan to receive signals.</p>
            </div>
        @endforelse
    </div>
</div>

