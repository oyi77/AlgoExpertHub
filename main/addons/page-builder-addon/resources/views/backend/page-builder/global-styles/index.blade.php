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
                        <div>
                            <a href="{{ route('admin.page-builder.global-styles.css.compiled') }}" target="_blank" class="btn btn-info">
                                <i data-feather="download"></i> {{ __('View Compiled CSS') }}
                            </a>
                            <a href="{{ route('admin.page-builder.global-styles.create') }}" class="btn btn-primary">
                                <i data-feather="plus"></i> {{ __('Create Style') }}
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>{{ __('Name') }}</th>
                                        <th>{{ __('Type') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th>{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($styles as $style)
                                        <tr>
                                            <td>{{ $style->name }}</td>
                                            <td><span class="badge badge-info">{{ strtoupper($style->type) }}</span></td>
                                            <td>
                                                @if($style->is_active)
                                                    <span class="badge badge-success">{{ __('Active') }}</span>
                                                @else
                                                    <span class="badge badge-danger">{{ __('Inactive') }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('admin.page-builder.global-styles.edit', $style->id) }}" class="btn btn-sm btn-primary">
                                                    <i data-feather="edit"></i>
                                                </a>
                                                <form action="{{ route('admin.page-builder.global-styles.destroy', $style->id) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Are you sure?') }}')">
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
                                            <td colspan="4" class="text-center">{{ __('No global styles found') }}</td>
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
