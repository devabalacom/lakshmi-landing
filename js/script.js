(() => {
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const supportsObserver = 'IntersectionObserver' in window;

    const navToggle = document.getElementById('nav-toggle');
    const navMobile = document.getElementById('nav-mobile');
    const nav = document.querySelector('.nav');

    const initMobileMenu = () => {
        if (!navToggle || !navMobile) return;

        const close = () => {
            navMobile.classList.remove('open');
            navToggle.setAttribute('aria-expanded', 'false');
        };

        navToggle.addEventListener('click', () => {
            const open = navMobile.classList.toggle('open');
            navToggle.setAttribute('aria-expanded', String(open));
        });

        navMobile.addEventListener('click', (event) => {
            if (event.target instanceof HTMLAnchorElement) close();
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') close();
        });

        window.addEventListener('resize', () => {
            if (window.innerWidth >= 900) close();
        }, { passive: true });
    };

    const initAnchorScrolling = () => {
        document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
            anchor.addEventListener('click', function (event) {
                const href = this.getAttribute('href');
                if (!href || href === '#') return;
                const target = document.querySelector(href);
                if (!target) return;

                event.preventDefault();
                const headerOffset = nav ? nav.getBoundingClientRect().height : 0;
                const targetPosition = target.getBoundingClientRect().top + window.pageYOffset;
                const scrollPosition = Math.max(targetPosition - headerOffset - 8, 0);
                const behavior = prefersReducedMotion ? 'auto' : 'smooth';
                window.scrollTo({ top: scrollPosition, behavior });

                if (history.pushState) history.pushState(null, '', href);
            });
        });
    };

    const initRevealAnimations = () => {
        const fadeElements = document.querySelectorAll('.fade-up');

        if (prefersReducedMotion || !supportsObserver) {
            fadeElements.forEach((el) => el.classList.add('is-revealed'));
            return;
        }

        const revealObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                entry.target.classList.add('is-revealed');
                observer.unobserve(entry.target);
            });
        }, {
            threshold: 0.12,
            rootMargin: '0px 0px -6% 0px'
        });

        fadeElements.forEach((el) => revealObserver.observe(el));
    };

    const init = () => {
        initMobileMenu();
        initAnchorScrolling();
        initRevealAnimations();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, { once: true });
    } else {
        init();
    }
})();
