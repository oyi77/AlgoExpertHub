(function() {
    'use strict';

    window.AdminCore = {
        theme: {
            init: function() {
                const toggleBtn = document.getElementById('theme-toggle');
                
                const savedTheme = localStorage.getItem('theme') || 'light';
                this.apply(savedTheme);
                this.updateIcons(savedTheme);

                if (!toggleBtn) return;

                const newBtn = toggleBtn.cloneNode(true);
                toggleBtn.parentNode.replaceChild(newBtn, toggleBtn);

                newBtn.addEventListener('click', () => {
                    const currentTheme = localStorage.getItem('theme') || 'light';
                    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                    
                    localStorage.setItem('theme', newTheme);
                    this.apply(newTheme);
                    this.updateIcons(newTheme);
                    
                    document.cookie = 'theme=' + newTheme + '; path=/; max-age=31536000';
                });
            },
            
            apply: function(theme) {
                const html = document.documentElement;
                if (theme === 'dark') {
                    html.setAttribute('data-theme', 'dark');
                    html.classList.add('theme-dark');
                } else {
                    html.removeAttribute('data-theme');
                    html.classList.remove('theme-dark');
                }
            },
            
            updateIcons: function(theme) {
                const sunIcon = document.querySelector('.theme-icon-sun');
                const moonIcon = document.querySelector('.theme-icon-moon');

                if (sunIcon && moonIcon) {
                    if (theme === 'dark') {
                        sunIcon.style.display = 'none';
                        moonIcon.style.display = 'block';
                    } else {
                        sunIcon.style.display = 'block';
                        moonIcon.style.display = 'none';
                    }
                }
            }
        },

        loader: {
            waitForJQuery: function(callback, maxAttempts = 200) {
                var attempts = 0;
                
                function check() {
                    attempts++;
                    if (typeof window.jQuery !== 'undefined') {
                        if (typeof window.$ === 'undefined') window.$ = window.jQuery;
                        callback();
                    } else if (attempts < maxAttempts) {
                        setTimeout(check, 50);
                    } else {
                        console.error('jQuery failed to load after ' + maxAttempts + ' attempts');
                    }
                }
                check();
            },

            loadScriptWhenJQueryReady: function(src, callback) {
                this.waitForJQuery(function() {
                    var script = document.createElement('script');
                    script.src = src;
                    script.async = false;
                    script.defer = false;
                    if (callback) script.onload = callback;
                    document.head.appendChild(script);
                });
            },

            initJQueryFallback: function(localSrc, cdnSrc, altCdnSrc) {
                if (typeof window.jQuery === 'undefined') {
                    var script = document.createElement('script');
                    script.src = cdnSrc;
                    script.async = false;
                    script.defer = false;
                    
                    script.onload = () => {
                        setTimeout(() => {
                            if (typeof window.jQuery !== 'undefined') {
                                if (typeof window.$ === 'undefined') window.$ = window.jQuery;
                                window.dispatchEvent(new Event('jquery-loaded'));
                            } else {
                                this.loadAlternateJQuery(altCdnSrc);
                            }
                        }, 50);
                    };
                    
                    script.onerror = () => this.loadAlternateJQuery(altCdnSrc);
                    document.head.appendChild(script);
                } else {
                    if (typeof window.$ === 'undefined') window.$ = window.jQuery;
                    window.dispatchEvent(new Event('jquery-loaded'));
                }
            },

            loadAlternateJQuery: function(src) {
                var script = document.createElement('script');
                script.src = src;
                script.async = false;
                script.onload = function() {
                    if (typeof window.jQuery !== 'undefined') {
                        if (typeof window.$ === 'undefined') window.$ = window.jQuery;
                        window.dispatchEvent(new Event('jquery-loaded'));
                    }
                };
                document.head.appendChild(script);
            }
        },

        interceptor: {
            init: function() {
                var observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        mutation.addedNodes.forEach(function(node) {
                            if (node.tagName === 'SCRIPT' && node.src) {
                                var jqueryDependent = /(toogle|colorpicker|select2|jquery\.|\.min\.js)/i.test(node.src);
                                
                                if (jqueryDependent && typeof window.jQuery === 'undefined') {
                                    var src = node.src;
                                    node.remove();
                                    
                                    AdminCore.loader.loadScriptWhenJQueryReady(src);
                                }
                            }
                        });
                    });
                });
                
                observer.observe(document.head, { childList: true, subtree: true });
            }
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            AdminCore.theme.init();
        });
    } else {
        AdminCore.theme.init();
    }

    AdminCore.interceptor.init();

    document.addEventListener('change', function(e) {
        if (e.target && e.target.classList.contains('changeLang')) {
            var url = "/admin/changeLang";
            if (e.target.value) {
                window.location.href = url + "?lang=" + e.target.value;
            }
        }
    });
    
    AdminCore.loader.waitForJQuery(function() {
        var $ = window.jQuery || window.$;
        $(document).on('change', '.changeLang', function() {
            var url = "/admin/changeLang";
            if ($(this).val()) {
                window.location.href = url + "?lang=" + $(this).val();
            }
        });
    });

})();
