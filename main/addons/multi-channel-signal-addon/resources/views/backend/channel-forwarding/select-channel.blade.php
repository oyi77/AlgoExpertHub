@extends('backend.layout.master')

@section('element')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.channel-forwarding.index') }}">
                        <i class="fa fa-arrow-left"></i> {{ __('Back') }}
                    </a>
                    <h4 class="card-title mb-0">{{ $title }}: {{ $source->name }}</h4>
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
                        @if(!empty($selectedChannels))
                            {{ __('You can modify your channel selection below. Currently selected channels are pre-checked.') }}
                            <div class="mt-2">
                                <strong>{{ __('Currently Selected:') }}</strong>
                                @foreach($selectedChannels as $ch)
                                    <span class="badge bg-primary">{{ $ch['title'] ?? $ch['username'] ?? 'Channel #' . ($ch['id'] ?? '') }}</span>
                                @endforeach
                            </div>
                        @else
                            {{ __('Select one or more channels/groups from the list below to forward messages from. You can select multiple channels by checking the boxes.') }}
                        @endif
                    </div>

                    <!-- Submit and Cancel buttons at top -->
                    <div class="mb-4 text-center">
                        <button type="submit" form="channels-form" class="btn btn-primary btn-lg" id="submitBtn" disabled>
                            <i class="fa fa-check"></i> <span id="submitBtnText">{{ __('Select Channels') }}</span>
                        </button>
                        <a href="{{ route('admin.channel-forwarding.index') }}" class="btn btn-secondary btn-lg">
                            {{ __('Cancel') }}
                        </a>
                    </div>

                    <!-- Selected channels count -->
                    <div class="mb-3">
                        <div class="alert alert-info" id="selected-count" style="display: none;">
                            <i class="fa fa-check-circle"></i>
                            <span id="selected-count-text">0 channels selected</span>
                        </div>
                    </div>

                    <!-- Search bar -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fa fa-search"></i>
                                </span>
                                <input type="text" 
                                       id="search-input" 
                                       class="form-control" 
                                       placeholder="{{ __('Search channels by name, username, or ID...') }}"
                                       autocomplete="off">
                                <button class="btn btn-outline-secondary" 
                                        type="button" 
                                        id="clear-search" 
                                        style="display: none;">
                                    <i class="fa fa-times"></i> {{ __('Clear') }}
                                </button>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted">
                                    <span id="search-results-count"></span>
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Error message -->
                    <div id="error-message" class="alert alert-danger" style="display: none;">
                        <i class="fa fa-exclamation-triangle"></i>
                        <span id="error-text"></span>
                    </div>

                    <!-- Channels list -->
                    <div id="channels-container">
                        <form id="channels-form" action="{{ route('admin.channel-forwarding.select-channel.post', $source->id) }}" method="post">
                            @csrf
                            <!-- Hidden input for channel data (will be populated by JavaScript) -->
                            <input type="hidden" name="channel_data" id="channel-data-input" value="">
                            
                            <!-- Scrollable channel list container -->
                            <div id="channels-scroll-container" style="max-height: 600px; overflow-y: auto; border: 1px solid #e0e0e0; border-radius: 8px; padding: 15px; background: #f9f9f9;">
                                <!-- Skeleton loaders -->
                                <div id="skeleton-loaders" class="row">
                                    @for($i = 0; $i < 6; $i++)
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="card border skeleton-card">
                                                <div class="card-body">
                                                <div class="d-flex align-items-center">
                                                    <div class="skeleton-checkbox me-3"></div>
                                                    <div class="flex-grow-1">
                                                        <div class="skeleton-line mb-2" style="width: 70%; height: 20px;"></div>
                                                        <div class="skeleton-line mb-1" style="width: 50%; height: 14px;"></div>
                                                        <div class="skeleton-line" style="width: 40%; height: 14px;"></div>
                                                    </div>
                                                    <div class="skeleton-badge"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endfor
                            </div>

                                <div class="row" id="channels-list">
                                    <!-- Channels will be loaded here via AJAX -->
                                </div>

                                <!-- Loading more indicator -->
                                <div id="loading-more" class="text-center py-3" style="display: none;">
                                    <div class="d-flex align-items-center justify-content-center">
                                        <div class="spinner-border spinner-border-sm text-primary" role="status" style="flex-shrink: 0;">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <span class="ms-2 text-muted" style="flex-shrink: 0;">Loading more channels...</span>
                                    </div>
                                </div>

                                <!-- End of list indicator -->
                                <div id="end-of-list" class="text-center py-3 text-muted" style="display: none;">
                                    <small><i class="fa fa-check-circle"></i> All channels loaded</small>
                                </div>

                                <!-- No results message -->
                                <div id="no-results" class="text-center py-5" style="display: none;">
                                    <i class="fa fa-search fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No channels found matching your search.</p>
                                    <button class="btn btn-sm btn-outline-primary" onclick="document.getElementById('search-input').value = ''; document.getElementById('clear-search').click();">
                                        <i class="fa fa-times"></i> Clear Search
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('style')
    <style>
        /* Ensure loading text doesn't rotate */
        #loading-more span {
            display: inline-block;
            transform: none !important;
            animation: none !important;
        }
        #loading-more .spinner-border {
            display: inline-block;
        }
        .skeleton-card {
            animation: pulse 1.5s ease-in-out infinite;
        }
        .skeleton-checkbox,
        .skeleton-radio {
            width: 20px;
            height: 20px;
            border-radius: 4px;
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }
        .skeleton-line {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
            border-radius: 4px;
        }
        .skeleton-badge {
            width: 60px;
            height: 24px;
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
            border-radius: 12px;
        }
        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        .channel-card {
            transition: all 0.2s ease;
            border: 2px solid #e0e0e0;
        }
        .channel-card:hover {
            border-color: #007bff;
            box-shadow: 0 2px 8px rgba(0,123,255,0.15);
            transform: translateY(-2px);
        }
        .channel-card.selected {
            border-color: #007bff;
            background-color: #f0f7ff;
            box-shadow: 0 2px 8px rgba(0,123,255,0.2);
        }
        /* Scrollable container styling */
        #channels-scroll-container {
            scrollbar-width: thin;
            scrollbar-color: #c0c0c0 #f0f0f0;
        }
        #channels-scroll-container::-webkit-scrollbar {
            width: 8px;
        }
        #channels-scroll-container::-webkit-scrollbar-track {
            background: #f0f0f0;
            border-radius: 4px;
        }
        #channels-scroll-container::-webkit-scrollbar-thumb {
            background: #c0c0c0;
            border-radius: 4px;
        }
        #channels-scroll-container::-webkit-scrollbar-thumb:hover {
            background: #a0a0a0;
        }
        .channel-card .form-check-input:checked {
            background-color: #007bff;
            border-color: #007bff;
        }
        #channels-list .card {
            margin-bottom: 1rem;
        }
        .channel-card.hidden {
            display: none !important;
        }
        .search-highlight {
            background-color: #fff3cd;
            padding: 2px 4px;
            border-radius: 3px;
            font-weight: 600;
        }
        #search-input:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
        }
    </style>
    @endpush

    @push('script')
    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
        const sourceId = {{ $source->id }};
        const loadDialogsUrl = '{{ route("admin.channel-forwarding.load-dialogs", $source->id) }}';
        const selectedChannelIds = @json($selectedChannelIds ?? []); // Currently selected channel IDs for pre-checking

        let currentChunk = 0;
        let isLoading = false;
        let hasMore = true;
        let totalLoaded = 0;
        let scrollTimeout = null;
        let allChannels = []; // Store all loaded channels for search
        let searchTimeout = null;

        // Load channels asynchronously on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadChannelsChunk(0);
            
            // Setup infinite scroll
            setupInfiniteScroll();
            
            // Setup search functionality
            setupSearch();
        });

        function setupSearch() {
            const searchInput = document.getElementById('search-input');
            const clearSearch = document.getElementById('clear-search');
            const searchResultsCount = document.getElementById('search-results-count');
            
            searchInput.addEventListener('input', function() {
                const query = this.value.trim().toLowerCase();
                
                // Show/hide clear button
                clearSearch.style.display = query ? 'block' : 'none';
                
                // Debounce search
                if (searchTimeout) {
                    clearTimeout(searchTimeout);
                }
                
                searchTimeout = setTimeout(function() {
                    filterChannels(query);
                    updateSearchResultsCount(query);
                }, 300);
            });
            
            clearSearch.addEventListener('click', function() {
                searchInput.value = '';
                clearSearch.style.display = 'none';
                filterChannels('');
                updateSearchResultsCount('');
            });
        }

        function filterChannels(query) {
            const channelsList = document.getElementById('channels-list');
            const cards = channelsList.querySelectorAll('.col-md-6.col-lg-4');
            const loadingMore = document.getElementById('loading-more');
            const endOfList = document.getElementById('end-of-list');
            let visibleCount = 0;
            
            cards.forEach(card => {
                const title = (card.getAttribute('data-title') || '').toLowerCase();
                const username = (card.getAttribute('data-username') || '').toLowerCase();
                const id = (card.getAttribute('data-id') || '').toLowerCase();
                
                if (!query) {
                    // Show all channels and restore original text
                    card.classList.remove('hidden');
                    restoreOriginalText(card);
                    visibleCount++;
                } else {
                    // Filter channels
                    const matches = title.includes(query) || 
                                   username.includes(query) || 
                                   id.includes(query);
                    
                    if (matches) {
                        card.classList.remove('hidden');
                        visibleCount++;
                        
                        // Highlight search terms
                        highlightSearchTerms(card, query);
                    } else {
                        card.classList.add('hidden');
                        restoreOriginalText(card);
                    }
                }
            });
            
            // Hide loading indicators when searching
            if (query && loadingMore) {
                loadingMore.style.display = 'none';
            }
            if (query && endOfList) {
                endOfList.style.display = 'none';
            }
            
            // Show/hide "no results" message
            const noResults = document.getElementById('no-results');
            if (noResults) {
                noResults.style.display = visibleCount === 0 && query ? 'block' : 'none';
            }
        }

        function highlightSearchTerms(card, query) {
            const titleElement = card.querySelector('.channel-title');
            const usernameElement = card.querySelector('.channel-username');
            const originalTitle = card.getAttribute('data-title') || '';
            const originalUsername = card.getAttribute('data-username') || '';
            
            if (titleElement && originalTitle) {
                titleElement.innerHTML = highlightText(originalTitle, query);
            }
            
            if (usernameElement && originalUsername) {
                usernameElement.innerHTML = `<i class="fa fa-at"></i> ${highlightText(originalUsername, query)}`;
                usernameElement.style.display = 'block';
            }
        }

        function restoreOriginalText(card) {
            const titleElement = card.querySelector('.channel-title');
            const usernameElement = card.querySelector('.channel-username');
            const originalTitle = card.getAttribute('data-title') || '';
            const originalUsername = card.getAttribute('data-username') || '';
            
            if (titleElement && originalTitle) {
                titleElement.textContent = originalTitle;
            }
            
            if (usernameElement) {
                if (originalUsername) {
                    usernameElement.innerHTML = `<i class="fa fa-at"></i> ${escapeHtml(originalUsername)}`;
                    usernameElement.style.display = 'block';
                } else {
                    usernameElement.style.display = 'none';
                }
            }
        }

        function highlightText(text, query) {
            if (!query) return escapeHtml(text);
            
            const regex = new RegExp(`(${escapeRegex(query)})`, 'gi');
            return escapeHtml(text).replace(regex, '<span class="search-highlight">$1</span>');
        }

        function escapeRegex(str) {
            return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        }

        function updateSearchResultsCount(query) {
            const searchResultsCount = document.getElementById('search-results-count');
            const channelsList = document.getElementById('channels-list');
            const visibleCards = channelsList.querySelectorAll('.col-md-6.col-lg-4:not(.hidden)');
            const totalCards = channelsList.querySelectorAll('.col-md-6.col-lg-4').length;
            
            if (query) {
                searchResultsCount.textContent = `Showing ${visibleCards.length} of ${totalCards} channels`;
            } else {
                searchResultsCount.textContent = totalCards > 0 ? `${totalCards} channels available` : '';
            }
        }

        function setupInfiniteScroll() {
            const scrollContainer = document.getElementById('channels-scroll-container');
            if (!scrollContainer) return;

            // Use intersection observer for better performance
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    // Don't load more if searching
                    const searchInput = document.getElementById('search-input');
                    const isSearching = searchInput && searchInput.value.trim();
                    
                    if (entry.isIntersecting && !isLoading && hasMore && !isSearching) {
                        // Load next chunk
                        loadChannelsChunk(currentChunk + 1);
                    }
                });
            }, {
                root: scrollContainer, // Use scroll container as root
                rootMargin: '200px', // Start loading 200px before reaching bottom
                threshold: 0.1
            });

            // Observe the loading more indicator
            const loadingMore = document.getElementById('loading-more');
            if (loadingMore) {
                observer.observe(loadingMore);
            }

            // Also observe end of list indicator
            const endOfList = document.getElementById('end-of-list');
            if (endOfList) {
                observer.observe(endOfList);
            }

            // Fallback: traditional scroll event on the scroll container
            scrollContainer.addEventListener('scroll', function() {
                if (scrollTimeout) {
                    clearTimeout(scrollTimeout);
                }
                
                scrollTimeout = setTimeout(function() {
                    // Don't load more if searching or already loading
                    const searchInput = document.getElementById('search-input');
                    const isSearching = searchInput && searchInput.value.trim();
                    
                    if (isLoading || !hasMore || isSearching) return;
                
                    // Check if user scrolled near bottom (200px from bottom) of the scroll container
                    const scrollTop = scrollContainer.scrollTop;
                    const containerHeight = scrollContainer.clientHeight;
                    const scrollHeight = scrollContainer.scrollHeight;
                    
                    if (scrollTop + containerHeight >= scrollHeight - 200) {
                        loadChannelsChunk(currentChunk + 1);
                    }
                }, 100);
            });
        }

        function loadChannelsChunk(chunk) {
            if (isLoading || (!hasMore && chunk > 0)) return;
            
            const skeletonLoaders = document.getElementById('skeleton-loaders');
            const errorMessage = document.getElementById('error-message');
            const errorText = document.getElementById('error-text');
            const channelsList = document.getElementById('channels-list');
            const loadingMore = document.getElementById('loading-more');
            const endOfList = document.getElementById('end-of-list');

            // Show loading indicators
            if (chunk === 0) {
                skeletonLoaders.style.display = 'block';
                errorMessage.style.display = 'none';
                channelsList.innerHTML = '';
                endOfList.style.display = 'none';
            } else {
                loadingMore.style.display = 'block';
            }

            isLoading = true;

            // Add timeout to prevent hanging requests
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 90000); // 90 second timeout
            
            fetch(loadDialogsUrl + '?chunk=' + chunk, {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                signal: controller.signal
            })
            .then(async response => {
                // Check if response is JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    // If not JSON, it's probably an HTML error page (password prompt)
                    const text = await response.text();
                    console.error('Non-JSON response received:', text.substring(0, 200));
                    
                    // Check if it's a password prompt
                    if (text.includes('Enter your password') || text.includes('password')) {
                        // Redirect to password authentication
                        window.location.href = `{{ route('admin.signal-sources.authenticate', ['id' => $source->id, 'step' => 'password']) }}`;
                        return;
                    }
                    
                    throw new Error(`Server returned HTML instead of JSON. Status: ${response.status}. Check console for details.`);
                }
                
                if (!response.ok) {
                    return response.json().then(data => {
                        // Check if password is required
                        if (data.password_required) {
                            // Redirect to password authentication
                            window.location.href = `{{ route('admin.signal-sources.authenticate', ['id' => $source->id, 'step' => 'password']) }}`;
                            return;
                        }
                        throw new Error(data.message || `Server error: ${response.status}`);
                    }).catch(() => {
                        throw new Error(`Server error: ${response.status} ${response.statusText}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                clearTimeout(timeoutId);
                isLoading = false;
                skeletonLoaders.style.display = 'none';
                loadingMore.style.display = 'none';

                if (!data.success) {
                    if (chunk === 0) {
                        errorText.textContent = data.message || 'Failed to load channels.';
                        errorMessage.style.display = 'block';
                        // Hide loading indicators
                        skeletonLoaders.style.display = 'none';
                        loadingMore.style.display = 'none';
                    }
                    return;
                }

                let dialogs = data.dialogs || [];
                hasMore = data.has_more || false;
                totalLoaded = data.total_loaded || totalLoaded + dialogs.length;
                currentChunk = chunk;
                
                // Check if we're in a loading state (background fetch in progress)
                if (data.loading && dialogs.length === 0 && chunk === 0) {
                    errorText.innerHTML = (data.message || 'Loading channels, please wait a moment...') + 
                        ' <button class="btn btn-sm btn-primary ms-2" onclick="loadChannelsChunk(0); this.disabled=true; this.innerHTML=\'<i class=\\\'fa fa-spinner fa-spin\\\'></i> Retrying...\';">Retry Now</button>';
                    errorMessage.style.display = 'block';
                    skeletonLoaders.style.display = 'none';
                    loadingMore.style.display = 'none';
                    // Auto-retry after 5 seconds
                    setTimeout(() => {
                        if (currentChunk === 0 && totalLoaded === 0) {
                            console.log('Auto-retrying channel load...');
                            loadChannelsChunk(0);
                        }
                    }, 5000);
                    return;
                }

                // Sort dialogs: selected channels first
                if (selectedChannelIds && selectedChannelIds.length > 0) {
                    dialogs = dialogs.sort((a, b) => {
                        const aId = String(a.id);
                        const aUsername = String(a.username || '');
                        const bId = String(b.id);
                        const bUsername = String(b.username || '');
                        
                        const aSelected = selectedChannelIds.includes(aId) || selectedChannelIds.includes(aUsername);
                        const bSelected = selectedChannelIds.includes(bId) || selectedChannelIds.includes(bUsername);
                        
                        if (aSelected && !bSelected) return -1; // a comes first
                        if (!aSelected && bSelected) return 1;  // b comes first
                        return 0; // keep original order
                    });
                }

                // Render new channels (append to existing)
                if (dialogs.length > 0) {
                    renderChannels(dialogs, true); // Append mode
                    
                    // Apply current search filter if active
                    const searchInput = document.getElementById('search-input');
                    if (searchInput && searchInput.value.trim()) {
                        filterChannels(searchInput.value.trim().toLowerCase());
                    }
                    
                    // Update search results count
                    updateSearchResultsCount(searchInput ? searchInput.value.trim().toLowerCase() : '');
                }

                // Show end of list if no more
                if (!hasMore) {
                    endOfList.style.display = 'block';
                    
                    // If no channels at all, show error
                    if (totalLoaded === 0 && chunk === 0) {
                        errorText.textContent = 'No channels or groups found. Make sure your Telegram account has access to channels/groups.';
                        errorMessage.style.display = 'block';
                    }
                }
            })
            .catch(error => {
                clearTimeout(timeoutId);
                isLoading = false;
                skeletonLoaders.style.display = 'none';
                loadingMore.style.display = 'none';
                
                if (chunk === 0) {
                    let errorMsg = 'Error loading channels: ';
                    if (error.name === 'AbortError') {
                        errorMsg = 'Request timed out. The server may be slow or overloaded. ';
                    } else {
                        errorMsg += error.message || 'Unknown error';
                    }
                    errorText.innerHTML = errorMsg + 
                        ' <button class="btn btn-sm btn-primary ms-2" onclick="loadChannelsChunk(0); this.disabled=true; this.innerHTML=\'<i class=\\\'fa fa-spinner fa-spin\\\'></i> Retrying...\';">Retry</button>';
                    errorMessage.style.display = 'block';
                }
                console.error('Error loading channels chunk:', error);
            });
        }

        function renderChannels(dialogs, append = false) {
            const channelsList = document.getElementById('channels-list');
            
            // Sort dialogs: selected channels first
            if (selectedChannelIds && selectedChannelIds.length > 0) {
                dialogs = dialogs.sort((a, b) => {
                    const aId = String(a.id);
                    const aUsername = String(a.username || '');
                    const bId = String(b.id);
                    const bUsername = String(b.username || '');
                    
                    const aSelected = selectedChannelIds.includes(aId) || selectedChannelIds.includes(aUsername);
                    const bSelected = selectedChannelIds.includes(bId) || selectedChannelIds.includes(bUsername);
                    
                    if (aSelected && !bSelected) return -1;
                    if (!aSelected && bSelected) return 1;
                    return 0;
                });
            }
            
            // If appending, we need to sort all channels (existing + new) to maintain order
            if (append) {
                // Collect all existing channel cards
                const existingCards = Array.from(channelsList.querySelectorAll('.col-md-6.col-lg-4'));
                const existingData = existingCards.map(card => ({
                    id: card.getAttribute('data-id'),
                    username: card.getAttribute('data-username'),
                    title: card.getAttribute('data-title'),
                    type: card.getAttribute('data-type'),
                    element: card
                }));
                
                // Combine with new dialogs
                const allChannels = [...existingData, ...dialogs.map(d => ({
                    id: String(d.id),
                    username: String(d.username || ''),
                    title: d.title || 'Unknown',
                    type: (d.type || '').toLowerCase().includes('channel') ? 'channel' : 'group',
                    element: null,
                    dialog: d
                }))];
                
                // Sort all channels: selected first
                if (selectedChannelIds && selectedChannelIds.length > 0) {
                    allChannels.sort((a, b) => {
                        const aSelected = selectedChannelIds.includes(a.id) || selectedChannelIds.includes(a.username);
                        const bSelected = selectedChannelIds.includes(b.id) || selectedChannelIds.includes(b.username);
                        
                        if (aSelected && !bSelected) return -1;
                        if (!aSelected && bSelected) return 1;
                        return 0;
                    });
                }
                
                // Clear and re-render all channels in sorted order
                channelsList.innerHTML = '';
                
                // Render existing cards first (they're already DOM elements)
                allChannels.forEach(channel => {
                    if (channel.element) {
                        channelsList.appendChild(channel.element);
                    } else if (channel.dialog) {
                        // Render new dialog
                        renderSingleChannel(channel.dialog, channelsList);
                    }
                });
                
                // Attach event listeners
                attachCheckboxListeners();
                return;
            }
            
            // Clear list only if not appending
            if (!append) {
                channelsList.innerHTML = '';
            }

            dialogs.forEach(dialog => {
                renderSingleChannel(dialog, channelsList);
            });
            
            // Attach event listeners
            attachCheckboxListeners();
        }
        
        function renderSingleChannel(dialog, channelsList) {
            const isChannel = (dialog.type || '').toLowerCase().includes('channel') || 
                             (dialog.type || '').toLowerCase().includes('supergroup');
            const isGroup = (dialog.type || '').toLowerCase().includes('chat') || 
                          (dialog.type || '').toLowerCase().includes('group') ||
                          (dialog.type || '').toLowerCase().includes('megagroup');

            if (!isChannel && !isGroup) {
                return; // Skip non-channel/group dialogs
            }

            const channelType = isChannel ? 'channel' : 'group';
            const channelId = dialog.id;
            const title = dialog.title || 'Unknown';
            const username = dialog.username || '';

            const channelCard = document.createElement('div');
            channelCard.className = 'col-md-6 col-lg-4';
            channelCard.setAttribute('data-title', title);
            channelCard.setAttribute('data-username', username);
            channelCard.setAttribute('data-id', channelId);
            channelCard.setAttribute('data-type', channelType);
            
            // Check if this channel is already selected
            const isSelected = selectedChannelIds.includes(String(channelId)) || selectedChannelIds.includes(String(username));
            const checkedAttr = isSelected ? 'checked' : '';
            
            channelCard.innerHTML = `
                <div class="card border channel-card ${isSelected ? 'selected' : ''}" 
                     onclick="toggleChannel(${channelId}, '${channelType}')"
                     style="cursor: pointer;">
                    <div class="card-body p-3">
                        <div class="form-check m-0">
                            <input class="form-check-input channel-checkbox" type="checkbox" 
                                name="channels[]" 
                                id="channel_${channelId}" 
                                value="${channelId}"
                                data-channel-type="${channelType}"
                                data-channel-title="${escapeHtml(title)}"
                                data-channel-username="${escapeHtml(username)}"
                                data-channel-id="${channelId}"
                                ${checkedAttr}>
                            <label class="form-check-label w-100 ms-2" for="channel_${channelId}">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 fw-bold channel-title">${escapeHtml(title)}</h6>
                                        ${username ? `<p class="mb-1 text-muted small channel-username"><i class="fa fa-at"></i> ${escapeHtml(username)}</p>` : '<p class="mb-1 text-muted small channel-username" style="display:none;"></p>'}
                                        <p class="mb-0 text-muted small channel-id"><i class="fa fa-hashtag"></i> ${channelId}</p>
                                    </div>
                                    <div class="ms-2">
                                        <span class="badge bg-${isChannel ? 'primary' : 'info'}">
                                            ${isChannel ? '{{ __('Channel') }}' : '{{ __('Group') }}'}
                                        </span>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            `;

            channelsList.appendChild(channelCard);
        }
        
        function attachCheckboxListeners() {
            // Attach event listeners to checkboxes
            document.querySelectorAll('.channel-checkbox').forEach(checkbox => {
                // Remove existing listeners to prevent duplicates
                const newCheckbox = checkbox.cloneNode(true);
                checkbox.parentNode.replaceChild(newCheckbox, checkbox);
                
                newCheckbox.addEventListener('change', function() {
                    updateSelectedCount();
                    updateSubmitButton();
                    
                    // Update card selection visual state
                    if (this.checked) {
                        this.closest('.channel-card').classList.add('selected');
                    } else {
                        this.closest('.channel-card').classList.remove('selected');
                    }
                });
            });
            
            // Update selected count on initial load (after pre-checking)
            updateSelectedCount();
            updateSubmitButton();
        }

        function toggleChannel(channelId, channelType) {
            const checkbox = document.getElementById('channel_' + channelId);
            if (checkbox) {
                checkbox.checked = !checkbox.checked;
                checkbox.dispatchEvent(new Event('change'));
            }
        }

        function updateSelectedCount() {
            const checkedBoxes = document.querySelectorAll('.channel-checkbox:checked');
            const count = checkedBoxes.length;
            const selectedCountDiv = document.getElementById('selected-count');
            const selectedCountText = document.getElementById('selected-count-text');
            
            if (count > 0) {
                selectedCountDiv.style.display = 'block';
                selectedCountText.textContent = count === 1 
                    ? '1 channel selected' 
                    : `${count} channels selected`;
            } else {
                selectedCountDiv.style.display = 'none';
            }
        }

        function updateSubmitButton() {
            const checkedBoxes = document.querySelectorAll('.channel-checkbox:checked');
            const submitBtn = document.getElementById('submitBtn');
            const submitBtnText = document.getElementById('submitBtnText');
            
            if (checkedBoxes.length > 0) {
                submitBtn.disabled = false;
                submitBtnText.textContent = checkedBoxes.length === 1 
                    ? '{{ __('Select Channel') }}' 
                    : `{{ __('Select') }} ${checkedBoxes.length} {{ __('Channels') }}`;
            } else {
                submitBtn.disabled = true;
                submitBtnText.textContent = '{{ __('Select Channels') }}';
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Handle form submission - collect channel data before submitting
        document.getElementById('channels-form').addEventListener('submit', function(e) {
            const checkedBoxes = document.querySelectorAll('.channel-checkbox:checked');
            const channelData = [];
            
            checkedBoxes.forEach(checkbox => {
                channelData.push({
                    id: checkbox.value,
                    title: checkbox.getAttribute('data-channel-title') || 'Unknown',
                    username: checkbox.getAttribute('data-channel-username') || null,
                    type: checkbox.getAttribute('data-channel-type') || 'unknown'
                });
            });
            
            // Set channel data as JSON in hidden input
            document.getElementById('channel-data-input').value = JSON.stringify(channelData);
        });
    </script>
    @endpush
@endsection

