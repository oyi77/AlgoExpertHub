@extends(\App\Helpers\Helper\Helper::theme().'layout.auth')

@section('title', 'Dashboard - ' . (Config::config()->appname ?? 'AlgoExpertHub'))
@section('page_title', 'Dashboard')

@section('content')
    <!-- Stats Grid -->
    <div class="tv-stats-grid">
        <!-- Balance -->
        <div class="tv-stat-card">
            <div class="tv-stat-label">Balance</div>
            <div class="tv-stat-value">${{ number_format(auth()->user()->balance ?? 0, 2) }}</div>
        </div>
        
        <!-- Active Plan -->
        <div class="tv-stat-card">
            <div class="tv-stat-label">Active Plan</div>
            <div class="tv-stat-value">
                @if(auth()->user()->currentplan()->exists())
                    {{ auth()->user()->currentplan->first()->plan->name ?? 'N/A' }}
                @else
                    No Plan
                @endif
            </div>
        </div>
        
        <!-- Total Signals -->
        <div class="tv-stat-card">
            <div class="tv-stat-label">Total Signals</div>
            <div class="tv-stat-value">
                @if(auth()->user()->currentplan()->exists())
                    {{ auth()->user()->currentplan->first()->plan->signals()->count() ?? 0 }}
                @else
                    0
                @endif
            </div>
        </div>
        
        <!-- Referrals -->
        <div class="tv-stat-card">
            <div class="tv-stat-label">Referrals</div>
            <div class="tv-stat-value">{{ auth()->user()->refferals()->count() ?? 0 }}</div>
        </div>
    </div>
    
    <!-- Recent Signals -->
    @if(Route::has('user.signal.all'))
        <div class="tv-card">
            <div class="tv-card-header">
                <h3 class="tv-card-title">Recent Signals</h3>
                <a href="{{ route('user.signal.all') }}" class="tv-btn tv-btn-outline">View All</a>
            </div>
            <div class="tv-card-body">
                @php
                    $recentSignals = auth()->user()->currentplan()->exists() 
                        ? auth()->user()->currentplan->first()->plan->signals()->where('is_published', 1)->latest()->take(5)->get()
                        : collect();
                @endphp
                
                @if($recentSignals->count() > 0)
                    <table class="tv-table">
                        <thead>
                            <tr>
                                <th>Pair</th>
                                <th>Direction</th>
                                <th>Entry</th>
                                <th>SL</th>
                                <th>TP</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentSignals as $signal)
                                <tr>
                                    <td><strong>{{ $signal->pair->name ?? 'N/A' }}</strong></td>
                                    <td>
                                        <span class="tv-badge tv-badge-{{ strtolower($signal->direction) == 'buy' ? 'success' : 'danger' }}">
                                            {{ strtoupper($signal->direction) }}
                                        </span>
                                    </td>
                                    <td>{{ $signal->open_price }}</td>
                                    <td>{{ $signal->sl }}</td>
                                    <td>{{ $signal->tp }}</td>
                                    <td>{{ $signal->published_date ? $signal->published_date->format('M d, Y') : 'N/A' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p style="text-align: center; color: var(--tv-text-secondary); padding: 2rem;">
                        No signals available. <a href="{{ route('user.plans') }}" style="color: var(--tv-primary);">Subscribe to a plan</a> to access signals.
                    </p>
                @endif
            </div>
        </div>
    @endif
@endsection

