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
                        <form action="{{ route('admin.page-builder.layouts.store') }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <label>{{ __('Name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>{{ __('Title') }}</label>
                                <input type="text" name="title" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>{{ __('Type') }}</label>
                                <select name="type" class="form-control">
                                    <option value="full">{{ __('Full Page') }}</option>
                                    <option value="header">{{ __('Header') }}</option>
                                    <option value="footer">{{ __('Footer') }}</option>
                                    <option value="sidebar">{{ __('Sidebar') }}</option>
                                    <option value="content">{{ __('Content') }}</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>{{ __('Description') }}</label>
                                <textarea name="description" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="form-group">
                                <div class="form-check">
                                    <input type="checkbox" name="is_default" value="1" class="form-check-input" id="is_default">
                                    <label class="form-check-label" for="is_default">{{ __('Set as Default') }}</label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">{{ __('Create Layout') }}</button>
                            <a href="{{ route('admin.page-builder.layouts.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
