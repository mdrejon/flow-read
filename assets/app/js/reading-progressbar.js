(function() {
    'use strict';

    const ProgressBar = {
        init: function() {
            this.progressBar = document.getElementById('flowread-progress-bar');
            
            if (!this.progressBar) {
                return;
            }

            this.progressElement = this.progressBar.querySelector('.progress');
            this.attachScrollListener();
            this.updateProgress(); // Initial update
        },

        attachScrollListener: function() {
            let ticking = false;

            window.addEventListener('scroll', () => {
                if (!ticking) {
                    window.requestAnimationFrame(() => {
                        this.updateProgress();
                        ticking = false;
                    });
                    ticking = true;
                }
            }, { passive: true });
        },

        updateProgress: function() {
            const windowHeight = window.innerHeight;
            const documentHeight = document.documentElement.scrollHeight - windowHeight;
            const scrollPosition = window.scrollY;

            // Calculate scroll percentage
            let scrollPercentage = 0;
            if (documentHeight > 0) {
                scrollPercentage = (scrollPosition / documentHeight) * 100;
            }

            // Update progress bar width
            this.progressElement.style.width = scrollPercentage + '%';
        }
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            ProgressBar.init();
        });
    } else {
        ProgressBar.init();
    }
})();