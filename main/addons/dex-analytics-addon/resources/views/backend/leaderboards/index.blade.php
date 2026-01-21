@extends('backend.layout.master')

@section('content')
<div class="card">
    <div class="card-header">
        <h4>Leaderboards</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Wallet</th>
                        <th>Platform</th>
                        <th>Confidence</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leaderboard as $entry)
                        <tr>
                            <td>{{ $entry['rank'] }}</td>
                            <td>{{ $entry['wallet_address'] }}</td>
                            <td>{{ $entry['platform'] }}</td>
                            <td>{{ $entry['confidence_score'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">No leaderboard data.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
