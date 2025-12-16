/**
 * Lazy Loading Component
 * Handles lazy loading of images and content with performance optimizations
 */
class LazyLoader {
    constructor(options = {}) {
        this.options = {
            imageSelector: options.imageSelector || 'img[data-src]',
            contentSelector: options.contentSelector || '[data-lazy-content]',
            rootMargin: options.rootMargin || '50px',
            threshold: options.threshold || 0.1,
            fadeInDuration: options.fadeInDuration || 300,
            placeholder: options.placeholder || 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzIwIiBoZWlnaHQ9IjE4MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkxvYWRpbmcuLi48L3RleHQ+PC9zdmc+',
            errorPlaceholder: options.errorPlaceholder || 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzIwIiBoZWlnaHQ9IjE4MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjVmNWY1Ii8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iI2NjYyIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkVycm9yPC90ZXh0Pjwvc3ZnPg==',
            retryAttempts: options.retryAttempts || 3,
            retryDelay: options.retryDelay || 1000,
            onLoad: options.onLoad || null,
            onError: options.onError || null,
            enableSkeleton: options.enableSkeleton !== false,
            skeletonClass: options.skeletonClass || 'skeleton-loading',
            ...options
        };

        this.observer = null;
        this.loadedImages = new Set();
        this.failedImages = new Set();
        this.retryCount = new Map();

        this.init();
    }

    init() {
        if (!('IntersectionObserver' in window)) {
            // Fallback for older browsers
            this.loadAllImages();
            return;
        }

        this.createObserver();
        this.observeElements();
    }

    createObserver() {
        const options = {
            root: null,
            rootMargin: this.options.rootMargin,
            threshold: this.options.threshold
        };

        this.observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.loadElement(entry.target);
                    this.observer.unobserve(entry.target);
                }
            });
        }, options);
    }

    observeElements() {
        // Observe images
        const images = document.querySelectorAll(this.options.imageSelector);
        images.forEach(img => this.observe(img));

        // Observe content elements
        const contentElements = document.querySelectorAll(this.options.contentSelector);
        contentElements.forEach(element => this.observe(element));
    }

    observe(element) {
        if (!this.observer) return;

        // Add skeleton loading if enabled
        if (this.options.enableSkeleton && element.tagName === 'IMG') {
            this.addSkeleton(element);
        }

        this.observer.observe(element);
    }

    loadElement(element) {
        if (element.tagName === 'IMG') {
            this.loadImage(element);
        } else if (element.hasAttribute('data-lazy-content')) {
            this.loadContent(element);
        }
    }

    async loadImage(img) {
        const src = img.dataset.src;
        if (!src || this.loadedImages.has(src)) return;

        // Remove skeleton
        this.removeSkeleton(img);

        try {
            await this.preloadImage(src);
            
            // Set the source
            img.src = src;
            img.removeAttribute('data-src');
            
            // Add loaded class and fade in
            img.classList.add('lazy-loaded');
            this.fadeIn(img);
            
            this.loadedImages.add(src);
            
            if (this.options.onLoad) {
                this.options.onLoad(img, src);
            }

        } catch (error) {
            this.handleImageError(img, src, error);
        }
    }

    async preloadImage(src) {
        return new Promise((resolve, reject) => {
            const img = new Image();
            
            img.onload = () => resolve(img);
            img.onerror = () => reject(new Error(`Failed to load image: ${src}`));
            
            // Set a timeout for slow loading images
            setTimeout(() => {
                reject(new Error(`Image load timeout: ${src}`));
            }, 10000);
            
            img.src = src;
        });
    }

    handleImageError(img, src, error) {
        console.warn('LazyLoader: Image load failed', src, error);
        
        const retryCount = this.retryCount.get(src) || 0;
        
        if (retryCount < this.options.retryAttempts) {
            // Retry loading
            this.retryCount.set(src, retryCount + 1);
            setTimeout(() => {
                this.loadImage(img);
            }, this.options.retryDelay * (retryCount + 1));
        } else {
            // Use error placeholder
            img.src = this.options.errorPlaceholder;
            img.classList.add('lazy-error');
            this.failedImages.add(src);
            
            if (this.options.onError) {
                this.options.onError(img, src, error);
            }
        }
    }

    async loadContent(element) {
        const contentUrl = element.dataset.lazyContent;
        if (!contentUrl) return;

        element.classList.add('lazy-loading');

        try {
            const response = await fetch(contentUrl, {
                headers: {
                    'Accept': 'text/html,application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const content = await response.text();
            element.innerHTML = content;
            element.classList.remove('lazy-loading');
            element.classList.add('lazy-loaded');
            element.removeAttribute('data-lazy-content');

            // Observe any new lazy elements in the loaded content
            this.observeNewElements(element);

        } catch (error) {
            console.error('LazyLoader: Content load failed', contentUrl, error);
            element.classList.remove('lazy-loading');
            element.classList.add('lazy-error');
            element.innerHTML = '<div class="lazy-error-message">Failed to load content</div>';
        }
    }

    observeNewElements(container) {
        const newImages = container.querySelectorAll(this.options.imageSelector);
        newImages.forEach(img => this.observe(img));

        const newContentElements = container.querySelectorAll(this.options.contentSelector);
        newContentElements.forEach(element => this.observe(element));
    }

    addSkeleton(img) {
        if (img.classList.contains(this.options.skeletonClass)) return;

        img.classList.add(this.options.skeletonClass);
        
        // Set placeholder if not already set
        if (!img.src || img.src === window.location.href) {
            img.src = this.options.placeholder;
        }
    }

    removeSkeleton(img) {
        img.classList.remove(this.options.skeletonClass);
    }

    fadeIn(element) {
        if (!this.options.fadeInDuration) return;

        element.style.opacity = '0';
        element.style.transition = `opacity ${this.options.fadeInDuration}ms ease-in-out`;
        
        // Force reflow
        element.offsetHeight;
        
        element.style.opacity = '1';
        
        // Clean up after animation
        setTimeout(() => {
            element.style.transition = '';
        }, this.options.fadeInDuration);
    }

    loadAllImages() {
        // Fallback for browsers without IntersectionObserver
        const images = document.querySelectorAll(this.options.imageSelector);
        images.forEach(img => this.loadImage(img));

        const contentElements = document.querySelectorAll(this.options.contentSelector);
        contentElements.forEach(element => this.loadContent(element));
    }

    refresh() {
        this.observeElements();
    }

    destroy() {
        if (this.observer) {
            this.observer.disconnect();
            this.observer = null;
        }
        
        this.loadedImages.clear();
        this.failedImages.clear();
        this.retryCount.clear();
    }

    // Static methods for manual control
    static loadImage(img) {
        const loader = new LazyLoader();
        loader.loadImage(img);
    }

    static loadContent(element) {
        const loader = new LazyLoader();
        loader.loadContent(element);
    }
}

/**
 * Skeleton Loading Component
 */
class SkeletonLoader {
    constructor(options = {}) {
        this.options = {
            selector: options.selector || '.skeleton-placeholder',
            animationType: options.animationType || 'pulse', // pulse, wave, shimmer
            duration: options.duration || 1500,
            delay: options.delay || 100,
            ...options
        };

        this.init();
    }

    init() {
        this.createSkeletons();
    }

    createSkeletons() {
        const placeholders = document.querySelectorAll(this.options.selector);
        placeholders.forEach((placeholder, index) => {
            this.createSkeleton(placeholder, index);
        });
    }

    createSkeleton(placeholder, index) {
        const type = placeholder.dataset.skeletonType || 'text';
        const lines = parseInt(placeholder.dataset.skeletonLines) || 3;
        const width = placeholder.dataset.skeletonWidth || '100%';
        const height = placeholder.dataset.skeletonHeight || '1em';

        placeholder.classList.add('skeleton-container');
        placeholder.innerHTML = this.generateSkeletonHTML(type, lines, width, height);

        // Add animation delay for staggered effect
        if (this.options.delay > 0) {
            placeholder.style.animationDelay = `${index * this.options.delay}ms`;
        }
    }

    generateSkeletonHTML(type, lines, width, height) {
        switch (type) {
            case 'card':
                return `
                    <div class="skeleton-item skeleton-image" style="height: 200px; margin-bottom: 12px;"></div>
                    <div class="skeleton-item skeleton-text" style="width: 80%; margin-bottom: 8px;"></div>
                    <div class="skeleton-item skeleton-text" style="width: 60%; margin-bottom: 8px;"></div>
                    <div class="skeleton-item skeleton-text" style="width: 40%;"></div>
                `;
            
            case 'list-item':
                return `
                    <div class="skeleton-list-item">
                        <div class="skeleton-item skeleton-avatar" style="width: 40px; height: 40px; border-radius: 50%; margin-right: 12px;"></div>
                        <div class="skeleton-content">
                            <div class="skeleton-item skeleton-text" style="width: 70%; margin-bottom: 4px;"></div>
                            <div class="skeleton-item skeleton-text" style="width: 50%;"></div>
                        </div>
                    </div>
                `;
            
            case 'table-row':
                const columns = parseInt(placeholder.dataset.skeletonColumns) || 4;
                let columnsHTML = '';
                for (let i = 0; i < columns; i++) {
                    const columnWidth = Math.random() * 30 + 60; // Random width between 60-90%
                    columnsHTML += `<div class="skeleton-item skeleton-text" style="width: ${columnWidth}%;"></div>`;
                }
                return `<div class="skeleton-table-row">${columnsHTML}</div>`;
            
            default: // text
                let textHTML = '';
                for (let i = 0; i < lines; i++) {
                    const lineWidth = i === lines - 1 ? Math.random() * 40 + 40 : Math.random() * 20 + 80;
                    textHTML += `<div class="skeleton-item skeleton-text" style="width: ${lineWidth}%; margin-bottom: 8px;"></div>`;
                }
                return textHTML;
        }
    }

    remove(placeholder) {
        placeholder.classList.remove('skeleton-container');
        placeholder.innerHTML = '';
    }

    removeAll() {
        const skeletons = document.querySelectorAll('.skeleton-container');
        skeletons.forEach(skeleton => this.remove(skeleton));
    }
}

// Auto-initialize lazy loading
document.addEventListener('DOMContentLoaded', () => {
    // Initialize lazy loader
    window.lazyLoader = new LazyLoader();
    
    // Initialize skeleton loader
    window.skeletonLoader = new SkeletonLoader();
    
    // Handle dynamic content
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType === Node.ELEMENT_NODE) {
                    // Observe new lazy images
                    const lazyImages = node.querySelectorAll('img[data-src]');
                    lazyImages.forEach(img => window.lazyLoader.observe(img));
                    
                    // Observe new lazy content
                    const lazyContent = node.querySelectorAll('[data-lazy-content]');
                    lazyContent.forEach(element => window.lazyLoader.observe(element));
                    
                    // Create new skeletons
                    const skeletonPlaceholders = node.querySelectorAll('.skeleton-placeholder');
                    skeletonPlaceholders.forEach((placeholder, index) => {
                        window.skeletonLoader.createSkeleton(placeholder, index);
                    });
                }
            });
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
});

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { LazyLoader, SkeletonLoader };
}