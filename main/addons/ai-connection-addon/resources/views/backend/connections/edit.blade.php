@extends('backend.layout.master')

@section('element')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">{{ __('Edit AI Connection') }}</h4>
                    <a href="{{ route('admin.ai-connections.connections.index') }}" class="btn btn-sm btn-primary">
                        <i class="fa fa-arrow-left"></i> {{ __('Back') }}
                    </a>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.ai-connections.connections.update', $connection->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="provider_id">{{ __('Provider') }} <span class="text-danger">*</span></label>
                                    <select name="provider_id" id="provider_id" class="form-control @error('provider_id') is-invalid @enderror" required>
                                        <option value="">{{ __('Select Provider') }}</option>
                                        @foreach ($providers as $provider)
                                            <option value="{{ $provider->id }}" {{ old('provider_id', $connection->provider_id) == $provider->id ? 'selected' : '' }}>
                                                {{ $provider->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('provider_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">{{ __('Connection Name') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                                        value="{{ old('name', $connection->name) }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="api_key">{{ __('API Key') }}</label>
                                    <input type="password" name="credentials[api_key]" id="api_key" class="form-control @error('credentials.api_key') is-invalid @enderror"
                                        placeholder="{{ __('Leave empty to keep current') }}">
                                    <small class="form-text text-muted">{{ __('Only enter if you want to update the API key') }}</small>
                                    @error('credentials.api_key')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="base_url">{{ __('Base URL') }}</label>
                                    <input type="url" name="credentials[base_url]" id="base_url" class="form-control @error('credentials.base_url') is-invalid @enderror"
                                        value="{{ old('credentials.base_url', $connection->getBaseUrl()) }}" placeholder="" data-placeholder-openai="https://api.openai.com/v1" data-placeholder-gemini="https://generativelanguage.googleapis.com/v1" data-placeholder-openrouter="https://openrouter.ai/api/v1">
                                    <small class="form-text text-muted">
                                        {{ __('Optional: Override default API endpoint. Leave empty to use provider default.') }}
                                    </small>
                                    @error('credentials.base_url')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="priority">{{ __('Priority') }} <span class="text-danger">*</span></label>
                                    <input type="number" name="priority" id="priority" class="form-control @error('priority') is-invalid @enderror"
                                        value="{{ old('priority', $connection->priority) }}" min="1" required>
                                    <small class="form-text text-muted">{{ __('Lower numbers have higher priority') }}</small>
                                    @error('priority')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="rate_limit_per_minute">{{ __('Rate Limit (per minute)') }}</label>
                                    <input type="number" name="rate_limit_per_minute" id="rate_limit_per_minute" class="form-control @error('rate_limit_per_minute') is-invalid @enderror"
                                        value="{{ old('rate_limit_per_minute', $connection->rate_limit_per_minute) }}" min="1">
                                    <small class="form-text text-muted">{{ __('Leave empty for unlimited') }}</small>
                                    @error('rate_limit_per_minute')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="rate_limit_per_day">{{ __('Rate Limit (per day)') }}</label>
                                    <input type="number" name="rate_limit_per_day" id="rate_limit_per_day" class="form-control @error('rate_limit_per_day') is-invalid @enderror"
                                        value="{{ old('rate_limit_per_day', $connection->rate_limit_per_day) }}" min="1">
                                    <small class="form-text text-muted">{{ __('Leave empty for unlimited') }}</small>
                                    @error('rate_limit_per_day')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <strong>{{ __('Status:') }}</strong> 
                                    <span class="badge badge-{{ $connection->status === 'active' ? 'success' : 'secondary' }}">
                                        {{ ucfirst($connection->status) }}
                                    </span>
                                    <br>
                                    <strong>{{ __('Health:') }}</strong> 
                                    {{ $connection->error_count }} {{ __('errors') }}
                                    <br>
                                    @if ($connection->last_used_at)
                                        <strong>{{ __('Last Used:') }}</strong> {{ $connection->last_used_at->diffForHumans() }}
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="form-group mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-save"></i> {{ __('Update Connection') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const providerSelect = document.getElementById('provider_id');
            const baseUrlInput = document.getElementById('base_url');
            const providerSlugs = @json($providers->pluck('slug', 'id')->toArray());
            
            function updateBaseUrlPlaceholder() {
                const selectedProviderId = providerSelect.value;
                if (!selectedProviderId || !baseUrlInput) return;
                
                const slug = providerSlugs[selectedProviderId];
                const placeholder = baseUrlInput.getAttribute(`data-placeholder-${slug}`);
                
                if (placeholder && !baseUrlInput.value) {
                    baseUrlInput.placeholder = placeholder;
                } else if (!baseUrlInput.value) {
                    baseUrlInput.placeholder = '';
                }
            }
            
            if (providerSelect && baseUrlInput) {
                providerSelect.addEventListener('change', updateBaseUrlPlaceholder);
                updateBaseUrlPlaceholder(); // Set initial placeholder
            }
        });
    </script>
@endsection

