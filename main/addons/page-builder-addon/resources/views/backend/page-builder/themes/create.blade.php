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
                        <form action="{{ route('admin.page-builder.themes.store') }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <label>{{ __('Theme Name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required placeholder="my-custom-theme">
                                <small class="form-text text-muted">{{ __('Use lowercase letters, numbers, and hyphens only') }}</small>
                            </div>
                            <div class="form-group">
                                <label>{{ __('Display Name') }}</label>
                                <input type="text" name="display_name" class="form-control" placeholder="My Custom Theme">
                            </div>
                            <div class="form-group">
                                <label>{{ __('Description') }}</label>
                                <textarea name="description" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{ __('Author') }}</label>
                                        <input type="text" name="author" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>{{ __('Version') }}</label>
                                        <input type="text" name="version" class="form-control" value="1.0.0">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>{{ __('Base Theme (Optional)') }}</label>
                                <select name="base_theme" class="form-control">
                                    <option value="">{{ __('Create from scratch') }}</option>
                                    @if(isset($themes))
                                        @foreach($themes as $theme)
                                            <option value="{{ $theme['name'] }}">{{ $theme['display_name'] ?? $theme['name'] }}</option>
                                        @endforeach
                                    @endif
                                </select>
                                <small class="form-text text-muted">{{ __('Clone from an existing theme to start with a base structure') }}</small>
                            </div>
                            <button type="submit" class="btn btn-primary">{{ __('Create Theme') }}</button>
                            <a href="{{ route('admin.page-builder.themes.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
