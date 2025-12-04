@extends('backend.layout.master')

@section('element')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0"><i class="fas fa-user-tie"></i> Traders (Ranked by Followers)</h4>
            </div>
            <div class="card-body">
                @if($traders->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Trader</th>
                                <th>Email</th>
                                <th>Total Followers</th>
                                <th>Total Subscriptions</th>
                                <th>Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($traders as $index => $item)
                            <tr>
                                <td>{{ $traders->firstItem() + $index }}</td>
                                <td><strong>{{ $item->trader->username ?? 'N/A' }}</strong></td>
                                <td>{{ $item->trader->email ?? 'N/A' }}</td>
                                <td>
                                    <span class="badge badge-primary badge-lg">{{ $item->follower_count }}</span>
                                </td>
                                <td>{{ $item->total_subscriptions }}</td>
                                <td>{{ $item->trader->created_at->format('Y-m-d') ?? 'N/A' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $traders->links() }}
                @else
                <div class="alert alert-info">No traders found.</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

