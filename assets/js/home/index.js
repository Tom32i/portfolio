import initNav from '../common/nav';

initNav();

// Some browsers ignore the autoplay attribute until the video is explicitly played.
document.querySelectorAll('video[autoplay]').forEach((video) => {
    video.muted = true;
    video.play().catch(() => {});
});

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
