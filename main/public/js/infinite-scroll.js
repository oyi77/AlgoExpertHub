/**
 * Infinite Scroll Component
 * Handles infinite scrolling with performance optimizations
 */
class InfiniteScroll {
    constructor(container, options = {}) {
        this.container = typeof container === 'string' ? document.querySelector(container) : container;
        this.options = {
            endpoint: options.endpoint || '/api/user/data-loading/signals/infinite-scroll',
            threshold: options.threshold || 200, // pixels from bottom
            perPage: options.perPage || 20,
            loadingClass: options.loadingClass || 'loading',
            errorClass: options.errorClass || 'error',
            emptyClass: options.emptyClass || 'empty',
            itemSelector: options.itemSelector || '.scroll-item',
            loadingTemplate: options.loadingTemplate || this.getDefaultLoadingTemplate(),
            errorTemplate: options.errorTemplate || this.getDefaultErrorTemplate(),
            emptyTemplate: options.emptyTemplate || this.getDefaultEmptyTemplate(),
            onLoad: options.onLoad || null,
            onError: options.onError || null,
            onComplete: options.onComplete || null,
            debounceDelay: options.debounceDelay || 100,
            maxRetries: options.maxRetries || 3,
            ...options
        };

        this.currentPage = 1;
        this.loading = false;
        this.hasMore = true;
        this.retryCount = 0;
        this.items = [];
        this.observer = null;
        this.loadingElement = null;
        this.errorElement = null;
        this.emptyElement = null;

        this.init();
    }

    init() {
        if (!this.container) {
            console.error('InfiniteScroll: Container not found');
            return;
        }

        this.createSentinel();
        this.setupIntersectionObserver();
        this.loadInitialData();
    }

    createSentinel() {
        this.sentinel = document.createElement('div');
        this.sentinel.className = 'infinite-scroll-sentinel';
        this.sentinel.style.height = '1px';
        this.sentinel.style.visibility = 'hidden';
        this.container.appendChild(this.sentinel);
    }

    setupIntersectionObserver() {
        const options = {
            root: null,
            rootMargin: `${this.options.threshold}px`,
            threshold: 0.1
        };

        this.observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && this.hasMore && !this.loading) {
                    this.loadMore();
                }
            });
        }, options);

        this.observer.observe(this.sentinel);
    }

    async loadInitialData() {
        this.currentPage = 1;
        this.hasMore = true;
        this.items = [];
        this.clearContainer();
        await this.loadMore();
    }

    async loadMore() {
        if (this.loading || !this.hasMore) return;

        this.loading = true;
        this.showLoading();

        try {
            const response = await this.fetchData();
            
            if (response.success) {
                const data = response.data;
                this.items = [...this.items, ...data.signals];
                this.hasMore = data.pagination.has_more_pages;
                this.currentPage = data.pagination.next_page || this.currentPage;
                
                this.renderItems(data.signals);
                this.retryCount = 0;

                if (this.options.onLoad) {
                    this.options.onLoad(data, this);
                }

                if (!this.hasMore && this.options.onComplete) {
                    this.options.onComplete(this.items, this);
                }
            } else {
                throw new Error(response.message || 'Failed to load data');
            }
        } catch (error) {
            console.error('InfiniteScroll: Load error', error);
            this.handleError(error);
        } finally {
            this.loading = false;
            this.hideLoading();
        }
    }

    async fetchData() {
        const params = new URLSearchParams({
            page: this.currentPage,
            per_page: this.options.perPage,
            ...this.options.filters
        });

        const response = await fetch(`${this.options.endpoint}?${params}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...this.getAuthHeaders()
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        return await response.json();
    }

    renderItems(items) {
        const fragment = document.createDocumentFragment();
        
        items.forEach(item => {
            const element = this.createItemElement(item);
            if (element) {
                fragment.appendChild(element);
            }
        });

        // Insert before sentinel
        this.container.insertBefore(fragment, this.sentinel);

        // Trigger lazy loading for new images
        this.triggerLazyLoading();
    }

    createItemElement(item) {
        if (this.options.itemTemplate) {
            return this.options.itemTemplate(item);
        }

        // Default template for signals
        const element = document.createElement('div');
        element.className = 'scroll-item signal-item';
        element.innerHTML = `
            <div class="signal-card">
                <div class="signal-header">
                    <h3 class="signal-title">${item.title}</h3>
                    <span class="signal-direction signal-direction-${item.direction}">${item.direction.toUpperCase()}</span>
                </div>
                <div class="signal-details">
                    <div class="signal-pair">${item.currency_pair?.name || 'N/A'}</div>
                    <div class="signal-price">Entry: ${item.open_price}</div>
                    <div class="signal-targets">
                        <span>SL: ${item.sl}</span>
                        <span>TP: ${item.tp}</span>
                    </div>
                </div>
                <div class="signal-meta">
                    <span class="signal-market">${item.market?.name || 'N/A'}</span>
                    <span class="signal-timeframe">${item.time_frame?.name || 'N/A'}</span>
                    <span class="signal-date">${new Date(item.published_date).toLocaleDateString()}</span>
                </div>
            </div>
        `;

        return element;
    }

    showLoading() {
        if (this.loadingElement) return;

        this.loadingElement = document.createElement('div');
        this.loadingElement.className = 'infinite-scroll-loading';
        this.loadingElement.innerHTML = this.options.loadingTemplate;
        this.container.insertBefore(this.loadingElement, this.sentinel);
    }

    hideLoading() {
        if (this.loadingElement) {
            this.loadingElement.remove();
            this.loadingElement = null;
        }
    }

    handleError(error) {
        this.hideLoading();

        if (this.retryCount < this.options.maxRetries) {
            this.retryCount++;
            setTimeout(() => {
                this.loadMore();
            }, 1000 * this.retryCount); // Exponential backoff
        } else {
            this.showError(error);
            if (this.options.onError) {
                this.options.onError(error, this);
            }
        }
    }

    showError(error) {
        if (this.errorElement) return;

        this.errorElement = document.createElement('div');
        this.errorElement.className = 'infinite-scroll-error';
        this.errorElement.innerHTML = this.options.errorTemplate.replace('{error}', error.message);
        
        // Add retry button
        const retryButton = this.errorElement.querySelector('.retry-button');
        if (retryButton) {
            retryButton.addEventListener('click', () => {
                this.hideError();
                this.retryCount = 0;
                this.loadMore();
            });
        }

        this.container.insertBefore(this.errorElement, this.sentinel);
    }

    hideError() {
        if (this.errorElement) {
            this.errorElement.remove();
            this.errorElement = null;
        }
    }

    showEmpty() {
        if (this.emptyElement || this.items.length > 0) return;

        this.emptyElement = document.createElement('div');
        this.emptyElement.className = 'infinite-scroll-empty';
        this.emptyElement.innerHTML = this.options.emptyTemplate;
        this.container.insertBefore(this.emptyElement, this.sentinel);
    }

    hideEmpty() {
        if (this.emptyElement) {
            this.emptyElement.remove();
            this.emptyElement = null;
        }
    }

    clearContainer() {
        // Remove all items except sentinel
        const items = this.container.querySelectorAll('.scroll-item, .infinite-scroll-loading, .infinite-scroll-error, .infinite-scroll-empty');
        items.forEach(item => item.remove());
        
        this.hideLoading();
        this.hideError();
        this.hideEmpty();
    }

    triggerLazyLoading() {
        // Trigger lazy loading for new images
        const images = this.container.querySelectorAll('img[data-src]:not([src])');
        images.forEach(img => {
            if (window.lazyLoader) {
                window.lazyLoader.observe(img);
            }
        });
    }

    updateFilters(filters) {
        this.options.filters = { ...this.options.filters, ...filters };
        this.loadInitialData();
    }

    refresh() {
        this.loadInitialData();
    }

    destroy() {
        if (this.observer) {
            this.observer.disconnect();
            this.observer = null;
        }

        if (this.sentinel) {
            this.sentinel.remove();
        }

        this.clearContainer();
    }

    getAuthHeaders() {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const headers = {};
        
        if (token) {
            headers['X-CSRF-TOKEN'] = token;
        }

        // Add Bearer token if available
        const bearerToken = localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token');
        if (bearerToken) {
            headers['Authorization'] = `Bearer ${bearerToken}`;
        }

        return headers;
    }

    getDefaultLoadingTemplate() {
        return `
            <div class="loading-spinner">
                <div class="spinner"></div>
                <span>Loading more items...</span>
            </div>
        `;
    }

    getDefaultErrorTemplate() {
        return `
            <div class="error-message">
                <span>Failed to load more items: {error}</span>
                <button class="retry-button">Retry</button>
            </div>
        `;
    }

    getDefaultEmptyTemplate() {
        return `
            <div class="empty-message">
                <span>No items found</span>
            </div>
        `;
    }
}

// Auto-initialize infinite scroll elements
document.addEventListener('DOMContentLoaded', () => {
    const containers = document.querySelectorAll('[data-infinite-scroll]');
    containers.forEach(container => {
        const options = {
            endpoint: container.dataset.endpoint,
            perPage: parseInt(container.dataset.perPage) || 20,
            threshold: parseInt(container.dataset.threshold) || 200,
        };

        new InfiniteScroll(container, options);
    });
});

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = InfiniteScroll;
}