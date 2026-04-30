// assets/js/main.js
document.addEventListener('DOMContentLoaded', () => {
    // Keep the dark header style and only adjust elevation on scroll.
    const header = document.querySelector('.glass-header');

    if (header) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 10) {
                header.style.boxShadow = '0 10px 25px rgba(0, 0, 0, 0.35)';
            } else {
                header.style.boxShadow = '0 8px 32px rgba(0, 0, 0, 0.2)';
            }
        });
    }

    // Mobile menu toggle (class-based so CSS controls responsive behavior)
    const mobileToggle = document.querySelector('.mobile-menu-toggle');
    const mainNav = document.querySelector('.main-nav');

    if (mobileToggle && mainNav) {
        mobileToggle.addEventListener('click', () => {
            mainNav.classList.toggle('mobile-open');
            mobileToggle.setAttribute('aria-expanded', mainNav.classList.contains('mobile-open') ? 'true' : 'false');
        });

        document.addEventListener('click', (event) => {
            const target = event.target;
            if (!mainNav.contains(target) && !mobileToggle.contains(target)) {
                mainNav.classList.remove('mobile-open');
                mobileToggle.setAttribute('aria-expanded', 'false');
            }
        });

        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                mainNav.classList.remove('mobile-open');
                mobileToggle.setAttribute('aria-expanded', 'false');
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
