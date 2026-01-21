@extends('backend.layout.master')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <h4>Watchlist</h4>
                <a href="{{ route('admin.dex-analytics.watchlist.create') }}" class="btn btn-primary">Add Trader</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Wallet</th>
                                <th>Platform</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($watchlist as $item)
                                <tr>
                                    <td>{{ $item->wallet_address }}</td>
                                    <td>{{ $item->platform }}</td>
                                    <td>{{ $item->status }}</td>
                                    <td>
                                        <a href="{{ route('admin.dex-analytics.watchlist.edit', $item->id) }}" class="btn btn-sm btn-info">Edit</a>
                                        <form method="POST" action="{{ route('admin.dex-analytics.watchlist.destroy', $item->id) }}" style="display:inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4">No traders found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $watchlist->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
