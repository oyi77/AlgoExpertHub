@extends('backend.layout.master')

@section('title')
    {{ $title }}
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{ $title }}</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.page-builder.widgets.update', $widget->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{ __('Name') }} <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control" value="{{ $widget->name }}" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{ __('Title') }} <span class="text-danger">*</span></label>
                                        <input type="text" name="title" class="form-control" value="{{ $widget->title }}" required>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>{{ __('Description') }}</label>
                                <textarea name="description" class="form-control" rows="2">{{ $widget->description }}</textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{ __('Category') }}</label>
                                        <select name="category" class="form-control">
                                            <option value="general" {{ $widget->category === 'general' ? 'selected' : '' }}>{{ __('General') }}</option>
                                            <option value="form" {{ $widget->category === 'form' ? 'selected' : '' }}>{{ __('Form') }}</option>
                                            <option value="media" {{ $widget->category === 'media' ? 'selected' : '' }}>{{ __('Media') }}</option>
                                            <option value="social" {{ $widget->category === 'social' ? 'selected' : '' }}>{{ __('Social') }}</option>
                                            <option value="navigation" {{ $widget->category === 'navigation' ? 'selected' : '' }}>{{ __('Navigation') }}</option>
                                            <option value="content" {{ $widget->category === 'content' ? 'selected' : '' }}>{{ __('Content') }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{ __('Icon') }}</label>
                                        <input type="text" name="icon" class="form-control" value="{{ $widget->icon }}" placeholder="feather icon name or class">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>{{ __('HTML Template') }}</label>
                                <textarea name="html_template" class="form-control" rows="10">{{ $widget->html_template }}</textarea>
                            </div>
                            <div class="form-group">
                                <label>{{ __('CSS Template') }}</label>
                                <textarea name="css_template" class="form-control" rows="10">{{ $widget->css_template }}</textarea>
                            </div>
                            <div class="form-group">
                                <div class="form-check">
                                    <input type="checkbox" name="is_pro" value="1" class="form-check-input" id="is_pro" {{ $widget->is_pro ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_pro">{{ __('Pro Widget') }}</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="form-check">
                                    <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" {{ $widget->is_active ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">{{ __('Active') }}</label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">{{ __('Update Widget') }}</button>
                            <a href="{{ route('admin.page-builder.widgets.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
