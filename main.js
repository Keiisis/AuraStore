/**
 * AuraStore — Interaction Engine
 * GSAP ScrollTrigger + Micro-interactions
 */

document.addEventListener('DOMContentLoaded', () => {

    gsap.registerPlugin(ScrollTrigger);

    // ═══ HERO ENTRANCE TIMELINE ═══
    const heroTL = gsap.timeline({ defaults: { ease: 'power3.out' } });
    const heroElements = document.querySelectorAll('.hero .anim-fade');

    heroTL
        .to(heroElements, {
            opacity: 1,
            y: 0,
            duration: 0.9,
            stagger: 0.15,
            delay: 0.3
        });

    // ═══ SCROLL REVEALS ═══
    const revealElements = document.querySelectorAll('.reveal');

    revealElements.forEach((el, i) => {
        gsap.to(el, {
            scrollTrigger: {
                trigger: el,
                start: 'top 88%',
                toggleActions: 'play none none none'
            },
            opacity: 1,
            y: 0,
            duration: 0.7,
            ease: 'power2.out',
            delay: i % 3 * 0.08  // Stagger within visual rows
        });
    });

    // ═══ NAV SHRINK ON SCROLL ═══
    const nav = document.querySelector('.main-nav');
    let lastScroll = 0;

    window.addEventListener('scroll', () => {
        const scrollY = window.scrollY;
        if (scrollY > 80) {
            nav.style.background = 'rgba(8,6,4,0.88)';
            nav.style.borderColor = 'rgba(254,117,1,0.12)';
        } else {
            nav.style.background = 'rgba(10,8,6,0.6)';
            nav.style.borderColor = 'rgba(254,117,1,0.08)';
        }
        lastScroll = scrollY;
    }, { passive: true });

    // ═══ MOBILE MENU ═══
    const menuToggle = document.getElementById('menuToggle');
    const mobileMenu = document.getElementById('mobileMenu');

    if (menuToggle && mobileMenu) {
        menuToggle.addEventListener('click', () => {
            const isActive = mobileMenu.classList.toggle('active');
            menuToggle.classList.toggle('active');
            menuToggle.setAttribute('aria-expanded', isActive);
            document.body.style.overflow = isActive ? 'hidden' : '';
        });

        // Close on link click
        mobileMenu.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                mobileMenu.classList.remove('active');
                menuToggle.classList.remove('active');
                menuToggle.setAttribute('aria-expanded', 'false');
                document.body.style.overflow = '';
            });
        });
    }

    // ═══ CATEGORY CARDS — ACCENT GLOW ON HOVER ═══
    const catCards = document.querySelectorAll('.cat-card');
    catCards.forEach(card => {
        const accent = getComputedStyle(card).getPropertyValue('--accent').trim();
        card.addEventListener('mouseenter', () => {
            card.style.boxShadow = `0 12px 36px ${accent}15`;
        });
        card.addEventListener('mouseleave', () => {
            card.style.boxShadow = '';
        });
    });

    // ═══ SMOOTH SCROLL FOR ANCHOR LINKS ═══
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', (e) => {
            const target = document.querySelector(anchor.getAttribute('href'));
            if (target) {
                e.preventDefault();
                const navHeight = nav ? nav.offsetHeight + 20 : 80;
                const targetPosition = target.getBoundingClientRect().top + window.scrollY - navHeight;
                window.scrollTo({ top: targetPosition, behavior: 'smooth' });
            }
        });
    });

    // ═══ FEATURE CARDS STAGGER ═══
    const featureCards = document.querySelectorAll('.feature-card');
    if (featureCards.length) {
        ScrollTrigger.batch(featureCards, {
            onEnter: (elements) => {
                gsap.to(elements, {
                    opacity: 1,
                    y: 0,
                    duration: 0.6,
                    stagger: 0.1,
                    ease: 'power2.out',
                    overwrite: true
                });
            },
            start: 'top 88%'
        });
    }

    // ═══ PRICING CARD FEATURED GLOW ═══
    const featuredCard = document.querySelector('.price-card.featured');
    if (featuredCard) {
        featuredCard.addEventListener('mousemove', (e) => {
            const rect = featuredCard.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            featuredCard.style.background = `radial-gradient(circle at ${x}px ${y}px, rgba(254,117,1,0.08), rgba(254,117,1,0.02) 50%, transparent 80%)`;
        });
        featuredCard.addEventListener('mouseleave', () => {
            featuredCard.style.background = 'rgba(254,117,1,0.04)';
        });
    }

    // ═══ PHONE MOCKUP PARALLAX ═══
    const phone = document.querySelector('.vto-phone');
    if (phone) {
        gsap.to(phone, {
            scrollTrigger: {
                trigger: '.hero',
                start: 'top top',
                end: 'bottom top',
                scrub: 1
            },
            y: -50,
            ease: 'none'
        });
    }

    // ═══ MARQUEE PAUSE ON HOVER ═══
    const proofTrack = document.querySelector('.proof-track');
    if (proofTrack) {
        const proofSection = proofTrack.parentElement;
        proofSection.addEventListener('mouseenter', () => {
            proofTrack.style.animationPlayState = 'paused';
        });
        proofSection.addEventListener('mouseleave', () => {
            proofTrack.style.animationPlayState = 'running';
        });
    }

    // ═══ PLAY DEMO BUTTON — Scroll to Features ═══
    const playDemo = document.getElementById('playDemo');
    if (playDemo) {
        playDemo.addEventListener('click', () => {
            const featuresSection = document.getElementById('features');
            if (featuresSection) {
                const navHeight = nav ? nav.offsetHeight + 20 : 80;
                const pos = featuresSection.getBoundingClientRect().top + window.scrollY - navHeight;
                window.scrollTo({ top: pos, behavior: 'smooth' });
            }
        });
    }

});
