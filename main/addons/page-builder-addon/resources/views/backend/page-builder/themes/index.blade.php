@extends('backend.layout.master')

@section('element')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">{{ $title }}</h4>
                </div>
                <div class="card-body">
                    <p>{{ __('Theme management integrated with page builder.') }}</p>
                    <a href="{{ route('admin.manage.theme') }}" class="btn btn-primary">{{ __('Manage Themes') }}</a>
                    <a href="{{ route('admin.page-builder.themes.edit') }}" class="btn btn-success">{{ __('Edit Theme Template') }}</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
