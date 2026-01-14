(function() {
    'use strict';

    if (window.Livewire) {
    }

    const containerSelector = '#main-wrapper'; 
    const contentSelector = '.content-body'; 

    async function loadContent(url) {
        try {
            const loader = document.getElementById('overlay');
            if (loader) loader.style.display = 'block';

            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-PJAX': 'true'
                }
            });

            if (!response.ok) throw new Error('Network response was not ok');

            const html = await response.text();
            
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            
            const newContent = doc.querySelector(contentSelector);
            if (!newContent) {
                window.location.href = url;
                return;
            }

            const currentContent = document.querySelector(contentSelector);
            if (currentContent) {
                currentContent.innerHTML = newContent.innerHTML;
            }

            document.title = doc.title;

            history.pushState({}, '', url);

            reinitPlugins();

        } catch (error) {
            console.error('Navigation error:', error);
            window.location.href = url; 
        } finally {
            const loader = document.getElementById('overlay');
            if (loader) loader.style.display = 'none';
        }
    }

    function reinitPlugins() {
        if (typeof feather !== 'undefined') feather.replace();
        
        if (window.AdminCore) {
        }
        
        window.dispatchEvent(new Event('content-loaded'));
        window.dispatchEvent(new Event('jquery-loaded')); 
    }

    window.addEventListener('popstate', function() {
        loadContent(window.location.href);
    });

})();
