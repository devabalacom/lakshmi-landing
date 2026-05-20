(() => {
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const supportsObserver = 'IntersectionObserver' in window;
    const supportsAnimate = typeof Element !== 'undefined' && typeof Element.prototype.animate === 'function';
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');
    const header = document.querySelector('header');

    const initMobileMenu = () => {
        if (!mobileMenuBtn || !mobileMenu) {
            return;
        }

        const focusableSelector = [
            'a[href]',
            'button:not([disabled])',
            'input:not([disabled])',
            'select:not([disabled])',
            'textarea:not([disabled])',
            '[tabindex]:not([tabindex="-1"])'
        ].join(', ');

        let previousBodyOverflow = '';

        const isOpen = () => !mobileMenu.classList.contains('hidden');

        const setAccessibilityState = (open) => {
            mobileMenuBtn.setAttribute('aria-expanded', String(open));
            mobileMenuBtn.setAttribute('aria-label', open ? 'Закрыть меню' : 'Открыть меню');
            mobileMenu.setAttribute('aria-hidden', String(!open));
        };

        const lockScroll = () => {
            if (!previousBodyOverflow) {
                previousBodyOverflow = document.body.style.overflow;
            }
            document.body.style.overflow = 'hidden';
        };

        const unlockScroll = () => {
            document.body.style.overflow = previousBodyOverflow;
            previousBodyOverflow = '';
        };

        const focusFirstItem = () => {
            const firstFocusable = mobileMenu.querySelector(focusableSelector);
            if (firstFocusable && typeof firstFocusable.focus === 'function') {
                firstFocusable.focus({ preventScroll: true });
            }
        };

        const closeMenu = ({ focusButton = true } = {}) => {
            if (!isOpen()) {
                setAccessibilityState(false);
                unlockScroll();
                return;
            }

            mobileMenu.classList.add('hidden');
            setAccessibilityState(false);
            unlockScroll();

            if (focusButton && typeof mobileMenuBtn.focus === 'function') {
                mobileMenuBtn.focus({ preventScroll: true });
            }
        };

        const openMenu = () => {
            if (isOpen()) {
                return;
            }

            mobileMenu.classList.remove('hidden');
            setAccessibilityState(true);
            lockScroll();

            window.requestAnimationFrame(focusFirstItem);
        };

        mobileMenuBtn.setAttribute('aria-controls', 'mobile-menu');
        setAccessibilityState(false);

        mobileMenuBtn.addEventListener('click', (event) => {
            event.preventDefault();
            if (isOpen()) {
                closeMenu();
            } else {
                openMenu();
            }
        });

        mobileMenu.addEventListener('click', (event) => {
            const target = event.target;
            if (target instanceof HTMLAnchorElement && target.getAttribute('href') !== '#') {
                closeMenu({ focusButton: false });
            }
        });

        document.addEventListener('click', (event) => {
            if (!isOpen()) {
                return;
            }

            const target = event.target;
            if (target instanceof Node && (mobileMenu.contains(target) || mobileMenuBtn.contains(target))) {
                return;
            }

            closeMenu({ focusButton: false });
        });

        document.addEventListener('keydown', (event) => {
            if (!isOpen()) {
                return;
            }

            if (event.key === 'Escape') {
                event.preventDefault();
                closeMenu();
                return;
            }

            if (event.key !== 'Tab') {
                return;
            }

            const focusables = Array.from(mobileMenu.querySelectorAll(focusableSelector)).filter((element) => {
                return element instanceof HTMLElement && !element.hasAttribute('disabled') && element.offsetParent !== null;
            });

            if (focusables.length === 0) {
                event.preventDefault();
                mobileMenuBtn.focus({ preventScroll: true });
                return;
            }

            const firstFocusable = focusables[0];
            const lastFocusable = focusables[focusables.length - 1];
            const activeElement = document.activeElement;

            if (event.shiftKey && activeElement === firstFocusable) {
                event.preventDefault();
                lastFocusable.focus({ preventScroll: true });
            } else if (!event.shiftKey && activeElement === lastFocusable) {
                event.preventDefault();
                firstFocusable.focus({ preventScroll: true });
            }
        });

        window.addEventListener('resize', () => {
            if (window.innerWidth >= 768 && isOpen()) {
                closeMenu({ focusButton: false });
            }
        }, { passive: true });
    };

    const initAnchorScrolling = () => {
        document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
            anchor.addEventListener('click', function (event) {
                const href = this.getAttribute('href');

                if (!href || href === '#') {
                    return;
                }

                const target = document.querySelector(href);
                if (!target) {
                    return;
                }

                event.preventDefault();

                const headerOffset = header ? header.getBoundingClientRect().height : 0;
                const targetPosition = target.getBoundingClientRect().top + window.pageYOffset;
                const scrollPosition = Math.max(targetPosition - headerOffset - 8, 0);
                const behavior = prefersReducedMotion ? 'auto' : 'smooth';

                window.scrollTo({
                    top: scrollPosition,
                    behavior
                });

                if (history.pushState) {
                    history.pushState(null, '', href);
                } else {
                    window.location.hash = href;
                }
            });
        });
    };

    const initHeaderScrollEffect = () => {
        if (!header) {
            return;
        }

        const updateHeaderState = () => {
            const currentScroll = window.pageYOffset || document.documentElement.scrollTop || 0;
            header.classList.toggle('shadow-lg', currentScroll > 100);
        };

        updateHeaderState();
        window.addEventListener('scroll', updateHeaderState, { passive: true });
    };

    const initRevealAnimations = () => {
        if (prefersReducedMotion || !supportsObserver || !supportsAnimate) {
            return;
        }

        const revealObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }

                observer.unobserve(entry.target);
                entry.target.animate(
                    [
                        { opacity: 0, transform: 'translateY(16px)' },
                        { opacity: 1, transform: 'translateY(0)' }
                    ],
                    {
                        duration: 650,
                        easing: 'cubic-bezier(0.16, 1, 0.3, 1)',
                        fill: 'both'
                    }
                );
            });
        }, {
            threshold: 0.12,
            rootMargin: '0px 0px -8% 0px'
        });

        document.querySelectorAll('section').forEach((section) => {
            revealObserver.observe(section);
        });
    };

    const init = () => {
        initMobileMenu();
        initAnchorScrolling();
        initHeaderScrollEffect();
        initRevealAnimations();

        console.log('%cЛакшми - Производство технического текстиля', 'color: #2563eb; font-size: 20px; font-weight: bold;');
        console.log('%cСвяжитесь с нами: +7 499 647-72-81', 'color: #6b7280; font-size: 14px;');
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, { once: true });
    } else {
        init();
    }
})();
