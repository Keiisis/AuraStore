/**
 * AuraStore SaaS - Frontend Core Engine
 */

document.addEventListener('DOMContentLoaded', () => {

    // GSAP Entrance Animations for Storefront
    gsap.from('.store-info h1', {
        y: 40,
        opacity: 0,
        duration: 1.5,
        ease: "power4.out"
    });

    gsap.from('nav.glass', {
        scaleX: 0,
        opacity: 0,
        duration: 1.5,
        delay: 0.3,
        ease: "power4.out"
    });

    gsap.from('.model-view', {
        scale: 0.9,
        opacity: 0,
        duration: 2,
        ease: "expo.out"
    });

    // Custom Cursor logic from previous step could be integrated here...
});
