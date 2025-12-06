@extends(Config::theme() . 'layout.auth')

@section('title')
    {{ $title }}
@endsection

@section('content')
<div class="sp_site_card">
    <div class="card-header">
        <div class="d-flex flex-wrap align-items-center justify-content-between">
            <h4>{{ __($title) }}</h4>
            <div>
                <a href="{{ route('user.trading-management.trading-bots.marketplace') }}" class="btn btn-sm btn-info me-2">
                    <i class="fa fa-store"></i> {{ __('Browse Templates') }}
                </a>
                <a href="{{ route('user.trading-management.trading-bots.index') }}" class="btn btn-sm btn-secondary">
                    <i class="fa fa-arrow-left"></i> {{ __('Go Back') }}
                </a>
            </div>
        </div>
    </div>
    <div class="card-body">
        {{-- Browse Templates Alert --}}
        <div class="alert alert-info mb-4">
            <i class="fa fa-lightbulb"></i> 
            <strong>Tip:</strong> Start from a prebuilt template with MA100, MA10, and PSAR indicators! 
            <a href="{{ route('user.trading-management.trading-bots.marketplace') }}" class="alert-link">Browse Templates →</a>
        </div>

        {{-- Demo Mode Badge --}}
        <div class="alert alert-warning mb-4">
            <i class="fa fa-exclamation-triangle"></i> <strong>Demo Mode:</strong> This bot will run in paper trading mode. No real money will be used.
        </div>

        <form action="{{ route('user.trading-management.trading-bots.store') }}" method="POST" id="bot-form">
            @csrf

            @include('trading-management::user.trading-bots.partials.form')

        </form>
    </div>
</div>

@push('script')
<script>
    // Form validation
    document.getElementById('bot-form').addEventListener('submit', function(e) {
        const connectionId = document.getElementById('exchange_connection_id').value;
        const presetId = document.getElementById('trading_preset_id').value;

        if (!connectionId || !presetId) {
            e.preventDefault();
            alert('Please select an exchange connection and trading preset.');
            return false;
        }
    });
</script>
@endpush
@endsection
