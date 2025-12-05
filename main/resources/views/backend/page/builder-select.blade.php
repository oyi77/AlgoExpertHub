@extends('backend.layout.master')

@section('element')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header site-card-header justify-content-between">
                <div class="card-header-left">
                    <h4>{{ __('Page Builder') }}</h4>
                </div>
                <div class="card-header-right">
                    <a href="{{ route('admin.frontend.pages') }}" class="btn btn-sm btn-secondary">
                        <i class="fa fa-arrow-left mr-2"></i>{{ __('Back to Pages') }}
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12">
                        <h5 class="mb-4">{{ __('Select a page to edit with Page Builder') }}</h5>
                        <div class="row">
                            @forelse($pages as $page)
                                <div class="col-md-4 mb-3">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <i class="fa fa-file-alt fa-3x mb-3 text-primary"></i>
                                            <h5>{{ $page->name }}</h5>
                                            <p class="text-muted mb-3">
                                                @if($page->widgets && $page->widgets->count() > 0)
                                                    {{ $page->widgets->count() }} {{ __('sections') }}
                                                @else
                                                    {{ __('No sections') }}
                                                @endif
                                            </p>
                                            <a href="{{ route('admin.page-builder.index', $page->id) }}" 
                                               class="btn btn-primary btn-block">
                                                <i class="fa fa-magic mr-2"></i>{{ __('Open in Page Builder') }}
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12">
                                    <div class="alert alert-info text-center">
                                        <i class="fa fa-info-circle mr-2"></i>
                                        {{ __('No pages found.') }}
                                        <a href="{{ route('admin.frontend.pages.create') }}" class="ml-2">
                                            {{ __('Create a new page') }}
                                        </a>
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
