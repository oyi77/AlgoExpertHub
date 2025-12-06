@extends('backend.layout.master')

@section('element')
<div class="row">
    <div class="col-12">
        <!-- Page Header -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3><i class="fas fa-store"></i> Bot Marketplace (Admin Moderation)</h3>
                        <p class="text-muted mb-0">Review and moderate bot templates submitted by users</p>
                    </div>
                    <a href="{{ route('admin.trading-management.trading-bots.index') }}" class="btn btn-secondary">
                        <i class="fa fa-arrow-left"></i> Back to Bots
                    </a>
                </div>
            </div>
        </div>

        <!-- Type Tabs -->
        <div class="card mb-3">
            <div class="card-body">
                <ul class="nav nav-pills">
                    <li class="nav-item">
                        <a class="nav-link {{ $type == 'bot' ? 'active' : '' }}" href="?type=bot">
                            <i class="fas fa-robot"></i> Trading Bots
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $type == 'signal' ? 'active' : '' }}" href="?type=signal">
                            <i class="fas fa-signal"></i> Signal Sources
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $type == 'complete' ? 'active' : '' }}" href="?type=complete">
                            <i class="fas fa-check-circle"></i> Complete Bots
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Templates List -->
        <div class="card">
            <div class="card-body">
                @if($templates->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Author</th>
                                    <th>Status</th>
                                    <th>Rating</th>
                                    <th>Clones</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($templates as $template)
                                    <tr>
                                        <td>
                                            <strong>{{ $template->name }}</strong><br>
                                            <small class="text-muted">{{ Str::limit($template->description ?? '', 50) }}</small>
                                        </td>
                                        <td>
                                            @if($template->user_id)
                                                <a href="{{ route('admin.user.details', $template->user_id) }}">
                                                    {{ $template->user->username ?? 'Unknown' }}
                                                </a>
                                            @else
                                                <span class="badge badge-info">Admin</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($template->is_approved)
                                                <span class="badge badge-success">Approved</span>
                                            @else
                                                <span class="badge badge-warning">Pending</span>
                                            @endif
                                            @if($template->is_featured ?? false)
                                                <span class="badge badge-primary">Featured</span>
                                            @endif
                                        </td>
                                        <td>
                                            <i class="fas fa-star text-warning"></i> 
                                            {{ number_format($template->rating ?? 0, 1) }}
                                            <small class="text-muted">({{ $template->rating_count ?? 0 }})</small>
                                        </td>
                                        <td>{{ $template->clone_count ?? 0 }}</td>
                                        <td>{{ $template->created_at->format('Y-m-d') }}</td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('admin.trading-management.marketplace.bots.show', ['id' => $template->id, 'type' => $type]) }}" 
                                                   class="btn btn-info" title="View">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                @if(!$template->is_approved)
                                                    <form action="{{ route('admin.trading-management.marketplace.bots.approve', $template->id) }}" 
                                                          method="POST" style="display: inline;">
                                                        @csrf
                                                        <button type="submit" class="btn btn-success" title="Approve">
                                                            <i class="fa fa-check"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                                <form action="{{ route('admin.trading-management.marketplace.bots.feature', $template->id) }}" 
                                                      method="POST" style="display: inline;">
                                                    @csrf
                                                    <button type="submit" class="btn btn-primary" title="{{ $template->is_featured ?? false ? 'Unfeature' : 'Feature' }}">
                                                        <i class="fa fa-star"></i>
                                                    </button>
                                                </form>
                                                <form action="{{ route('admin.trading-management.marketplace.bots.destroy', $template->id) }}" 
                                                      method="POST" style="display: inline;" 
                                                      onsubmit="return confirm('Delete this template?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger" title="Delete">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $templates->appends(['type' => $type])->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-store fa-3x text-muted mb-3"></i>
                        <h5>No Templates Found</h5>
                        <p class="text-muted">No {{ $type }} templates have been submitted yet.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

