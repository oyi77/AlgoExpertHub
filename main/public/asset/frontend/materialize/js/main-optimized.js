'use strict';

/*
=====================================
Preloader js
=====================================
*/
document.addEventListener('DOMContentLoaded', function() {
    const preloader = document.querySelector('.preloader-holder');
    if (preloader) {
        setTimeout(() => {
            preloader.style.opacity = '0';
            setTimeout(() => {
                preloader.style.display = 'none';
            }, 300);
        }, 300);
    }
});

/*
=====================================
Sticky Header When Scroll - Vanilla JS
=====================================
*/
let lastScroll = 0;
window.addEventListener('scroll', function() {
    const scroll = window.pageYOffset || document.documentElement.scrollTop;
    const header = document.querySelector('.sp_header');
    
    if (header) {
        if (scroll >= 50) {
            header.classList.add('header-fixed');
        } else {
            header.classList.remove('header-fixed');
        }
    }
    lastScroll = scroll;
});

/*
=====================================
Scroll Animation - Intersection Observer (replaces WOW.js)
=====================================
*/
function initScrollAnimations() {
    const animatedElements = document.querySelectorAll('[data-animate]');
    
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const element = entry.target;
                    const animation = element.getAttribute('data-animate') || 'fadeInUp';
                    element.classList.add('animated', animation);
                    observer.unobserve(element);
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        });

        animatedElements.forEach(el => observer.observe(el));
    } else {
        // Fallback for older browsers
        animatedElements.forEach(el => {
            el.classList.add('animated', el.getAttribute('data-animate') || 'fadeInUp');
        });
    }
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', initScrollAnimations);

/*
=====================================
Parallax Effect - Vanilla JS (replaces Paroller)
=====================================
*/
function initParallax() {
    const parallaxElements = document.querySelectorAll('[data-parallax]');
    
    if (window.innerWidth <= 991) return; // Disable on mobile
    
    window.addEventListener('scroll', () => {
        const scrolled = window.pageYOffset;
        
        parallaxElements.forEach(element => {
            const factor = parseFloat(element.getAttribute('data-parallax')) || 0.5;
            const yPos = -(scrolled * factor);
            element.style.transform = `translate3d(0, ${yPos}px, 0)`;
        });
    });
}

document.addEventListener('DOMContentLoaded', initParallax);

/*
=====================================
Banner section mouse hover moving - Vanilla JS (replaces TweenMax)
=====================================
*/
function initBannerParallax() {
    const banner = document.querySelector('.sp_banner');
    const bannerImg = document.querySelector('.sp_banner_img');
    
    if (!banner || !bannerImg) return;
    
    banner.addEventListener('mousemove', function(e) {
        const rect = banner.getBoundingClientRect();
        const relX = e.clientX - rect.left;
        const relY = e.clientY - rect.top;
        const movement = -90;
        
        const x = (relX - rect.width / 2) / rect.width * movement;
        const y = (relY - rect.height / 2) / rect.height * movement;
        
        bannerImg.style.transform = `translate(${x}px, ${y}px)`;
        bannerImg.style.transition = 'transform 0.1s ease-out';
    });
    
    banner.addEventListener('mouseleave', function() {
        bannerImg.style.transform = 'translate(0, 0)';
        bannerImg.style.transition = 'transform 0.5s ease-out';
    });
}

document.addEventListener('DOMContentLoaded', initBannerParallax);

/*
=====================================
Counter Odometer - Vanilla JS with Intersection Observer
=====================================
*/
function initCounters() {
    const counters = document.querySelectorAll('[data-counter]');
    
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const counter = entry.target;
                    const target = parseFloat(counter.getAttribute('data-counter')) || 0;
                    animateCounter(counter, target);
                    observer.unobserve(counter);
                }
            });
        }, { threshold: 0.5 });

        counters.forEach(counter => observer.observe(counter));
    } else {
        counters.forEach(counter => {
            const target = parseFloat(counter.getAttribute('data-counter')) || 0;
            counter.textContent = target;
        });
    }
}

function animateCounter(element, target) {
    let current = 0;
    const increment = target / 100;
    const duration = 2000;
    const stepTime = duration / 100;
    
    const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
            element.textContent = Math.round(target);
            clearInterval(timer);
        } else {
            element.textContent = Math.round(current);
        }
    }, stepTime);
}

document.addEventListener('DOMContentLoaded', initCounters);

/*
=====================================
Sidebar Toggler
=====================================
*/
document.addEventListener('DOMContentLoaded', function() {
    const toggler = document.querySelector('.sidebar-toggeler');
    const sidebar = document.querySelector('.user-sidebar');
    
    if (toggler && sidebar) {
        toggler.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
});

/*
=====================================
Testimonial Slider - Vanilla JS (optional Slick replacement)
=====================================
*/
function initTestimonialSlider() {
    const contentSlider = document.querySelector('.sp_testimonial_content_slider');
    const thumbSlider = document.querySelector('.sp_testimonial_thumb_slider');
    const prevBtn = document.querySelector('.testi-prev');
    const nextBtn = document.querySelector('.testi-next');
    
    if (!contentSlider || !prevBtn || !nextBtn) return;
    
    let currentSlide = 0;
    const slides = contentSlider.querySelectorAll('.sp_testimonial_content');
    const thumbs = thumbSlider ? thumbSlider.querySelectorAll('.sp_testimonial_thumb_slide') : [];
    const totalSlides = slides.length;
    
    function showSlide(index) {
        // Wrap around
        if (index >= totalSlides) index = 0;
        if (index < 0) index = totalSlides - 1;
        
        currentSlide = index;
        
        // Hide all slides
        slides.forEach(slide => {
            slide.style.display = 'none';
            slide.classList.remove('active');
        });
        
        // Show current slide
        if (slides[currentSlide]) {
            slides[currentSlide].style.display = 'block';
            slides[currentSlide].classList.add('active');
        }
        
        // Update thumbs
        thumbs.forEach((thumb, i) => {
            thumb.classList.remove('active');
            if (i === currentSlide) {
                thumb.classList.add('active');
            }
        });
    }
    
    // Navigation
    if (nextBtn) {
        nextBtn.addEventListener('click', () => showSlide(currentSlide + 1));
    }
    if (prevBtn) {
        prevBtn.addEventListener('click', () => showSlide(currentSlide - 1));
    }
    
    // Initialize
    showSlide(0);
    
    // Auto-play (optional)
    // setInterval(() => showSlide(currentSlide + 1), 5000);
}

document.addEventListener('DOMContentLoaded', initTestimonialSlider);


