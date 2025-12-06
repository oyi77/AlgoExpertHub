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
                        <form action="{{ route('admin.page-builder.global-styles.store') }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <label>{{ __('Name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>{{ __('Type') }}</label>
                                <select name="type" class="form-control">
                                    <option value="css">CSS</option>
                                    <option value="scss">SCSS</option>
                                    <option value="less">LESS</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>{{ __('CSS Content') }} <span class="text-danger">*</span></label>
                                <textarea name="content" class="form-control" rows="15" required placeholder="/* Your CSS code here */"></textarea>
                                <small class="form-text text-muted">{{ __('Enter your global CSS styles here') }}</small>
                            </div>
                            <button type="submit" class="btn btn-primary">{{ __('Create Style') }}</button>
                            <a href="{{ route('admin.page-builder.global-styles.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
