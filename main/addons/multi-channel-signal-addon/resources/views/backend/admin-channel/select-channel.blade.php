@extends('backend.layout.master')

@section('element')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.channels.index') }}">
                        <i class="fa fa-arrow-left"></i> {{ __('Back') }}
                    </a>
                    <h4 class="card-title mb-0">{{ $title }}: {{ $channel->name }}</h4>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <div class="alert alert-info mb-4">
                        <i class="fa fa-info-circle"></i>
                        {{ __('Select a channel or group from the list below to monitor for trading signals.') }}
                    </div>

                    @if (empty($dialogs))
                        <div class="alert alert-warning">
                            <i class="fa fa-exclamation-triangle"></i>
                            {{ __('No channels or groups found. Make sure your Telegram account has access to channels/groups.') }}
                        </div>
                    @else
                        <form action="{{ route('admin.channels.select-channel.post', $channel->id) }}" method="post">
                            @csrf
                            
                            <div class="row">
                                @foreach ($dialogs as $dialog)
                                    @php
                                        $isChannel = strpos($dialog['type'] ?? '', 'Channel') !== false || strpos($dialog['type'] ?? '', 'channel') !== false;
                                        $isGroup = strpos($dialog['type'] ?? '', 'Chat') !== false || strpos($dialog['type'] ?? '', 'Group') !== false;
                                    @endphp
                                    
                                    @if ($isChannel || $isGroup)
                                        <div class="col-md-6 mb-3">
                                            <div class="card border channel-card" 
                                                 onclick="selectChannel({{ $dialog['id'] }}, '{{ $isChannel ? 'channel' : 'group' }}')"
                                                 style="cursor: pointer;">
                                                <div class="card-body">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" 
                                                            name="channel_id" 
                                                            id="channel_{{ $dialog['id'] }}" 
                                                            value="{{ $dialog['id'] }}"
                                                            data-channel-type="{{ $isChannel ? 'channel' : 'group' }}"
                                                            required>
                                                        <label class="form-check-label w-100" for="channel_{{ $dialog['id'] }}">
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <div>
                                                                    <strong>{{ $dialog['title'] ?? 'Unknown' }}</strong>
                                                                    @if (!empty($dialog['username']))
                                                                        <br><small class="text-muted">@{{ $dialog['username'] }}</small>
                                                                    @endif
                                                                    <br><small class="text-muted">ID: {{ $dialog['id'] }}</small>
                                                                </div>
                                                                <div>
                                                                    <span class="badge bg-{{ $isChannel ? 'primary' : 'info' }}">
                                                                        {{ $isChannel ? __('Channel') : __('Group') }}
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>

                            @if (count(array_filter($dialogs, function($d) {
                                return strpos($d['type'] ?? '', 'Channel') !== false || 
                                       strpos($d['type'] ?? '', 'channel') !== false ||
                                       strpos($d['type'] ?? '', 'Chat') !== false ||
                                       strpos($d['type'] ?? '', 'Group') !== false;
                            })) > 0)
                                <input type="hidden" name="channel_type" id="channel_type" value="" required>
                                <div class="mt-4">
                                    <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                                        <i class="fa fa-check"></i> {{ __('Select Channel') }}
                                    </button>
                                    <a href="{{ route('admin.channels.index') }}" class="btn btn-secondary">
                                        {{ __('Cancel') }}
                                    </a>
                                </div>
                            @endif
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('script')
    <script>
        function selectChannel(channelId, channelType) {
            // Uncheck all radios
            document.querySelectorAll('input[name="channel_id"]').forEach(radio => {
                radio.checked = false;
            });
            
            // Check selected radio
            const radio = document.getElementById('channel_' + channelId);
            if (radio) {
                radio.checked = true;
                document.getElementById('channel_type').value = channelType;
                document.getElementById('submitBtn').disabled = false;
                
                // Highlight selected card
                document.querySelectorAll('.channel-card').forEach(card => {
                    card.classList.remove('border-primary', 'bg-light');
                });
                radio.closest('.channel-card').classList.add('border-primary', 'bg-light');
            }
        }
        
        // Handle radio change
        document.querySelectorAll('input[name="channel_id"]').forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.checked) {
                    const channelType = this.getAttribute('data-channel-type');
                    document.getElementById('channel_type').value = channelType;
                    document.getElementById('submitBtn').disabled = false;
                }
            });
        });
    </script>
    @endpush
@endsection

