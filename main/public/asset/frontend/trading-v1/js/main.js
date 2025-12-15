/**
 * Trading V1 Theme - Main JavaScript
 * Handles all interactive elements and animations
 */

(function ($) {
    'use strict';

    // ========================================
    // NAVBAR SCROLL EFFECT
    // ========================================
    const header = $('#header');

    $(window).on('scroll', function () {
        if ($(this).scrollTop() > 50) {
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
    // PROFILE DROPDOWN TOGGLE
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
    // INITIALIZE ON DOCUMENT READY
    // ========================================
    $(document).ready(function () {
        // Add loaded class to body
        $('body').addClass('loaded');

        // Log theme info
        console.log('%c Trading V1 Theme ', 'background: #1AFFD5; color: #121212; padding: 5px 10px; font-weight: bold;');
        console.log('Version: 1.0.0');
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

