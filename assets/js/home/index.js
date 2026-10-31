import initNav from '../common/nav';

initNav();

// Videos only load and play while on screen, and never move with reduced motion.
const stillMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
const videoObserver = new IntersectionObserver((entries) => {
    entries.forEach(({ target: video, isIntersecting }) => {
        if (isIntersecting && !stillMotion.matches) {
            video.muted = true;
            video.play().catch(() => {});
        } else {
            video.pause();
        }
    });
}, { rootMargin: '200px' });

document.querySelectorAll('video[data-autoplay]').forEach((video) => videoObserver.observe(video));

// The 3D scene (three.js + GLB) loads once the page is idle, off the critical path.
function loadScene() {
    import('./scene/index.js')
        .then(({ default: initScene }) => initScene())
        .catch((error) => console.error('3D scene unavailable:', error));
}

if ('requestIdleCallback' in window) {
    requestIdleCallback(loadScene, { timeout: 2000 });
} else {
    setTimeout(loadScene, 300);
}
