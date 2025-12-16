/**
 * Trading V1 Theme - Main JavaScript
 * Handles all interactive elements and animations
 */

(function ($) {
    'use strict';

    // ========================================
    // NAVBAR SCROLL EFFECT - Enhanced
    // ========================================
    const header = $('#header');

    $(window).on('scroll', function () {
        const scrollTop = $(this).scrollTop();

        // Add scrolled class for visual transition
        if (scrollTop > 50) {
            header.addClass('scrolled');
        } else {
            header.removeClass('scrolled');
        }
    });

    // ========================================
    // MOBILE MENU TOGGLE
    // ========================================
    const navToggle = $('#navToggle');
    const navMenu = $('#navMenu');

    navToggle.on('click', function () {
        $(this).toggleClass('active');
        navMenu.toggleClass('show');

        // Animate hamburger icon
        const spans = $(this).find('span');
        if ($(this).hasClass('active')) {
            spans.eq(0).css('transform', 'rotate(45deg) translateY(8px)');
            spans.eq(1).css('opacity', '0');
            spans.eq(2).css('transform', 'rotate(-45deg) translateY(-8px)');
        } else {
            spans.css({
                'transform': 'none',
                'opacity': '1'
            });
        }
    });

    // Close menu when clicking outside
    $(document).on('click', function (e) {
        if (!$(e.target).closest('.tv-navbar').length) {
            navToggle.removeClass('active');
            navMenu.removeClass('show');
            navToggle.find('span').css({
                'transform': 'none',
                'opacity': '1'
            });
        }
    });

    // Close menu when clicking on a link
    navMenu.find('a').on('click', function () {
        navToggle.removeClass('active');
        navMenu.removeClass('show');
        navToggle.find('span').css({
            'transform': 'none',
            'opacity': '1'
        });
    });

    // ========================================
    // SIDEBAR TOGGLE (USER PANEL)
    // ========================================
    const sidebar = $('#sidebar');
    const sidebarToggle = $('#sidebarToggle');
    const sidebarOverlay = $('#sidebarOverlay');

    if (sidebarToggle.length) {
        sidebarToggle.on('click', function () {
            sidebar.toggleClass('show');
            sidebarOverlay.toggleClass('show');
        });

        // Close sidebar button
        $('#sidebarClose').on('click', function () {
            sidebar.removeClass('show');
            sidebarOverlay.removeClass('show');
        });

        // Close sidebar when clicking overlay
        sidebarOverlay.on('click', function () {
            sidebar.removeClass('show');
            sidebarOverlay.removeClass('show');
        });

        // Close sidebar when clicking outside on mobile
        $(document).on('click', function (e) {
            if ($(window).width() < 1024) {
                if (!$(e.target).closest('.tv-sidebar, #sidebarToggle').length) {
                    sidebar.removeClass('show');
                    sidebarOverlay.removeClass('show');
                }
            }
        });
    }

    // ========================================
    // SIDEBAR SUBMENU TOGGLE
    // ========================================
    $('.tv-has-submenu').on('click', function (e) {
        e.preventDefault();
        const $parent = $(this).closest('.tv-sidebar-submenu');

        // Toggle current submenu
        $parent.toggleClass('open');

        // Close other submenus (optional - remove if you want multiple open)
        // $parent.siblings('.tv-sidebar-submenu').removeClass('open');
    });

    // Auto-open submenu if current page is in it
    $('.tv-sidebar-submenu').each(function () {
        const hasActive = $(this).find('.tv-submenu-link.active').length > 0;
        if (hasActive) {
            $(this).addClass('open');
        }
    });

    // ========================================
    // PROFILE DROPDOWN TOGGLE (Desktop)
    // ========================================
    const profileToggle = $('#profileToggle');
    const profileDropdown = $('#profileDropdown');

    if (profileToggle.length) {
        profileToggle.on('click', function (e) {
            e.stopPropagation();
            profileDropdown.toggleClass('show');
        });

        // Close dropdown when clicking outside
        $(document).on('click', function (e) {
            if (!$(e.target).closest('#profileToggle, #profileDropdown').length) {
                profileDropdown.removeClass('show');
            }
        });
    }

    // ========================================
    // MOBILE PROFILE DROPDOWN TOGGLE
    // ========================================
    const mobileProfileToggle = $('#mobileProfileToggle');
    const mobileProfileDropdown = $('#mobileProfileDropdown');

    if (mobileProfileToggle.length) {
        mobileProfileToggle.on('click', function (e) {
            e.stopPropagation();
            $(this).toggleClass('active');
            mobileProfileDropdown.toggleClass('show');
        });

        // Close dropdown when clicking outside
        $(document).on('click', function (e) {
            if (!$(e.target).closest('#mobileProfileToggle, #mobileProfileDropdown').length) {
                mobileProfileToggle.removeClass('active');
                mobileProfileDropdown.removeClass('show');
            }
        });

        // Close dropdown when clicking on a link
        mobileProfileDropdown.find('a').on('click', function () {
            mobileProfileToggle.removeClass('active');
            mobileProfileDropdown.removeClass('show');
        });
    }

    // ========================================
    // SMOOTH SCROLL FOR ANCHOR LINKS
    // ========================================
    $('a[href^="#"]').on('click', function (e) {
        const target = $(this.hash);
        if (target.length) {
            e.preventDefault();
            $('html, body').animate({
                scrollTop: target.offset().top - 80
            }, 800);
        }
    });

    // ========================================
    // FADE IN ANIMATION ON SCROLL
    // ========================================
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function (entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-fade-in');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    // Observe elements
    $('.tv-feature-card, .tv-pricing-card, .tv-market-card, .tv-stat-card').each(function () {
        observer.observe(this);
    });

    // ========================================
    // FORM VALIDATION
    // ========================================
    $('form').on('submit', function (e) {
        const form = $(this);
        let isValid = true;

        // Check required fields
        form.find('[required]').each(function () {
            const input = $(this);
            if (!input.val()) {
                isValid = false;
                input.addClass('error');
            } else {
                input.removeClass('error');
            }
        });

        // Email validation
        form.find('input[type="email"]').each(function () {
            const email = $(this).val();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (email && !emailRegex.test(email)) {
                isValid = false;
                $(this).addClass('error');
            }
        });

        // Password confirmation
        const password = form.find('input[name="password"]').val();
        const passwordConfirm = form.find('input[name="password_confirmation"]').val();
        if (password && passwordConfirm && password !== passwordConfirm) {
            isValid = false;
            form.find('input[name="password_confirmation"]').addClass('error');
            if (typeof toastr !== 'undefined') {
                toastr.error('Passwords do not match');
            }
        }

        if (!isValid) {
            e.preventDefault();
            if (typeof toastr !== 'undefined') {
                toastr.error('Please fill in all required fields correctly');
            }
        }
    });

    // Remove error class on input
    $('input, textarea, select').on('input change', function () {
        $(this).removeClass('error');
    });

    // ========================================
    // COPY TO CLIPBOARD
    // ========================================
    $('.copy-btn').on('click', function () {
        const text = $(this).data('copy');
        const btn = $(this);

        // Create temporary input
        const temp = $('<input>');
        $('body').append(temp);
        temp.val(text).select();
        document.execCommand('copy');
        temp.remove();

        // Show feedback
        const originalText = btn.html();
        btn.html('<i class="fas fa-check"></i> Copied!');
        setTimeout(() => {
            btn.html(originalText);
        }, 2000);

        if (typeof toastr !== 'undefined') {
            toastr.success('Copied to clipboard');
        }
    });

    // ========================================
    // TOOLTIPS
    // ========================================
    $('[data-tooltip]').each(function () {
        const element = $(this);
        const text = element.data('tooltip');

        element.on('mouseenter', function () {
            const tooltip = $('<div class="tv-tooltip">' + text + '</div>');
            $('body').append(tooltip);

            const pos = element.offset();
            tooltip.css({
                top: pos.top - tooltip.outerHeight() - 10,
                left: pos.left + (element.outerWidth() / 2) - (tooltip.outerWidth() / 2)
            });

            setTimeout(() => tooltip.addClass('show'), 10);
        });

        element.on('mouseleave', function () {
            $('.tv-tooltip').removeClass('show');
            setTimeout(() => $('.tv-tooltip').remove(), 300);
        });
    });

    // ========================================
    // TABLE RESPONSIVE WRAPPER
    // ========================================
    $('.tv-table').each(function () {
        if (!$(this).parent().hasClass('table-responsive')) {
            $(this).wrap('<div class="table-responsive"></div>');
        }
    });

    // ========================================
    // AUTO-HIDE ALERTS
    // ========================================
    $('.tv-alert').each(function () {
        const alert = $(this);
        if (alert.data('auto-hide')) {
            setTimeout(() => {
                alert.fadeOut();
            }, 5000);
        }
    });

    // ========================================
    // LOADING SPINNER
    // ========================================
    window.showLoading = function () {
        if (!$('#loading-overlay').length) {
            $('body').append(`
                <div id="loading-overlay" style="
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(0, 0, 0, 0.8);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 9999;
                ">
                    <div style="
                        width: 50px;
                        height: 50px;
                        border: 3px solid rgba(26, 255, 213, 0.3);
                        border-top-color: #1AFFD5;
                        border-radius: 50%;
                        animation: spin 0.8s linear infinite;
                    "></div>
                </div>
            `);
        }
    };

    window.hideLoading = function () {
        $('#loading-overlay').fadeOut(300, function () {
            $(this).remove();
        });
    };

    // ========================================
    // AJAX SETUP
    // ========================================
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // ========================================
    // TOASTR CONFIGURATION
    // ========================================
    if (typeof toastr !== 'undefined') {
        toastr.options = {
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right',
            timeOut: 5000,
            extendedTimeOut: 1000,
            showEasing: 'swing',
            hideEasing: 'linear',
            showMethod: 'fadeIn',
            hideMethod: 'fadeOut'
        };
    }

    // ========================================
    // FAKE ORDER POPUP NOTIFICATIONS
    // ========================================
    const fakeOrderNotifications = {
        templates: [
            {
                type: 'purchase',
                icon: 'fa-shopping-cart',
                color: '#1AFFD5',
                messages: [
                    '{name} telah membeli paket {plan}',
                    '{name} baru saja berlangganan {plan}',
                    '{name} membeli paket {plan} monthly'
                ],
                plans: ['Basic', 'Premium', 'Pro', 'Enterprise', 'Starter']
            },
            {
                type: 'trial',
                icon: 'fa-gift',
                color: '#3AFFE2',
                messages: [
                    '{name} mendaftar free trial',
                    '{name} memulai free trial',
                    '{name} mencoba free trial'
                ]
            },
            {
                type: 'liquidation',
                icon: 'fa-exclamation-triangle',
                color: '#FF1D48',
                messages: [
                    '{name} liquidation triggered',
                    '{name} mengalami liquidation',
                    'Liquidation untuk {name}'
                ]
            },
            {
                type: 'profit',
                icon: 'fa-chart-line',
                color: '#3AFFE2',
                messages: [
                    '{name} profits {amount}+ USD from {pair}',
                    '{name} mendapat profit {amount}+ USD dari {pair}',
                    'Profit {amount}+ USD untuk {name} dari {pair}'
                ],
                pairs: ['XAUUSD', 'EURUSD', 'GBPUSD', 'BTCUSD', 'ETHUSD', 'XAUUSD', 'GBPJPY']
            },
            {
                type: 'copy',
                icon: 'fa-users',
                color: '#1AFFD5',
                messages: [
                    '{name} copy trade {trader}',
                    '{name} mengikuti trading {trader}',
                    '{name} mulai copy trading {trader}'
                ]
            },
            {
                type: 'signal',
                icon: 'fa-bell',
                color: '#F59E0B',
                messages: [
                    'Signal baru untuk {pair}',
                    'New signal: {pair}',
                    'Signal {pair} telah dipublikasi'
                ],
                pairs: ['EUR/USD', 'GBP/USD', 'XAU/USD', 'BTC/USD', 'ETH/USD']
            }
        ],

        names: [
            'John', 'Sarah', 'Michael', 'Emma', 'David', 'Lisa', 'James', 'Maria',
            'Robert', 'Anna', 'William', 'Sophia', 'Richard', 'Olivia', 'Daniel', 'Isabella',
            'Ahmad', 'Siti', 'Budi', 'Dewi', 'Andi', 'Rina', 'Eko', 'Lina',
            'Rizki', 'Putri', 'Fajar', 'Sari', 'Dedi', 'Maya', 'Hadi', 'Nina'
        ],

        activeNotifications: [],
        maxNotifications: 3,
        interval: null,

        init: function () {
            // Create notification container
            if (!$('#fakeOrderNotifications').length) {
                $('body').append(`
                    <div id="fakeOrderNotifications" style="
                        position: fixed;
                        bottom: 100px;
                        right: 20px;
                        z-index: 10000;
                        display: flex;
                        flex-direction: column;
                        gap: 12px;
                        max-width: 350px;
                        pointer-events: none;
                    "></div>
                `);
            }

            // Start showing notifications after 5 seconds
            setTimeout(() => {
                this.showRandomNotification();
                this.interval = setInterval(() => {
                    this.showRandomNotification();
                }, 8000 + Math.random() * 7000); // 8-15 seconds
            }, 5000);
        },

        showRandomNotification: function () {
            if (this.activeNotifications.length >= this.maxNotifications) {
                return;
            }

            const template = this.templates[Math.floor(Math.random() * this.templates.length)];
            const name = this.names[Math.floor(Math.random() * this.names.length)];

            let message = template.messages[Math.floor(Math.random() * template.messages.length)];

            // Replace placeholders
            message = message.replace('{name}', name);

            if (template.type === 'purchase' && template.plans) {
                const plan = template.plans[Math.floor(Math.random() * template.plans.length)];
                message = message.replace('{plan}', plan);
            }

            if (template.type === 'profit' && template.pairs) {
                const pair = template.pairs[Math.floor(Math.random() * template.pairs.length)];
                const amount = (Math.random() * 500 + 50).toFixed(0);
                message = message.replace('{amount}', amount).replace('{pair}', pair);
            }

            if (template.type === 'copy') {
                const trader = this.names[Math.floor(Math.random() * this.names.length)];
                message = message.replace('{trader}', trader);
            }

            if (template.type === 'signal' && template.pairs) {
                const pair = template.pairs[Math.floor(Math.random() * template.pairs.length)];
                message = message.replace('{pair}', pair);
            }

            this.createNotification(message, template.icon, template.color, template.type);
        },

        createNotification: function (message, icon, color, type) {
            const id = 'notif_' + Date.now();
            const notification = $(`
                <div class="fake-order-notification" data-id="${id}" style="
                    background: linear-gradient(135deg, rgba(18, 18, 18, 0.95) 0%, rgba(30, 30, 30, 0.95) 100%);
                    border: 1px solid ${color}40;
                    border-left: 3px solid ${color};
                    border-radius: 12px;
                    padding: 16px;
                    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3), 0 0 20px ${color}20;
                    backdrop-filter: blur(10px);
                    transform: translateX(400px);
                    opacity: 0;
                    transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
                    pointer-events: auto;
                    cursor: pointer;
                ">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="
                            width: 40px;
                            height: 40px;
                            border-radius: 50%;
                            background: ${color}20;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            flex-shrink: 0;
                        ">
                            <i class="fas ${icon}" style="color: ${color}; font-size: 18px;"></i>
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            <div style="
                                color: #FFFFFF;
                                font-size: 14px;
                                font-weight: 500;
                                line-height: 1.4;
                                font-family: var(--tv-font-body, 'Inter', sans-serif);
                            ">${message}</div>
                            <div style="
                                color: #666D80;
                                font-size: 12px;
                                margin-top: 4px;
                            ">${this.getTimeAgo()}</div>
                        </div>
                        <button class="notif-close" style="
                            background: none;
                            border: none;
                            color: #666D80;
                            cursor: pointer;
                            padding: 4px;
                            opacity: 0.6;
                            transition: opacity 0.2s;
                        " onclick="fakeOrderNotifications.remove('${id}')">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            `);

            $('#fakeOrderNotifications').append(notification);
            this.activeNotifications.push(id);

            // Animate in
            setTimeout(() => {
                notification.css({
                    transform: 'translateX(0)',
                    opacity: 1
                });
            }, 10);

            // Auto remove after 6 seconds
            setTimeout(() => {
                this.remove(id);
            }, 6000);

            // Hover effect
            notification.on('mouseenter', function () {
                $(this).css('transform', 'translateX(-5px) scale(1.02)');
            }).on('mouseleave', function () {
                $(this).css('transform', 'translateX(0) scale(1)');
            });
        },

        remove: function (id) {
            const notification = $(`.fake-order-notification[data-id="${id}"]`);
            if (notification.length) {
                notification.css({
                    transform: 'translateX(400px)',
                    opacity: 0
                });

                setTimeout(() => {
                    notification.remove();
                    this.activeNotifications = this.activeNotifications.filter(n => n !== id);
                }, 400);
            }
        },

        getTimeAgo: function () {
            const times = ['baru saja', '1 menit lalu', '2 menit lalu', '3 menit lalu'];
            return times[Math.floor(Math.random() * times.length)];
        }
    };

    // ========================================
    // BOOTSTRAP TAB COMPATIBILITY
    // ========================================
    $(document).on('click', '[data-bs-toggle="tab"]', function (e) {
        e.preventDefault();
        const target = $(this).attr('href');

        // Remove active from all tabs and panes
        $(this).closest('.nav-pills').find('.nav-link').removeClass('active');
        $(this).addClass('active');

        // Show target pane
        $(target).closest('.tab-content').find('.tab-pane').removeClass('active show');
        $(target).addClass('active show');
    });

    // ========================================
    // HERO TYPING ANIMATION
    // ========================================
    const heroTyping = {
        element: null,
        phrases: [
            'master yourself!',
            'maximize your profits!',
            'trade with confidence!',
            'achieve financial freedom!',
            'join 1M+ traders!',
            'unlock your potential!'
        ],
        currentPhraseIndex: 0,
        currentCharIndex: 0,
        isDeleting: false,
        isPaused: false,
        typingSpeed: 100,
        deletingSpeed: 50,
        pauseTime: 2000,

        init: function () {
            this.element = document.getElementById('heroTyping');
            if (!this.element) return;

            // Start typing
            setTimeout(() => this.type(), 500);
        },

        type: function () {
            if (this.isPaused) return;

            const currentPhrase = this.phrases[this.currentPhraseIndex];

            if (this.isDeleting) {
                // Delete character
                this.currentCharIndex--;
                this.element.textContent = currentPhrase.substring(0, this.currentCharIndex);

                if (this.currentCharIndex === 0) {
                    this.isDeleting = false;
                    this.currentPhraseIndex = (this.currentPhraseIndex + 1) % this.phrases.length;
                    setTimeout(() => this.type(), 500);
                } else {
                    setTimeout(() => this.type(), this.deletingSpeed);
                }
            } else {
                // Type character
                this.currentCharIndex++;
                this.element.textContent = currentPhrase.substring(0, this.currentCharIndex);

                if (this.currentCharIndex === currentPhrase.length) {
                    // Phrase is complete, pause before deleting
                    this.isDeleting = true;
                    setTimeout(() => this.type(), this.pauseTime);
                } else {
                    setTimeout(() => this.type(), this.typingSpeed);
                }
            }
        },

        pause: function () {
            this.isPaused = true;
            // If currently typing, finish the current phrase
            if (!this.isDeleting && this.currentCharIndex < this.phrases[this.currentPhraseIndex].length) {
                const currentPhrase = this.phrases[this.currentPhraseIndex];
                this.element.textContent = currentPhrase;
                this.currentCharIndex = currentPhrase.length;
            }
        }
    };

    // ========================================
    // STAT COUNTERS - Random Increasing Animation
    // ========================================
    const statCounters = {
        counters: [],

        init: function () {
            this.counters = document.querySelectorAll('.tv-stat-number');
            if (this.counters.length === 0) return;

            // Set up intersection observer
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting && !entry.target.classList.contains('counted')) {
                        this.animateCounter(entry.target);
                        entry.target.classList.add('counted');
                    }
                });
            }, { threshold: 0.5 });

            this.counters.forEach(counter => observer.observe(counter));
        },

        animateCounter: function (element) {
            const target = parseFloat(element.getAttribute('data-target'));
            const prefix = element.getAttribute('data-prefix') || '';
            const suffix = element.getAttribute('data-suffix') || '';
            const duration = 2000; // 2 seconds
            const steps = 60;
            const increment = target / steps;
            let current = 0;
            let step = 0;

            const timer = setInterval(() => {
                step++;
                // Add randomness but ensure it increases
                const randomFactor = 0.8 + (Math.random() * 0.4); // 0.8 to 1.2
                current += increment * randomFactor;

                // Ensure we don't exceed target
                if (current >= target || step >= steps) {
                    current = target;
                    clearInterval(timer);
                }

                // Format number
                let displayValue;
                if (target >= 1000000) {
                    // For millions, show as 1M+
                    displayValue = (current / 1000000).toFixed(1);
                    if (current >= target) displayValue = (target / 1000000).toFixed(0);
                    element.textContent = prefix + displayValue + 'M' + suffix;
                } else if (target >= 1000) {
                    // For thousands, show with commas
                    displayValue = Math.floor(current).toLocaleString();
                    element.textContent = prefix + displayValue + suffix;
                } else {
                    // For small numbers, show with decimal
                    displayValue = current.toFixed(1);
                    if (current >= target) displayValue = target.toFixed(1);
                    element.textContent = prefix + displayValue + suffix;
                }
            }, duration / steps);
        }
    };

    // ========================================
    // SCROLL REVEAL ANIMATIONS
    // ========================================
    const scrollReveal = {
        elements: [],

        init: function () {
            // Get all elements with scroll-reveal classes
            this.elements = document.querySelectorAll('[class*="scroll-reveal"]');

            if (this.elements.length === 0) return;

            // Check if user prefers reduced motion
            const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            if (prefersReducedMotion) {
                this.elements.forEach(el => el.classList.add('revealed'));
                return;
            }

            // Create intersection observer
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('revealed');
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            });

            // Observe all elements
            this.elements.forEach(el => observer.observe(el));
        }
    };

    // ========================================
    // INITIALIZE ON DOCUMENT READY
    // ========================================
    $(document).ready(function () {
        // Add loaded class to body
        $('body').addClass('loaded');

        // Log theme info
        console.log('%c Trading V1 Theme ', 'background: #1AFFD5; color: #121212; padding: 5px 10px; font-weight: bold;');
        console.log('Version: 1.0.0');

        // Initialize hero typing animation
        heroTyping.init();

        // Initialize scroll reveal animations
        scrollReveal.init();

        // Initialize stat counters
        statCounters.init();

        // Initialize fake order notifications (only on home page)
        if ($('#market-trends').length) {
            fakeOrderNotifications.init();
        }
    });

})(jQuery);

// ========================================
// SPINNER ANIMATION CSS
// ========================================
if (!document.getElementById('spinner-style')) {
    const style = document.createElement('style');
    style.id = 'spinner-style';
    style.textContent = `
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    `;
    document.head.appendChild(style);
}

