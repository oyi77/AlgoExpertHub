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
                            <a href="{{ route('admin.page-builder.layouts.edit', $layout->id) }}" class="btn btn-primary">
                                <i data-feather="edit"></i> {{ __('Edit') }}
                            </a>
                            <a href="{{ route('admin.page-builder.layouts.index') }}" class="btn btn-secondary">
                                <i data-feather="arrow-left"></i> {{ __('Back') }}
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <dl class="row">
                            <dt class="col-sm-3">{{ __('Name') }}</dt>
                            <dd class="col-sm-9">{{ $layout->name }}</dd>
                            
                            <dt class="col-sm-3">{{ __('Title') }}</dt>
                            <dd class="col-sm-9">{{ $layout->title ?? '-' }}</dd>
                            
                            <dt class="col-sm-3">{{ __('Type') }}</dt>
                            <dd class="col-sm-9"><span class="badge badge-info">{{ ucfirst($layout->type) }}</span></dd>
                            
                            <dt class="col-sm-3">{{ __('Description') }}</dt>
                            <dd class="col-sm-9">{{ $layout->description ?? '-' }}</dd>
                            
                            <dt class="col-sm-3">{{ __('Status') }}</dt>
                            <dd class="col-sm-9">
                                @if($layout->is_active)
                                    <span class="badge badge-success">{{ __('Active') }}</span>
                                @else
                                    <span class="badge badge-danger">{{ __('Inactive') }}</span>
                                @endif
                            </dd>
                            
                            <dt class="col-sm-3">{{ __('Default') }}</dt>
                            <dd class="col-sm-9">
                                @if($layout->is_default)
                                    <span class="badge badge-success">{{ __('Yes') }}</span>
                                @else
                                    <span class="badge badge-secondary">{{ __('No') }}</span>
                                @endif
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
