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
                        <form action="{{ route('admin.page-builder.layouts.update', $layout->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="form-group">
                                <label>{{ __('Name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ $layout->name }}" required>
                            </div>
                            <div class="form-group">
                                <label>{{ __('Title') }}</label>
                                <input type="text" name="title" class="form-control" value="{{ $layout->title }}">
                            </div>
                            <div class="form-group">
                                <label>{{ __('Type') }}</label>
                                <select name="type" class="form-control">
                                    <option value="full" {{ $layout->type === 'full' ? 'selected' : '' }}>{{ __('Full Page') }}</option>
                                    <option value="header" {{ $layout->type === 'header' ? 'selected' : '' }}>{{ __('Header') }}</option>
                                    <option value="footer" {{ $layout->type === 'footer' ? 'selected' : '' }}>{{ __('Footer') }}</option>
                                    <option value="sidebar" {{ $layout->type === 'sidebar' ? 'selected' : '' }}>{{ __('Sidebar') }}</option>
                                    <option value="content" {{ $layout->type === 'content' ? 'selected' : '' }}>{{ __('Content') }}</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>{{ __('Description') }}</label>
                                <textarea name="description" class="form-control" rows="3">{{ $layout->description }}</textarea>
                            </div>
                            <div class="form-group">
                                <div class="form-check">
                                    <input type="checkbox" name="is_default" value="1" class="form-check-input" id="is_default" {{ $layout->is_default ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_default">{{ __('Set as Default') }}</label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="form-check">
                                    <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" {{ $layout->is_active ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">{{ __('Active') }}</label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">{{ __('Update Layout') }}</button>
                            <a href="{{ route('admin.page-builder.layouts.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
