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
                        <form action="{{ route('admin.page-builder.global-styles.update', $style->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="form-group">
                                <label>{{ __('Name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ $style->name }}" required>
                            </div>
                            <div class="form-group">
                                <label>{{ __('Type') }}</label>
                                <select name="type" class="form-control">
                                    <option value="css" {{ $style->type === 'css' ? 'selected' : '' }}>CSS</option>
                                    <option value="scss" {{ $style->type === 'scss' ? 'selected' : '' }}>SCSS</option>
                                    <option value="less" {{ $style->type === 'less' ? 'selected' : '' }}>LESS</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>{{ __('CSS Content') }} <span class="text-danger">*</span></label>
                                <textarea name="content" class="form-control" rows="15" required>{{ $style->content }}</textarea>
                                <small class="form-text text-muted">{{ __('Enter your global CSS styles here') }}</small>
                            </div>
                            <div class="form-group">
                                <div class="form-check">
                                    <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" {{ $style->is_active ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">{{ __('Active') }}</label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">{{ __('Update Style') }}</button>
                            <a href="{{ route('admin.page-builder.global-styles.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
