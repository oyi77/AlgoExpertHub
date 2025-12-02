@extends('backend.layout.master')

@section('element')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.signal-sources.index') }}">
                        <i class="fa fa-arrow-left"></i> {{ __('Back') }}
                    </a>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.signal-sources.update', $source->id) }}" method="post">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-12 mb-4">
                                <label>{{ __('Source Name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $source->name) }}" required>
                            </div>

                            @if ($source->type === 'telegram_mtproto')
                                <div class="col-md-6 mb-4">
                                    <label>{{ __('Telegram API ID') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="api_id" class="form-control" value="{{ old('api_id', $source->config['api_id'] ?? '') }}" required>
                                    <small class="text-muted">{{ __('Get from https://my.telegram.org/apps') }}</small>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label>{{ __('Telegram API Hash') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="api_hash" class="form-control" value="{{ old('api_hash', $source->config['api_hash'] ?? '') }}" required>
                                </div>
                                <div class="col-md-12 mb-4">
                                    <div class="alert alert-warning">
                                        <i class="fa fa-exclamation-triangle"></i>
                                        {{ __('Changing API credentials will require re-authentication.') }}
                                    </div>
                                </div>
                            @endif

                            <div class="col-md-6 mb-4">
                                <label>{{ __('Parser Preference') }}</label>
                                <select name="parser_preference" class="form-control">
                                    <option value="auto" {{ old('parser_preference', $source->parser_preference ?? 'auto') === 'auto' ? 'selected' : '' }}>
                                        {{ __('Auto (Pattern First, Then AI)') }}
                                    </option>
                                    <option value="pattern" {{ old('parser_preference', $source->parser_preference ?? 'auto') === 'pattern' ? 'selected' : '' }}>
                                        {{ __('Pattern Templates Only') }}
                                    </option>
                                    <option value="ai" {{ old('parser_preference', $source->parser_preference ?? 'auto') === 'ai' ? 'selected' : '' }}>
                                        {{ __('AI Parsing Only') }}
                                    </option>
                                </select>
                                <small class="text-muted">
                                    {{ __('Auto: Try pattern templates first, fallback to AI if pattern fails') }}<br>
                                    {{ __('Pattern: Only use pattern templates (faster, requires configured patterns)') }}<br>
                                    {{ __('AI: Only use AI parsing (slower, more flexible, requires AI configuration)') }}
                                </small>
                            </div>

                            <div class="col-md-12 mb-4">
                                <div class="d-flex gap-2">
                                    <button type="button" 
                                            class="btn btn-info test-connection-btn" 
                                            data-source-id="{{ $source->id }}">
                                        <i class="fa fa-plug"></i> {{ __('Test Connection') }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-save"></i> {{ __('Update Signal Source') }}
                                </button>
                                <a href="{{ route('admin.signal-sources.index') }}" class="btn btn-secondary">
                                    {{ __('Cancel') }}
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('script')
    <script>
        document.querySelectorAll('.test-connection-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const btnElement = this;
                const sourceId = btnElement.dataset.sourceId;
                const route = '{{ route("admin.signal-sources.test-connection", ":id") }}'.replace(':id', sourceId);
                
                const originalHtml = btnElement.innerHTML;
                btnElement.disabled = true;
                btnElement.innerHTML = '<i class="fa fa-spinner fa-spin"></i> {{ __('Testing...') }}';
                
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
                
                // Use FormData to send CSRF token properly
                const formData = new FormData();
                formData.append('_token', csrfToken);
                
                fetch(route, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(data => {
                            throw new Error(data.message || `Server error: ${response.status} ${response.statusText}`);
                        }).catch(() => {
                            throw new Error(`Server error: ${response.status} ${response.statusText}`);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        btnElement.classList.remove('btn-info', 'btn-danger');
                        btnElement.classList.add('btn-success');
                        btnElement.innerHTML = '<i class="fa fa-check"></i> {{ __('Connection Successful!') }}';
                        setTimeout(() => {
                            btnElement.classList.remove('btn-success');
                            btnElement.classList.add('btn-info');
                            btnElement.innerHTML = originalHtml;
                        }, 3000);
                    } else {
                        btnElement.classList.remove('btn-info', 'btn-success');
                        btnElement.classList.add('btn-danger');
                        btnElement.innerHTML = '<i class="fa fa-times"></i> {{ __('Connection Failed') }}';
                        setTimeout(() => {
                            btnElement.classList.remove('btn-danger');
                            btnElement.classList.add('btn-info');
                            btnElement.innerHTML = originalHtml;
                        }, 3000);
                    }
                })
                .catch(error => {
                    btnElement.classList.remove('btn-info');
                    btnElement.classList.add('btn-danger');
                    btnElement.innerHTML = '<i class="fa fa-times"></i> {{ __('Error') }}';
                    setTimeout(() => {
                        btnElement.classList.remove('btn-danger');
                        btnElement.classList.add('btn-info');
                        btnElement.innerHTML = originalHtml;
                    }, 3000);
                })
                .finally(() => {
                    btnElement.disabled = false;
                });
            });
        });
    </script>
    @endpush
@endsection

