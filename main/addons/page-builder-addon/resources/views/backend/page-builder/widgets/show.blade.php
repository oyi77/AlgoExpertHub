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
                            <a href="{{ route('admin.page-builder.widgets.edit', $widget->id) }}" class="btn btn-primary">
                                <i data-feather="edit"></i> {{ __('Edit') }}
                            </a>
                            <a href="{{ route('admin.page-builder.widgets.index') }}" class="btn btn-secondary">
                                <i data-feather="arrow-left"></i> {{ __('Back') }}
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <dl class="row">
                            <dt class="col-sm-3">{{ __('Name') }}</dt>
                            <dd class="col-sm-9">{{ $widget->name }}</dd>
                            
                            <dt class="col-sm-3">{{ __('Title') }}</dt>
                            <dd class="col-sm-9">{{ $widget->title }}</dd>
                            
                            <dt class="col-sm-3">{{ __('Category') }}</dt>
                            <dd class="col-sm-9"><span class="badge badge-info">{{ ucfirst($widget->category) }}</span></dd>
                            
                            <dt class="col-sm-3">{{ __('Type') }}</dt>
                            <dd class="col-sm-9">
                                @if($widget->is_pro)
                                    <span class="badge badge-warning">{{ __('Pro') }}</span>
                                @else
                                    <span class="badge badge-success">{{ __('Free') }}</span>
                                @endif
                            </dd>
                            
                            <dt class="col-sm-3">{{ __('Description') }}</dt>
                            <dd class="col-sm-9">{{ $widget->description ?? '-' }}</dd>
                            
                            <dt class="col-sm-3">{{ __('Status') }}</dt>
                            <dd class="col-sm-9">
                                @if($widget->is_active)
                                    <span class="badge badge-success">{{ __('Active') }}</span>
                                @else
                                    <span class="badge badge-danger">{{ __('Inactive') }}</span>
                                @endif
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
