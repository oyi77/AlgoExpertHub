@extends('frontend.layout.master')

@section('content')
<div class="card">
    <div class="card-header">
        <h4>My Watchlist</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Wallet</th>
                        <th>Platform</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($watchlist as $item)
                        <tr>
                            <td>{{ $item->wallet_address }}</td>
                            <td>{{ $item->platform }}</td>
                            <td>{{ $item->status }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3">No traders assigned.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $watchlist->links() }}
    </div>
</div>
@endsection
