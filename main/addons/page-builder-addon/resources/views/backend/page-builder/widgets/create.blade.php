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
                        <form action="{{ route('admin.page-builder.widgets.store') }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{ __('Name') }} <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{ __('Title') }} <span class="text-danger">*</span></label>
                                        <input type="text" name="title" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>{{ __('Description') }}</label>
                                <textarea name="description" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{ __('Category') }}</label>
                                        <select name="category" class="form-control">
                                            <option value="general">{{ __('General') }}</option>
                                            <option value="form">{{ __('Form') }}</option>
                                            <option value="media">{{ __('Media') }}</option>
                                            <option value="social">{{ __('Social') }}</option>
                                            <option value="navigation">{{ __('Navigation') }}</option>
                                            <option value="content">{{ __('Content') }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{ __('Icon') }}</label>
                                        <input type="text" name="icon" class="form-control" placeholder="feather icon name or class">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="form-check">
                                    <input type="checkbox" name="is_pro" value="1" class="form-check-input" id="is_pro">
                                    <label class="form-check-label" for="is_pro">{{ __('Pro Widget') }}</label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">{{ __('Create Widget') }}</button>
                            <a href="{{ route('admin.page-builder.widgets.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
