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
                    <form action="{{ route('admin.page-builder.store') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label>{{ __('Page Name') }}</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>{{ __('Slug') }}</label>
                            <input type="text" name="slug" class="form-control">
                            <small class="form-text text-muted">{{ __('Leave empty to auto-generate from name') }}</small>
                        </div>
                        <div class="form-group">
                            <label>{{ __('Order') }}</label>
                            <input type="number" name="order" class="form-control" value="0">
                        </div>
                        <div class="form-group">
                            <label>{{ __('Status') }}</label>
                            <select name="status" class="form-control">
                                <option value="1">{{ __('Active') }}</option>
                                <option value="0">{{ __('Inactive') }}</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">{{ __('Create Page') }}</button>
                        <a href="{{ route('admin.page-builder.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
