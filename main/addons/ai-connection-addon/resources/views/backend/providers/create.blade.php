@extends('backend.layout.master')

@section('element')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">{{ __('Add AI Provider') }}</h4>
                    <a href="{{ route('admin.ai-connections.providers.index') }}" class="btn btn-sm btn-primary">
                        <i class="fa fa-arrow-left"></i> {{ __('Back') }}
                    </a>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.ai-connections.providers.store') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">{{ __('Provider Name') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                                        value="{{ old('name') }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="slug">{{ __('Slug') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="slug" id="slug" class="form-control @error('slug') is-invalid @enderror"
                                        value="{{ old('slug') }}" required>
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
                                        <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                                        <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>{{ __('Inactive') }}</option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-save"></i> {{ __('Create Provider') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
<script>
    $(function() {
        'use strict';

        // Auto-generate slug from name
        $('#name').on('input', function() {
            let name = $(this).val();
            let slug = name.toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
            $('#slug').val(slug);
        });
    });
</script>
@endpush

