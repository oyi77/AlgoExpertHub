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
                    <form action="{{ route('admin.page-builder.templates.store') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label>{{ __('Template Name') }}</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>{{ __('Description') }}</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label>{{ __('Category') }}</label>
                            <select name="category" class="form-control">
                                <option value="general">{{ __('General') }}</option>
                                <option value="landing">{{ __('Landing Page') }}</option>
                                <option value="blog">{{ __('Blog') }}</option>
                                <option value="contact">{{ __('Contact') }}</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>{{ __('Status') }}</label>
                            <select name="status" class="form-control">
                                <option value="1">{{ __('Active') }}</option>
                                <option value="0">{{ __('Inactive') }}</option>
                            </select>
                        </div>
                        <div class="alert alert-info">
                            <p>{{ __('After creating the template, you can edit it in the page builder to add content.') }}</p>
                        </div>
                        <button type="submit" class="btn btn-primary">{{ __('Create Template') }}</button>
                        <a href="{{ route('admin.page-builder.templates.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
