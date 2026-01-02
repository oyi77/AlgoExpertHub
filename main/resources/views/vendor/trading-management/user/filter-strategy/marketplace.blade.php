@extends(Config::theme() . 'layout.auth')

@section('content')
    <div class="row gy-4">
        <div class="col-12">
            <div class="sp_site_card">
                <div class="card-header">
                    <div class="d-flex flex-wrap align-items-center justify-content-between">
                        <h4>{{ __($title) }}</h4>
                        <a href="{{ route('user.filter-strategies.index') }}" class="btn btn-sm btn-secondary">
                            <i class="fa fa-arrow-left"></i> {{ __('My Strategies') }}
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if($strategies->count() > 0)
                        <div class="row">
                            @foreach($strategies as $strategy)
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <h5>{{ $strategy->name }}</h5>
                                        </div>
                                        <div class="card-body">
                                            <p class="text-muted">{{ Str::limit($strategy->description ?? '-', 100) }}</p>
                                            <div class="mb-2">
                                                @if($strategy->enabled)
                                                    <span class="badge badge-success">{{ __('Enabled') }}</span>
                                                @else
                                                    <span class="badge badge-danger">{{ __('Disabled') }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="card-footer">
                                            <small class="text-muted">{{ __('Created') }}: {{ $strategy->created_at->format('Y-m-d') }}</small>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @if ($strategies->hasPages())
                            <div class="mt-3">
                                {{ $strategies->links() }}
                            </div>
                        @endif
                    @else
                        <div class="text-center py-5">
                            <p>{{ __('No filter strategies available in marketplace.') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
