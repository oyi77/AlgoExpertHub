@extends('backend.layout.master')

@section('element')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0"><i class="fas fa-users"></i> Followers</h4>
            </div>
            <div class="card-body">
                @if($followers->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Follower</th>
                                <th>Email</th>
                                <th>Following Count</th>
                                <th>Total Subscriptions</th>
                                <th>Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($followers as $index => $item)
                            <tr>
                                <td>{{ $followers->firstItem() + $index }}</td>
                                <td><strong>{{ $item->follower->username ?? 'N/A' }}</strong></td>
                                <td>{{ $item->follower->email ?? 'N/A' }}</td>
                                <td>
                                    <span class="badge badge-success badge-lg">{{ $item->following_count }}</span>
                                </td>
                                <td>{{ $item->total_subscriptions }}</td>
                                <td>{{ $item->follower->created_at->format('Y-m-d') ?? 'N/A' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $followers->links() }}
                @else
                <div class="alert alert-info">No followers found.</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

