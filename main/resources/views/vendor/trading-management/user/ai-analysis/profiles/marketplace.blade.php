@extends(Config::theme() . 'layout.auth')

@section('content')
    <div class="row gy-4">
        <div class="col-12">
            <div class="sp_site_card">
                <div class="card-header">
                    <div class="d-flex flex-wrap align-items-center justify-content-between">
                        <h4>{{ __($title) }}</h4>
                        <a href="{{ route('user.ai-model-profiles.index') }}" class="btn btn-sm btn-secondary">
                            <i class="fa fa-arrow-left"></i> {{ __('My Profiles') }}
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if($profiles->count() > 0)
                        <div class="row">
                            @foreach($profiles as $profile)
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <h5>{{ $profile->name }}</h5>
                                        </div>
                                        <div class="card-body">
                                            <p class="text-muted">{{ Str::limit($profile->description ?? '-', 100) }}</p>
                                            <div class="mb-2">
                                                @if(isset($profile->mode))
                                                    <span class="badge badge-info">{{ $profile->mode }}</span>
                                                @endif
                                                @if($profile->enabled)
                                                    <span class="badge badge-success">{{ __('Enabled') }}</span>
                                                @else
                                                    <span class="badge badge-danger">{{ __('Disabled') }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="card-footer">
                                            <small class="text-muted">{{ __('Created') }}: {{ $profile->created_at->format('Y-m-d') }}</small>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @if ($profiles->hasPages())
                            <div class="mt-3">
                                {{ $profiles->links() }}
                            </div>
                        @endif
                    @else
                        <div class="text-center py-5">
                            <p>{{ __('No AI model profiles available in marketplace.') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
