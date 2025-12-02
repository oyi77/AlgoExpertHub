@extends('backend.layout.master')

@section('element')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">{{ __('Edit OpenRouter Configuration') }}</h4>
                </div>
                <form action="{{ route('admin.openrouter.configurations.update', $configuration->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">{{ __('Configuration Name') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="name" id="name" class="form-control" 
                                        value="{{ old('name', $configuration->name) }}" required>
                                    @error('name')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="model_id">{{ __('Model') }} <span class="text-danger">*</span></label>
                                    <select name="model_id" id="model_id" class="form-control" required>
                                        <option value="">{{ __('Select Model') }}</option>
                                        @foreach ($models as $model)
                                            <option value="{{ $model->model_id }}" 
                                                {{ old('model_id', $configuration->model_id) == $model->model_id ? 'selected' : '' }}>
                                                {{ $model->display_name }} ({{ $model->context_length }} tokens)
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('model_id')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="api_key">{{ __('OpenRouter API Key') }}</label>
                                    <input type="password" name="api_key" id="api_key" class="form-control" 
                                        value="{{ old('api_key') }}" placeholder="Leave empty to keep current API key">
                                    @error('api_key')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                    <small class="text-muted">
                                        Leave empty to keep current API key. 
                                        Get new keys from <a href="https://openrouter.ai/keys" target="_blank">OpenRouter Dashboard</a>
                                    </small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="site_url">{{ __('Site URL (Optional)') }}</label>
                                    <input type="url" name="site_url" id="site_url" class="form-control" 
                                        value="{{ old('site_url', $configuration->site_url) }}">
                                    @error('site_url')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="site_name">{{ __('Site Name (Optional)') }}</label>
                                    <input type="text" name="site_name" id="site_name" class="form-control" 
                                        value="{{ old('site_name', $configuration->site_name) }}">
                                    @error('site_name')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="temperature">{{ __('Temperature') }} <span class="text-danger">*</span></label>
                                    <input type="number" name="temperature" id="temperature" class="form-control" 
                                        value="{{ old('temperature', $configuration->temperature) }}" step="0.1" min="0" max="2" required>
                                    @error('temperature')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="max_tokens">{{ __('Max Tokens') }} <span class="text-danger">*</span></label>
                                    <input type="number" name="max_tokens" id="max_tokens" class="form-control" 
                                        value="{{ old('max_tokens', $configuration->max_tokens) }}" min="10" max="4000" required>
                                    @error('max_tokens')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="timeout">{{ __('Timeout (seconds)') }} <span class="text-danger">*</span></label>
                                    <input type="number" name="timeout" id="timeout" class="form-control" 
                                        value="{{ old('timeout', $configuration->timeout) }}" min="5" max="120" required>
                                    @error('timeout')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="priority">{{ __('Priority') }} <span class="text-danger">*</span></label>
                                    <input type="number" name="priority" id="priority" class="form-control" 
                                        value="{{ old('priority', $configuration->priority) }}" min="0" max="100" required>
                                    @error('priority')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                    <small class="text-muted">Higher priority configurations are used first</small>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{ __('Enable for Signal Parsing') }}</label>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" name="use_for_parsing" value="1" 
                                            class="custom-control-input" id="use_for_parsing"
                                            {{ old('use_for_parsing', $configuration->use_for_parsing) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="use_for_parsing"></label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{ __('Enable for Market Analysis') }}</label>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" name="use_for_analysis" value="1" 
                                            class="custom-control-input" id="use_for_analysis"
                                            {{ old('use_for_analysis', $configuration->use_for_analysis) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="use_for_analysis"></label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i> {{ __('Update Configuration') }}
                        </button>
                        <a href="{{ route('admin.openrouter.configurations.index') }}" class="btn btn-secondary">
                            <i class="fa fa-times"></i> {{ __('Cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

