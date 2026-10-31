import { Timer } from 'three';
import { DESKTOP, REDUCED_MOTION } from './config';
import HeroView from './HeroView';
import IconViews from './IconViews';
import { loadScene } from './load';

/**
 * Hero desk + section icons, rendered only while on screen.
 */
export default async function initScene() {
    const heroElement = document.querySelector('.hero-scene[data-scene]');
    const iconElements = Array.from(document.querySelectorAll('.scene-icon[data-scene]'));

    if (!heroElement && iconElements.length === 0) {
        return;
    }

    const source = await loadScene('/3d/scene.glb');
    const desktop = window.matchMedia(DESKTOP);
    const still = window.matchMedia(REDUCED_MOTION);
    const hero = heroElement ? new HeroView(heroElement, source, { desktop, still }) : null;
    const icons = iconElements.length ? new IconViews(iconElements, source, { still }) : null;
    const views = [hero, icons].filter(Boolean);
    const timer = new Timer();
    let frame = null;

    const active = (view) => view.ready && view.visible && !document.hidden;

    function render() {
        timer.update();

        const delta = Math.min(timer.getDelta(), 0.05);

        views.filter(active).forEach((view) => view.update(delta));
        frame = null;

        if (!still.matches) {
            schedule();
        }
    }

    // Keep looping only while something is visible; with reduced motion, draw single frames on demand.
    function schedule() {
        if (frame === null && views.some(active)) {
            frame = requestAnimationFrame(render);
        }
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(({ target, isIntersecting }) => {
            if (hero && target === hero.element) {
                hero.visible = isIntersecting;
            }

            icons?.icons.filter((icon) => icon.element === target).forEach((icon) => { icon.visible = isIntersecting; });
        });
        schedule();
    }, { rootMargin: '200px' });

    if (hero) {
        observer.observe(hero.element);
    }

    icons?.icons.forEach((icon) => observer.observe(icon.element));

    const resize = () => {
        views.forEach((view) => view.resize());
        schedule();
    };

    // After a lost GPU context comes back, redraw and restart the loop.
    views.forEach((view) => { view.onRestore = resize; });

    // Only the 3D elements' own size matters, not every change in page height.
    const resizeObserver = new ResizeObserver(resize);

    if (hero) {
        resizeObserver.observe(hero.element);
    }

    icons?.icons.forEach((icon) => resizeObserver.observe(icon.element));
    desktop.addEventListener('change', resize);
    still.addEventListener('change', schedule);
    document.addEventListener('visibilitychange', () => {
        timer.reset();
        schedule();
    });
}
