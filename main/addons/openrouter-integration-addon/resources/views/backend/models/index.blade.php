@extends('backend.layout.master')

@section('element')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header site-card-header justify-content-between">
                    <div class="card-header-left">
                        <form action="{{ route('admin.openrouter.models.index') }}" method="get" class="form-inline">
                            <div class="input-group me-2">
                                <input type="text" name="search" class="form-control form-control-sm" 
                                    placeholder="Search models..." value="{{ request('search') }}">
                            </div>
                            <div class="input-group me-2">
                                <select name="provider" class="form-control form-control-sm">
                                    <option value="">All Providers</option>
                                    @foreach ($providers as $provider)
                                        <option value="{{ $provider }}" {{ request('provider') == $provider ? 'selected' : '' }}>
                                            {{ ucfirst($provider) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <button class="btn btn-sm btn-primary" type="submit">
                                <i class="fa fa-search"></i> Filter
                            </button>
                        </form>
                    </div>
                    <div class="card-header-right">
                        <form action="{{ route('admin.openrouter.models.sync') }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Sync models from OpenRouter API?');">
                                <i class="fa fa-sync"></i> {{ __('Sync Models') }}
                            </button>
                        </form>
                        <a href="{{ route('admin.openrouter.configurations.index') }}" class="btn btn-sm btn-secondary">
                            <i class="fa fa-cog"></i> {{ __('Configurations') }}
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table student-data-table m-t-20">
                            <thead>
                                <tr>
                                    <th>{{ __('SL') }}</th>
                                    <th>{{ __('Model ID') }}</th>
                                    <th>{{ __('Name') }}</th>
                                    <th>{{ __('Provider') }}</th>
                                    <th>{{ __('Context Length') }}</th>
                                    <th>{{ __('Pricing') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Last Synced') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($models as $model)
                                    <tr>
                                        <td>{{ $loop->iteration + ($models->currentPage() - 1) * $models->perPage() }}</td>
                                        <td>
                                            <small class="text-monospace">{{ $model->model_id }}</small>
                                        </td>
                                        <td>{{ $model->name }}</td>
                                        <td>
                                            <span class="badge badge-secondary">{{ ucfirst($model->provider) }}</span>
                                        </td>
                                        <td>
                                            @if($model->context_length)
                                                {{ number_format($model->context_length) }} tokens
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $model->pricing_string }}</small>
                                        </td>
                                        <td>
                                            @if($model->is_available)
                                                <span class="badge badge-success">Available</span>
                                            @else
                                                <span class="badge badge-danger">Unavailable</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($model->last_synced_at)
                                                {{ $model->last_synced_at->diffForHumans() }}
                                            @else
                                                <span class="text-muted">Never</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-center" colspan="100%">
                                            {{ __('No Models Found') }}
                                            <br>
                                            <small class="text-muted">Click "Sync Models" to fetch available models from OpenRouter</small>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($models->hasPages())
                    <div class="card-footer">
                        {{ $models->appends(request()->query())->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

