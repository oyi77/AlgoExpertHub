@extends(Config::theme() . 'layout.auth')

@section('content')
    <div class="row gy-4">
        <div class="col-12">
            <div class="sp_site_card">
                <div class="card-header">
                    <div class="d-flex flex-wrap align-items-center justify-content-between">
                        <h4>{{ __($title) }}</h4>
                        <a href="{{ route('user.trading-presets.index') }}" class="btn btn-sm btn-secondary">
                            <i class="fa fa-arrow-left"></i> {{ __('My Presets') }}
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if($presets->count() > 0)
                        <div class="row">
                            @foreach($presets as $preset)
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <h5>{{ $preset->name }}</h5>
                                        </div>
                                        <div class="card-body">
                                            <p class="text-muted">{{ Str::limit($preset->description ?? '-', 100) }}</p>
                                            <div class="mb-2">
                                                <strong>{{ __('Symbol') }}:</strong> {{ $preset->symbol ?? __('All') }}
                                            </div>
                                            @if($preset->tags)
                                                <div class="mb-2">
                                                    @foreach(array_slice($preset->tags, 0, 3) as $tag)
                                                        <span class="badge badge-secondary">{{ $tag }}</span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                        <div class="card-footer">
                                            <small class="text-muted">{{ __('Created') }}: {{ $preset->created_at->format('Y-m-d') }}</small>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @if ($presets->hasPages())
                            <div class="mt-3">
                                {{ $presets->links() }}
                            </div>
                        @endif
                    @else
                        <div class="text-center py-5">
                            <p>{{ __('No presets available in marketplace.') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
