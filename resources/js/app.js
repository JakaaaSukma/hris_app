import './bootstrap';
import Alpine from 'alpinejs';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import Swiper from 'swiper';
import { A11y, EffectCoverflow, Keyboard, Navigation, Pagination } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/effect-coverflow';
import 'swiper/css/pagination';

gsap.registerPlugin(ScrollTrigger);

window.Alpine = Alpine;

const prefersReducedMotion = () =>
    window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/**
 * Skiper19 — SVG Follow Scroll + Top Header Progress Rail
 * Draws architectural SVG strokes continuously in sync with vertical scroll.
 */
function initSkiperScrollFlow() {
    const reduced = prefersReducedMotion();

    // Global top hairline scroll indicator inside <header>
    const topRail = document.getElementById('top-scroll-rail');
    if (topRail) {
        const updateTopRail = () => {
            const doc = document.documentElement;
            const scrollable = doc.scrollHeight - window.innerHeight;
            const progress = scrollable > 0 ? Math.min(1, Math.max(0, window.scrollY / scrollable)) : 0;
            topRail.style.transform = `scaleX(${progress})`;
        };
        updateTopRail();
        window.addEventListener('scroll', updateTopRail, { passive: true });
        window.addEventListener('resize', updateTopRail, { passive: true });
    }

    // Skiper19 flowing SVG paths
    const paths = document.querySelectorAll('[data-skiper-scroll-path]');
    paths.forEach((path) => {
        if (typeof path.getTotalLength !== 'function') return;
        const length = path.getTotalLength();
        const initialFraction = parseFloat(path.getAttribute('data-initial-progress') || '0.32');

        path.style.strokeDasharray = `${length}`;

        if (reduced) {
            path.style.strokeDashoffset = '0';
            return;
        }

        path.style.strokeDashoffset = `${length * (1 - initialFraction)}`;

        const triggerEl = path.closest('[data-skiper-scroll-container]') || document.documentElement;
        gsap.to(path, {
            strokeDashoffset: 0,
            ease: 'none',
            scrollTrigger: {
                trigger: triggerEl,
                start: 'top top',
                end: 'bottom bottom',
                scrub: 0.45,
            },
        });
    });

    // Subtle single-pass entrance choreography for marked sections
    if (!reduced) {
        const reveals = document.querySelectorAll('[data-reveal]');
        reveals.forEach((el, idx) => {
            gsap.fromTo(
                el,
                { opacity: 0, y: 14 },
                {
                    opacity: 1,
                    y: 0,
                    duration: 0.48,
                    delay: Math.min(idx * 0.05, 0.2),
                    ease: 'power3.out',
                    clearProps: 'transform,opacity',
                    scrollTrigger: {
                        trigger: el,
                        start: 'top 92%',
                        once: true,
                    },
                }
            );
        });
    }
}

/**
 * Skiper49 — Inverted Perspective 3D Carousel (Swiper EffectCoverflow)
 */
function initSkiperCarousels() {
    const containers = document.querySelectorAll('[data-skiper-carousel]');
    containers.forEach((root) => {
        const swiperEl = root.querySelector('.swiper');
        if (!swiperEl) return;

        const nextBtn = root.querySelector('[data-carousel-next]');
        const prevBtn = root.querySelector('[data-carousel-prev]');
        const paginationEl = root.querySelector('[data-carousel-pagination]');
        const initialSlide = Number(root.getAttribute('data-initial-slide') || '0');

        new Swiper(swiperEl, {
            modules: [EffectCoverflow, Navigation, Pagination, Keyboard, A11y],
            effect: 'coverflow',
            grabCursor: true,
            centeredSlides: true,
            slidesPerView: 'auto',
            initialSlide,
            speed: prefersReducedMotion() ? 0 : 480,
            spaceBetween: 20,
            coverflowEffect: {
                rotate: -30,
                stretch: 0,
                depth: 135,
                modifier: 1,
                slideShadows: false,
            },
            keyboard: {
                enabled: true,
                onlyInViewport: true,
            },
            navigation: nextBtn && prevBtn ? { nextEl: nextBtn, prevEl: prevBtn } : false,
            pagination: paginationEl ? { el: paginationEl, clickable: true } : false,
        });
    });
}

/**
 * Crisp tabular-nums entrance counter (Skiper37 feel, zero infinite loops)
 */
function initAnimatedNumbers() {
    if (prefersReducedMotion()) return;

    document.querySelectorAll('[data-count-to]').forEach((el) => {
        const raw = el.getAttribute('data-count-to');
        const target = Number(raw);
        if (!Number.isFinite(target) || target === 0) return;

        const decimals = Number(el.getAttribute('data-count-decimals') || '0');
        const suffix = el.getAttribute('data-count-suffix') || '';
        const counter = { val: 0 };

        gsap.to(counter, {
            val: target,
            duration: 0.75,
            ease: 'power2.out',
            scrollTrigger: {
                trigger: el,
                start: 'top 95%',
                once: true,
            },
            onUpdate: () => {
                el.textContent =
                    (decimals > 0
                        ? counter.val.toFixed(decimals)
                        : Math.round(counter.val).toLocaleString('id-ID')) + suffix;
            },
        });
    });
}

/**
 * Keyboard shortcut Cmd/Ctrl+K or '/' focuses global directory search cleanly.
 */
function initGlobalShortcuts() {
    window.addEventListener('keydown', (e) => {
        const activeTag = document.activeElement?.tagName;
        const isTyping = activeTag === 'INPUT' || activeTag === 'TEXTAREA' || activeTag === 'SELECT';

        if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
            const search = document.getElementById('global-nav-search');
            if (search) {
                e.preventDefault();
                search.focus();
                search.select();
            }
        } else if (!isTyping && e.key === '/') {
            const search = document.getElementById('global-nav-search');
            if (search) {
                e.preventDefault();
                search.focus();
            }
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initSkiperScrollFlow();
    initSkiperCarousels();
    initAnimatedNumbers();
    initGlobalShortcuts();
});

Alpine.start();
