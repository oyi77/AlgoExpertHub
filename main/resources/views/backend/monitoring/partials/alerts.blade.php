@if(isset($alerts) && count($alerts) > 0)
    @foreach($alerts as $alert)
        <div class="alert-banner alert-{{ $alert['severity'] }}">
            <strong>{{ strtoupper($alert['severity']) }}:</strong> {{ $alert['message'] }}
            <br><small>Current: {{ $alert['value'] }} | Threshold: {{ $alert['threshold'] }}</small>
        </div>
    @endforeach
@else
    <div class="alert alert-info">No active alerts</div>
@endif

