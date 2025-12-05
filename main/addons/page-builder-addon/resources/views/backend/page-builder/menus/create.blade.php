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
                    <form action="{{ route('admin.page-builder.menus.store') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label>{{ __('Menu Name') }}</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>{{ __('Status') }}</label>
                            <select name="status" class="form-control">
                                <option value="1">{{ __('Active') }}</option>
                                <option value="0">{{ __('Inactive') }}</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">{{ __('Create Menu') }}</button>
                        <a href="{{ route('admin.page-builder.menus.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
