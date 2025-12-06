@extends('backend.layout.master')

@section('title')
    {{ $title }}
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title">{{ $title }}</h4>
                        <a href="{{ route('admin.page-builder.widgets.create') }}" class="btn btn-primary">
                            <i data-feather="plus"></i> {{ __('Create Widget') }}
                        </a>
                    </div>
                    <div class="card-body">
                        @if(count($categories) > 0)
                            <div class="mb-3">
                                <a href="{{ route('admin.page-builder.widgets.index') }}" class="btn btn-sm {{ $selectedCategory === 'all' ? 'btn-primary' : 'btn-outline-primary' }}">
                                    {{ __('All') }}
                                </a>
                                @foreach($categories as $category)
                                    <a href="{{ route('admin.page-builder.widgets.index', ['category' => $category]) }}" class="btn btn-sm {{ $selectedCategory === $category ? 'btn-primary' : 'btn-outline-primary' }}">
                                        {{ ucfirst($category) }}
                                    </a>
                                @endforeach
                            </div>
                        @endif
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>{{ __('Widget') }}</th>
                                        <th>{{ __('Category') }}</th>
                                        <th>{{ __('Type') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th>{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($widgets as $widget)
                                        <tr>
                                            <td>
                                                <strong>{{ $widget->title }}</strong>
                                                @if($widget->description)
                                                    <br><small class="text-muted">{{ \Illuminate\Support\Str::limit($widget->description, 50) }}</small>
                                                @endif
                                            </td>
                                            <td><span class="badge badge-info">{{ ucfirst($widget->category) }}</span></td>
                                            <td>
                                                @if($widget->is_pro)
                                                    <span class="badge badge-warning">{{ __('Pro') }}</span>
                                                @else
                                                    <span class="badge badge-success">{{ __('Free') }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($widget->is_active)
                                                    <span class="badge badge-success">{{ __('Active') }}</span>
                                                @else
                                                    <span class="badge badge-danger">{{ __('Inactive') }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('admin.page-builder.widgets.edit', $widget->id) }}" class="btn btn-sm btn-primary">
                                                    <i data-feather="edit"></i>
                                                </a>
                                                <form action="{{ route('admin.page-builder.widgets.destroy', $widget->id) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Are you sure?') }}')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i data-feather="trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center">{{ __('No widgets found') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
