@extends('backend.layout.master')

@section('element')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">{{ $title }}</h4>
                    <a href="{{ route('admin.page-builder.create') }}" class="btn btn-primary float-right">
                        <i data-feather="plus"></i> {{ __('Create Page') }}
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>{{ __('Name') }}</th>
                                    <th>{{ __('Slug') }}</th>
                                    <th>{{ __('Order') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pages as $page)
                                    <tr>
                                        <td>{{ $page->name }}</td>
                                        <td>{{ $page->slug }}</td>
                                        <td>{{ $page->order }}</td>
                                        <td>
                                            @if($page->status)
                                                <span class="badge badge-success">{{ __('Active') }}</span>
                                            @else
                                                <span class="badge badge-danger">{{ __('Inactive') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.page-builder.edit', $page->id) }}" class="btn btn-sm btn-primary">
                                                <i data-feather="edit-3"></i> {{ __('Edit in Builder') }}
                                            </a>
                                            <a href="{{ route('admin.frontend.pages.edit', $page->id) }}" class="btn btn-sm btn-outline-secondary">
                                                <i data-feather="edit"></i> {{ __('Legacy Edit') }}
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">{{ __('No pages found') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $pages->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
