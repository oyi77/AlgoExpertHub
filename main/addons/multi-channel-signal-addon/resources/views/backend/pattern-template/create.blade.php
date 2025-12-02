@extends('backend.layout.master')

@section('element')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">{{ __('Create Pattern Template') }}</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.pattern-templates.store') }}" method="POST" id="patternForm">
                        @csrf

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>{{ __('Pattern Name') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>{{ __('Channel') }}</label>
                                    <select name="channel_source_id" class="form-control">
                                        <option value="">{{ __('Global (All Channels)') }}</option>
                                        @foreach ($channels as $channel)
                                            <option value="{{ $channel->id }}" {{ old('channel_source_id') == $channel->id || ($channelSource && $channelSource->id == $channel->id) ? 'selected' : '' }}>
                                                {{ $channel->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>{{ __('Description') }}</label>
                            <textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{ __('Pattern Type') }} <span class="text-danger">*</span></label>
                                    <select name="pattern_type" id="pattern_type" class="form-control" required>
                                        <option value="regex" {{ old('pattern_type') == 'regex' ? 'selected' : '' }}>Regex</option>
                                        <option value="template" {{ old('pattern_type') == 'template' ? 'selected' : '' }}>Template (Line-based)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{ __('Priority') }}</label>
                                    <input type="number" name="priority" class="form-control" value="{{ old('priority', 0) }}" min="0" max="1000">
                                    <small class="text-muted">{{ __('Higher priority patterns are tried first') }}</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{ __('Status') }}</label>
                                    <select name="is_active" class="form-control">
                                        <option value="1" {{ old('is_active', 1) == 1 ? 'selected' : '' }}>{{ __('Active') }}</option>
                                        <option value="0" {{ old('is_active') == 0 ? 'selected' : '' }}>{{ __('Inactive') }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>{{ __('Pattern Configuration (JSON)') }} <span class="text-danger">*</span></label>
                            <textarea name="pattern_config" id="pattern_config" class="form-control" rows="15" required>{{ old('pattern_config', isset($defaultTemplates[0]) ? json_encode($defaultTemplates[0]['pattern_config'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '') }}</textarea>
                            <small class="text-muted">
                                @foreach($defaultTemplates as $index => $template)
                                    <a href="#" onclick="loadTemplate({{ $index }}); return false;">{{ __('Load') }} {{ $template['name'] }}</a>
                                    @if(!$loop->last) | @endif
                                @endforeach
                            </small>
                        </div>

                        <div class="form-group">
                            <label>{{ __('Test Sample Message') }}</label>
                            <textarea id="test_message" class="form-control" rows="5" placeholder="Paste a sample message here to test the pattern"></textarea>
                            <button type="button" class="btn btn-sm btn-info mt-2" onclick="testPattern()">
                                <i class="fa fa-flask"></i> {{ __('Test Pattern') }}
                            </button>
                            <div id="test_result" class="mt-2"></div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-save"></i> {{ __('Create Pattern') }}
                            </button>
                            <a href="{{ route('admin.pattern-templates.index') }}" class="btn btn-secondary">
                                {{ __('Cancel') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('script')
    <script>
        const templates = @json($defaultTemplates);

        function loadTemplate(index) {
            const template = templates[index];
            if (template) {
                document.getElementById('pattern_config').value = JSON.stringify(template.pattern_config, null, 2);
                document.getElementById('pattern_type').value = template.pattern_type || 'regex';
                if (template.name) {
                    document.querySelector('input[name="name"]').value = template.name;
                }
                if (template.description) {
                    document.querySelector('textarea[name="description"]').value = template.description;
                }
                if (template.priority !== undefined) {
                    document.querySelector('input[name="priority"]').value = template.priority;
                }
            }
        }

        function testPattern() {
            const patternConfig = document.getElementById('pattern_config').value;
            const sampleMessage = document.getElementById('test_message').value;
            const resultDiv = document.getElementById('test_result');

            if (!patternConfig || !sampleMessage) {
                resultDiv.innerHTML = '<div class="alert alert-warning">Please provide both pattern config and sample message.</div>';
                return;
            }

            resultDiv.innerHTML = '<div class="alert alert-info">Testing pattern...</div>';

            fetch('{{ route("admin.pattern-templates.test") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    pattern_config: JSON.parse(patternConfig),
                    sample_message: sampleMessage
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultDiv.innerHTML = `
                        <div class="alert alert-success">
                            <strong>Pattern matched!</strong><br>
                            <strong>Confidence:</strong> ${data.confidence}%<br>
                            <strong>Parsed Data:</strong><br>
                            <pre class="mt-2">${JSON.stringify(data.parsed_data, null, 2)}</pre>
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <strong>Pattern did not match:</strong><br>
                            ${data.error || 'Unknown error'}
                        </div>
                    `;
                }
            })
            .catch(error => {
                resultDiv.innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;
            });
        }
    </script>
    @endpush
@endsection

