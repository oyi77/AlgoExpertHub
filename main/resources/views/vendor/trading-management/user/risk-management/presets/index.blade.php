@extends(Config::theme() . 'layout.auth')

@section('content')
    <div class="row gy-4">
        <div class="col-12">
            <div class="sp_site_card">
                <div class="card-header">
                    <div class="d-flex flex-wrap align-items-center justify-content-between">
                        <h4>{{ __($title) }}</h4>
                        <div>
                            <a href="{{ route('user.trading-presets.marketplace') }}" class="btn btn-sm btn-info">
                                <i class="fa fa-store"></i> {{ __('Marketplace') }}
                            </a>
                            <a href="{{ route('user.trading-presets.create') }}" class="btn btn-sm btn-primary">
                                <i class="fa fa-plus"></i> {{ __('Create Preset') }}
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if($presets->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>{{ __('Name') }}</th>
                                        <th>{{ __('Description') }}</th>
                                        <th>{{ __('Symbol') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th>{{ __('Created') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($presets as $preset)
                                        <tr>
                                            <td><strong>{{ $preset->name }}</strong></td>
                                            <td>{{ Str::limit($preset->description ?? '-', 50) }}</td>
                                            <td>{{ $preset->symbol ?? '-' }}</td>
                                            <td>
                                                @if($preset->enabled)
                                                    <span class="badge badge-success">{{ __('Enabled') }}</span>
                                                @else
                                                    <span class="badge badge-danger">{{ __('Disabled') }}</span>
                                                @endif
                                            </td>
                                            <td>{{ $preset->created_at->format('Y-m-d') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if ($presets->hasPages())
                            <div class="mt-3">
                                {{ $presets->links() }}
                            </div>
                        @endif
                    @else
                        <div class="text-center py-5">
                            <p>{{ __('No presets found.') }}</p>
                            <a href="{{ route('user.trading-presets.create') }}" class="btn btn-primary">
                                {{ __('Create your first preset') }}
                            </a>
                            <span class="mx-2">{{ __('or') }}</span>
                            <a href="{{ route('user.trading-presets.marketplace') }}" class="btn btn-info">
                                {{ __('browse marketplace') }}
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
