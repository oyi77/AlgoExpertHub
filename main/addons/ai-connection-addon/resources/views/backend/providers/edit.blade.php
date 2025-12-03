@extends('backend.layout.master')

@section('element')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">{{ __('Edit AI Provider') }}</h4>
                    <a href="{{ route('admin.ai-connections.providers.index') }}" class="btn btn-sm btn-primary">
                        <i class="fa fa-arrow-left"></i> {{ __('Back') }}
                    </a>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.ai-connections.providers.update', $provider->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">{{ __('Provider Name') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                                        value="{{ old('name', $provider->name) }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="slug">{{ __('Slug') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="slug" id="slug" class="form-control @error('slug') is-invalid @enderror"
                                        value="{{ old('slug', $provider->slug) }}" required>
                                    <small class="form-text text-muted">{{ __('e.g., openai, gemini, openrouter') }}</small>
                                    @error('slug')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="status">{{ __('Status') }} <span class="text-danger">*</span></label>
                                    <select name="status" id="status" class="form-control @error('status') is-invalid @enderror" required>
                                        <option value="active" {{ old('status', $provider->status) === 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                                        <option value="inactive" {{ old('status', $provider->status) === 'inactive' ? 'selected' : '' }}>{{ __('Inactive') }}</option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-save"></i> {{ __('Update Provider') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

