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
            {{-- Search and Filter --}}
            <form action="{{ route('user.filter-strategies.marketplace') }}" method="get" class="mb-4">
                <div class="row">
                    <div class="col-md-8">
                        <div class="input-group">
                            <input type="text" 
                                   class="form-control" 
                                   name="search" 
                                   placeholder="{{ __('Search strategies...') }}" 
                                   value="{{ request('search') }}">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fa fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <select name="sort" class="form-control" onchange="this.form.submit()">
                            <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>{{ __('Newest First') }}</option>
                            <option value="name" {{ request('sort') == 'name' ? 'selected' : '' }}>{{ __('Name A-Z') }}</option>
                            <option value="popular" {{ request('sort') == 'popular' ? 'selected' : '' }}>{{ __('Most Popular') }}</option>
                        </select>
                    </div>
                </div>
            </form>

            {{-- Strategies List --}}
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Description') }}</th>
                            <th>{{ __('Owner') }}</th>
                            <th>{{ __('Linked Presets') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($strategies as $strategy)
                            <tr>
                                <td>
                                    <strong>{{ $strategy->name }}</strong>
                                </td>
                                <td>
                                    <span class="text-muted">
                                        {{ Str::limit($strategy->description ?? '-', 50) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="text-muted">{{ $strategy->owner->username ?? __('System') }}</span>
                                </td>
                                <td>
                                    <span class="badge badge-info">{{ $strategy->trading_presets_count ?? 0 }}</span>
                                </td>
                                <td>
                                    <span class="badge badge-{{ $strategy->enabled ? 'success' : 'danger' }}">
                                        {{ $strategy->enabled ? __('Enabled') : __('Disabled') }}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('user.filter-strategies.show', $strategy->id) }}" 
                                           class="btn btn-outline-info btn-sm">
                                            <i class="fa fa-eye"></i> {{ __('View') }}
                                        </a>
                                        @if($strategy->canBeClonedBy(auth()->id()))
                                            <form action="{{ route('user.filter-strategies.clone', $strategy->id) }}" 
                                                  method="POST" 
                                                  class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-success btn-sm">
                                                    <i class="fa fa-copy"></i> {{ __('Clone') }}
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="text-center" colspan="100%">
                                    {{ __('No public filter strategies found.') }}
                                    <a href="{{ route('user.filter-strategies.index') }}">{{ __('View my strategies') }}</a>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($strategies->hasPages())
                <div class="mt-3">
                    {{ $strategies->links() }}
                </div>
            @endif
            </div>
        </div>
    </div>
@endsection
