@extends('backend.layout.master')

@section('element')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.channel-forwarding.index') }}">
                        <i class="fa fa-arrow-left"></i> {{ __('Back') }}
                    </a>
                    <h4 class="card-title mb-0">{{ $title }}</h4>
                </div>
                <div class="card-body">
                    <!-- Channel Selector -->
                    @if(count($channels) > 1)
                    <div class="mb-4">
                        <label class="form-label">{{ __('Select Channel') }}</label>
                        <select id="channel-selector" class="form-select">
                            @foreach($channels as $ch)
                                <option value="{{ $ch['id'] ?? $ch['username'] }}" 
                                    {{ ($selectedChannel['id'] ?? $selectedChannel['username']) == ($ch['id'] ?? $ch['username']) ? 'selected' : '' }}>
                                    {{ $ch['title'] ?? $ch['username'] ?? 'Channel #' . ($ch['id'] ?? '') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                        <!-- Messages Section -->
                        <div class="row">
                            <div class="col-md-8">
                                <div class="card" style="position: relative;">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">{{ __('Sample Messages') }}</h5>
                                        <div>
                                            <select id="limit-selector" class="form-select form-select-sm d-inline-block" style="width: auto;">
                                                <option value="10" {{ request('limit', 20) == 10 ? 'selected' : '' }}>10 messages</option>
                                                <option value="20" {{ request('limit', 20) == 20 ? 'selected' : '' }}>20 messages</option>
                                                <option value="50" {{ request('limit', 20) == 50 ? 'selected' : '' }}>50 messages</option>
                                            </select>
                                            <button id="refresh-btn" class="btn btn-sm btn-primary ms-2">
                                                <i class="fa fa-refresh"></i> {{ __('Refresh') }}
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body" style="position: relative;">
                                    @if($messagesError)
                                        <div class="alert alert-danger">
                                            <i class="fa fa-exclamation-circle"></i> {{ $messagesError }}
                                        </div>
                                    @elseif(empty($messages))
                                        <div class="alert alert-info">
                                            <i class="fa fa-info-circle"></i> {{ __('No messages found in this channel.') }}
                                        </div>
                                    @else
                                        <div id="messages-container" style="max-height: 600px; overflow-y: auto; position: relative;">
                                            @foreach($messages as $index => $message)
                                                <div class="message-item mb-3 p-3 border rounded" data-message-id="{{ $message['message_id'] }}" data-index="{{ $index }}" style="cursor: pointer; transition: all 0.3s ease;">
                                                    <div class="d-flex justify-content-between mb-2">
                                                        <small class="text-muted">
                                                            <i class="fa fa-clock-o"></i> {{ $message['formatted_date'] ?? date('Y-m-d H:i:s', $message['date']) }}
                                                        </small>
                                                        <button class="btn btn-sm btn-outline-primary test-message-btn" data-index="{{ $index }}">
                                                            <i class="fa fa-flask"></i> {{ __('Test Parser') }}
                                                        </button>
                                                    </div>
                                                    <pre class="message-text mb-0" style="white-space: pre-wrap; font-family: inherit; background: #f8f9fa; padding: 10px; border-radius: 4px;">{{ $message['text'] }}</pre>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Parser Creation Panel -->
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">{{ __('Create Parser Pattern') }}</h5>
                                </div>
                                <div class="card-body">
                                    <form id="parser-form" action="{{ route('admin.channel-forwarding.store-parser', $source->id) }}" method="POST">
                                        @csrf
                                        <div class="mb-3">
                                            <label class="form-label">{{ __('Pattern Name') }} <span class="text-danger">*</span></label>
                                            <input type="text" name="name" class="form-control" required placeholder="e.g., Standard Signal Format">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">{{ __('Description') }}</label>
                                            <textarea name="description" class="form-control" rows="2" placeholder="Optional description"></textarea>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">{{ __('Pattern Type') }} <span class="text-danger">*</span></label>
                                            <select name="pattern_type" id="pattern-type" class="form-select" required>
                                                <option value="regex">Regex Pattern</option>
                                                <option value="template">Template Pattern</option>
                                            </select>
                                        </div>

                                        <!-- Regex Pattern Config -->
                                        <div id="regex-config">
                                            <div class="mb-3">
                                                <label class="form-label">{{ __('Currency Pair Pattern') }}</label>
                                                <input type="text" name="pattern_config[patterns][currency_pair][]" class="form-control" placeholder="/EUR\/USD|EURUSD/i">
                                                <small class="text-muted">Regex pattern to match currency pairs</small>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">{{ __('Direction Pattern') }}</label>
                                                <input type="text" name="pattern_config[patterns][direction][]" class="form-control" placeholder="/(BUY|SELL)/i">
                                                <small class="text-muted">Regex pattern to match BUY/SELL</small>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">{{ __('Entry Price Pattern') }}</label>
                                                <input type="text" name="pattern_config[patterns][open_price][]" class="form-control" placeholder="/ENTRY[:\s]*([\d,]+\.?\d*)/i">
                                                <small class="text-muted">Regex pattern to match entry price</small>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">{{ __('Stop Loss Pattern') }}</label>
                                                <input type="text" name="pattern_config[patterns][sl][]" class="form-control" placeholder="/SL[:\s]*([\d,]+\.?\d*)/i">
                                                <small class="text-muted">Regex pattern to match stop loss</small>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">{{ __('Take Profit Pattern') }}</label>
                                                <input type="text" name="pattern_config[patterns][tp][]" class="form-control" placeholder="/TP[:\s]*([\d,]+\.?\d*)/i">
                                                <small class="text-muted">Regex pattern to match take profit</small>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">{{ __('Required Fields') }}</label>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="pattern_config[required_fields][]" value="currency_pair" checked>
                                                    <label class="form-check-label">Currency Pair</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="pattern_config[required_fields][]" value="direction" checked>
                                                    <label class="form-check-label">Direction</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="pattern_config[required_fields][]" value="open_price">
                                                    <label class="form-check-label">Entry Price</label>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Template Pattern Config (simplified for now) -->
                                        <div id="template-config" style="display: none;">
                                            <div class="alert alert-info">
                                                <small>{{ __('Template patterns are line-based. Configure advanced templates in Pattern Templates section.') }}</small>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">{{ __('Priority') }}</label>
                                            <input type="number" name="priority" class="form-control" value="100" min="0" max="1000">
                                            <small class="text-muted">Higher priority patterns are tried first</small>
                                        </div>

                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                                                <label class="form-check-label">{{ __('Active') }}</label>
                                            </div>
                                        </div>

                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fa fa-save"></i> {{ __('Create Parser') }}
                                        </button>
                                    </form>

                                    <!-- Test Parser Section -->
                                    <hr>
                                    <h6>{{ __('Test Parser') }}</h6>
                                    <div class="mb-3">
                                        <label class="form-label">{{ __('Test Message') }}</label>
                                        <textarea id="test-message" class="form-control" rows="4" placeholder="Paste a sample message here to test your pattern"></textarea>
                                    </div>
                                    <button id="test-parser-btn" class="btn btn-outline-primary w-100">
                                        <i class="fa fa-flask"></i> {{ __('Test Pattern') }}
                                    </button>
                                    <div id="test-result" class="mt-3"></div>
                                </div>
                            </div>

                            <!-- Existing Patterns -->
                            @if($patterns->count() > 0)
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h5 class="mb-0">{{ __('Existing Patterns') }}</h5>
                                </div>
                                <div class="card-body">
                                    @foreach($patterns as $pattern)
                                        <div class="mb-3 p-2 border rounded">
                                            <div class="d-flex justify-content-between">
                                                <strong>{{ $pattern->name }}</strong>
                                                <span class="badge bg-{{ $pattern->is_active ? 'success' : 'secondary' }}">
                                                    {{ $pattern->is_active ? __('Active') : __('Inactive') }}
                                                </span>
                                            </div>
                                            @if($pattern->description)
                                                <small class="text-muted">{{ $pattern->description }}</small>
                                            @endif
                                            <div class="mt-2">
                                                <small class="text-muted">
                                                    Priority: {{ $pattern->priority }} | 
                                                    Success Rate: {{ number_format($pattern->getSuccessRate(), 1) }}%
                                                </small>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('style')
    <style>
        .message-item {
            transition: all 0.3s ease;
        }
        .message-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .message-item.border-primary {
            border-width: 2px !important;
        }
        #messages-container {
            scroll-behavior: smooth;
        }
    </style>
    @endpush

    @push('script')
    <script>
        $(document).ready(function() {
            let selectedMessageIndex = null;
            let messages = @json($messages);
            const sourceId = {{ $source->id }};
            const viewSamplesUrl = '{{ route("admin.channel-forwarding.view-samples", $source->id) }}';
            const testParserUrl = '{{ route("admin.channel-forwarding.test-parser", $source->id) }}';
            const currentChannelId = '{{ $selectedChannel["id"] ?? $selectedChannel["username"] }}';

            // Loading state management
            function showLoading(container) {
                const $container = $(container);
                if ($container.find('.loading-overlay').length === 0) {
                    $container.css('position', 'relative');
                    $container.append('<div class="loading-overlay" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.9); z-index: 1000; display: flex; align-items: center; justify-content: center; border-radius: 4px;"><div class="text-center"><i class="fa fa-spinner fa-spin fa-2x text-primary"></i><br><small class="text-muted mt-2">Loading messages...</small></div></div>');
                }
            }

            function hideLoading(container) {
                const $container = $(container);
                $container.find('.loading-overlay').fadeOut(200, function() {
                    $(this).remove();
                });
            }

            // Load messages via AJAX
            function loadMessages(channelId, limit) {
                const $messagesCardBody = $('.card-body').first();
                
                showLoading($messagesCardBody);
                
                $.ajax({
                    url: viewSamplesUrl,
                    method: 'GET',
                    data: {
                        channel_id: channelId,
                        limit: limit
                    },
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    success: function(response) {
                        hideLoading($messagesCardBody);
                        
                        // Handle response structure (messagesResult from controller)
                        if (response.success !== false) {
                            messages = response.messages || [];
                            updateMessagesDisplay(messages, response.error || null);
                            
                            // Update URL without reload
                            const newUrl = viewSamplesUrl + '?channel_id=' + encodeURIComponent(channelId) + '&limit=' + limit;
                            window.history.pushState({}, '', newUrl);
                            
                            // Show success notification
                            if (typeof toastr !== 'undefined') {
                                toastr.success('Messages loaded successfully');
                            }
                        } else {
                            updateMessagesDisplay([], response.error || 'Failed to load messages');
                            if (typeof toastr !== 'undefined') {
                                toastr.error(response.error || 'Failed to load messages');
                            }
                        }
                    },
                    error: function(xhr) {
                        hideLoading($messagesCardBody);
                        const errorMsg = xhr.responseJSON?.error || xhr.responseJSON?.message || 'An error occurred while loading messages';
                        updateMessagesDisplay([], errorMsg);
                        if (typeof toastr !== 'undefined') {
                            toastr.error(errorMsg);
                        }
                    }
                });
            }

            // Update messages display
            function updateMessagesDisplay(messagesData, error) {
                const $cardBody = $('.card-body').first();
                let $container = $('#messages-container');
                
                // Create container if it doesn't exist
                if ($container.length === 0) {
                    $container = $('<div id="messages-container" style="max-height: 600px; overflow-y: auto; position: relative;"></div>');
                    $cardBody.html($container);
                }
                
                if (error) {
                    $container.html('<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> ' + escapeHtml(error) + '</div>');
                    messages = [];
                    return;
                }
                
                if (!messagesData || messagesData.length === 0) {
                    $container.html('<div class="alert alert-info"><i class="fa fa-info-circle"></i> {{ __("No messages found in this channel.") }}</div>');
                    messages = [];
                    return;
                }
                
                let html = '';
                messagesData.forEach(function(message, index) {
                    const formattedDate = message.formatted_date || new Date(message.date * 1000).toLocaleString();
                    html += '<div class="message-item mb-3 p-3 border rounded" data-message-id="' + message.message_id + '" style="cursor: pointer; transition: all 0.3s ease;" data-index="' + index + '">';
                    html += '<div class="d-flex justify-content-between mb-2">';
                    html += '<small class="text-muted"><i class="fa fa-clock-o"></i> ' + escapeHtml(formattedDate) + '</small>';
                    html += '<button class="btn btn-sm btn-outline-primary test-message-btn" data-index="' + index + '"><i class="fa fa-flask"></i> {{ __("Test Parser") }}</button>';
                    html += '</div>';
                    html += '<pre class="message-text mb-0" style="white-space: pre-wrap; font-family: inherit; background: #f8f9fa; padding: 10px; border-radius: 4px;">' + escapeHtml(message.text) + '</pre>';
                    html += '</div>';
                });
                
                $container.html(html).hide().fadeIn(300);
                
                // Rebind event handlers
                bindMessageEvents();
            }

            // Escape HTML
            function escapeHtml(text) {
                const map = {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                };
                return text.replace(/[&<>"']/g, function(m) { return map[m]; });
            }

            // Bind message events
            function bindMessageEvents() {
                // Message click handler
                $('.message-item').off('click').on('click', function(e) {
                    if ($(e.target).closest('.test-message-btn').length) {
                        return;
                    }
                    selectMessage($(this).data('index'));
                });

                // Test parser button handler
                $('.test-message-btn').off('click').on('click', function(e) {
                    e.stopPropagation();
                    const index = $(this).data('index');
                    testWithMessage(index);
                });
            }

            // Channel selector change - AJAX
            $('#channel-selector').on('change', function() {
                const channelId = $(this).val();
                const limit = $('#limit-selector').val();
                loadMessages(channelId, limit);
            });

            // Limit selector change - AJAX
            $('#limit-selector').on('change', function() {
                const channelId = $('#channel-selector').val() || currentChannelId;
                const limit = $(this).val();
                loadMessages(channelId, limit);
            });

            // Refresh button - AJAX
            $('#refresh-btn').on('click', function() {
                const $btn = $(this);
                const originalHtml = $btn.html();
                $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> {{ __("Loading...") }}');
                
                const channelId = $('#channel-selector').val() || currentChannelId;
                const limit = $('#limit-selector').val();
                
                loadMessages(channelId, limit);
                
                setTimeout(function() {
                    $btn.prop('disabled', false).html(originalHtml);
                }, 1000);
            });

            // Select message
            function selectMessage(index) {
                // Remove previous selection with animation
                $('.message-item').removeClass('border-primary bg-light').css('transition', 'all 0.3s ease');
                
                // Highlight selected with animation
                const $messageItem = $('.message-item').eq(index);
                if ($messageItem.length) {
                    $messageItem.addClass('border-primary bg-light');
                    selectedMessageIndex = index;
                    
                    // Fill test message textarea
                    $('#test-message').val(messages[index].text);
                    
                    // Smooth scroll to message
                    $('html, body').animate({
                        scrollTop: $messageItem.offset().top - 100
                    }, 300);
                }
            }

            // Test parser with selected message
            function testWithMessage(index) {
                if (!messages[index]) return;
                const messageText = messages[index].text;
                $('#test-message').val(messageText);
                selectMessage(index);
                setTimeout(function() {
                    testParser();
                }, 300);
            }

            // Test parser button
            $('#test-parser-btn').on('click', function() {
                testParser();
            });

            // Test parser function
            function testParser() {
                const testMessage = $('#test-message').val();
                if (!testMessage.trim()) {
                    if (typeof toastr !== 'undefined') {
                        toastr.warning('Please enter a test message');
                    } else {
                        alert('Please enter a test message');
                    }
                    return;
                }

                // Collect pattern config from form
                const patternType = $('#pattern-type').val();
                const patternConfig = {
                    patterns: {},
                    required_fields: []
                };

                if (patternType === 'regex') {
                    // Collect regex patterns
                    $('#regex-config input[name*="[patterns]"]').each(function() {
                        const match = $(this).attr('name').match(/\[patterns\]\[(\w+)\]/);
                        if (match && $(this).val()) {
                            if (!patternConfig.patterns[match[1]]) {
                                patternConfig.patterns[match[1]] = [];
                            }
                            patternConfig.patterns[match[1]].push($(this).val());
                        }
                    });

                    // Collect required fields
                    $('#regex-config input[name*="[required_fields]"]:checked').each(function() {
                        patternConfig.required_fields.push($(this).val());
                    });
                }

                // Show loading
                const $resultDiv = $('#test-result');
                $resultDiv.html('<div class="text-center"><i class="fa fa-spinner fa-spin"></i> Testing...</div>').hide().fadeIn(200);

                // Send test request via AJAX
                $.ajax({
                    url: testParserUrl,
                    method: 'POST',
                    data: JSON.stringify({
                        pattern_type: patternType,
                        pattern_config: patternConfig,
                        test_message: testMessage
                    }),
                    contentType: 'application/json',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(data) {
                        let html = '';
                        if (data.success) {
                            if (data.parsed) {
                                html = '<div class="alert alert-success"><strong>Pattern Matched!</strong><br>';
                                html += '<small>Currency Pair: ' + (data.parsed.currency_pair || 'N/A') + '<br>';
                                html += 'Direction: ' + (data.parsed.direction || 'N/A') + '<br>';
                                html += 'Entry Price: ' + (data.parsed.open_price || 'N/A') + '<br>';
                                html += 'Stop Loss: ' + (data.parsed.sl || 'N/A') + '<br>';
                                html += 'Take Profit: ' + (data.parsed.tp || 'N/A') + '<br>';
                                html += 'Confidence: ' + (data.parsed.confidence || 'N/A') + '%</small></div>';
                            } else {
                                html = '<div class="alert alert-warning">Pattern did not match the message.</div>';
                            }
                        } else {
                            html = '<div class="alert alert-danger">Error: ' + (data.error || 'Unknown error') + '</div>';
                        }
                        $resultDiv.html(html).hide().fadeIn(300);
                    },
                    error: function(xhr) {
                        const errorMsg = xhr.responseJSON?.error || xhr.statusText || 'An error occurred';
                        $resultDiv.html('<div class="alert alert-danger">Error: ' + errorMsg + '</div>').hide().fadeIn(300);
                    }
                });
            }

            // Pattern type change with smooth transition
            $('#pattern-type').on('change', function() {
                if ($(this).val() === 'regex') {
                    $('#regex-config').slideDown(300);
                    $('#template-config').slideUp(300);
                } else {
                    $('#regex-config').slideUp(300);
                    $('#template-config').slideDown(300);
                }
            });

            // Form submission with AJAX
            $('#parser-form').on('submit', function(e) {
                e.preventDefault();
                
                const $form = $(this);
                const $submitBtn = $form.find('button[type="submit"]');
                const originalHtml = $submitBtn.html();
                
                $submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> {{ __("Saving...") }}');
                
                $.ajax({
                    url: $form.attr('action'),
                    method: 'POST',
                    data: $form.serialize(),
                    success: function(response) {
                        $submitBtn.prop('disabled', false).html(originalHtml);
                        
                        if (typeof toastr !== 'undefined') {
                            toastr.success('Parser pattern created successfully');
                        }
                        
                        // Reload page after short delay to show new pattern
                        setTimeout(function() {
                            window.location.reload();
                        }, 1000);
                    },
                    error: function(xhr) {
                        $submitBtn.prop('disabled', false).html(originalHtml);
                        const errorMsg = xhr.responseJSON?.message || xhr.responseJSON?.error || 'Failed to create parser pattern';
                        
                        if (typeof toastr !== 'undefined') {
                            toastr.error(errorMsg);
                        } else {
                            alert(errorMsg);
                        }
                    }
                });
            });

            // Initialize message events
            bindMessageEvents();
        });
    </script>
    @endpush
@endsection

