// assets/js/main.js
document.addEventListener('DOMContentLoaded', () => {
    // Glass header opacity on scroll
    const header = document.querySelector('.glass-header');
    
    window.addEventListener('scroll', () => {
        if (window.scrollY > 10) {
            header.style.background = 'rgba(255, 255, 255, 0.95)';
            header.style.boxShadow = '0 4px 6px -1px rgb(0 0 0 / 0.1)';
        } else {
            header.style.background = 'rgba(255, 255, 255, 0.85)';
            header.style.boxShadow = '0 1px 2px 0 rgb(0 0 0 / 0.05)';
        }
    });

    // Mobile menu toggle
    const mobileToggle = document.querySelector('.mobile-menu-toggle');
    const mainNav = document.querySelector('.main-nav');

    if (mobileToggle && mainNav) {
        mobileToggle.addEventListener('click', () => {
            mainNav.style.display = mainNav.style.display === 'block' ? 'none' : 'block';
            if(mainNav.style.display === 'block') {
                mainNav.style.position = 'absolute';
                mainNav.style.top = '100%';
                mainNav.style.left = '0';
                mainNav.style.right = '0';
                mainNav.style.background = '#fff';
                mainNav.style.padding = '1rem';
                mainNav.style.borderBottom = '1px solid #E2E8F0';
                mainNav.style.boxShadow = '0 4px 6px -1px rgb(0 0 0 / 0.1)';
                mainNav.querySelector('ul').style.flexDirection = 'column';
                mainNav.querySelector('ul').style.gap = '1rem';
            }
        });
    }

    // Animate progress bars on scroll intersection
    const progressFills = document.querySelectorAll('.progress-fill');
    
    // Intersection observer to trigger animation slightly before they come fully into view
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const fill = entry.target;
                const targetWidth = fill.getAttribute('data-width');
                if (targetWidth) {
                    fill.style.width = targetWidth;
                }
                observer.unobserve(fill); // Only animate once
            }
        });
    }, { threshold: 0.1 });

    progressFills.forEach(fill => {
        fill.style.width = '0%'; // Start at 0
        observer.observe(fill);
    });
});
