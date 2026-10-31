import { Box3, Group, Raycaster, Scene, Vector2, Vector3 } from 'three';
import { createCamera, fitCamera } from './camera';
import { HERO_MOTION, LINKS, OBJECTS, REST_ANGLE, SWAY, TURN_SPEED } from './config';
import { addRealtimeLights, bakedFloor } from './lighting';
import { cloneObjects } from './load';
import { createRenderer, watchContext } from './renderer';

/**
 * The hero desk: all objects, slow turntable (or sway), pointer tilt, click to scroll to a section.
 * Objects never move relative to the floor, so the baked shadows stay under them.
 * Has its own renderer (and so its own WebGL context).
 */
export default class HeroView {
    constructor(element, source, { desktop, still }) {
        this.element = element;
        this.trigger = element.closest('.intro-area') ?? element;
        this.hero = element.closest('.hero');
        this.desktop = desktop;
        this.still = still;
        this.visible = false;
        this.ready = true;
        this.time = 0;
        this.pointer = new Vector2(0, 0);
        this.tilt = new Vector2(0, 0);
        this.hovered = null;
        this.raycaster = new Raycaster();

        this.canvas = document.createElement('canvas');
        element.append(this.canvas);
        this.renderer = createRenderer({ canvas: this.canvas, shadows: !source.baked });
        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        watchContext(this.renderer, (ready) => { this.ready = ready; });

        this.scene = new Scene();
        this.camera = createCamera();
        this.objects = cloneObjects(source, OBJECTS);

        // Centre on the objects only (the baked floor is much bigger), floor moves with them.
        const box = new Box3().setFromObject(this.objects);
        const center = box.getCenter(new Vector3());

        this.size = box.getSize(new Vector3());
        this.radius = Math.max(Math.hypot(this.size.x, this.size.z) / 2, this.size.y * 0.8);

        const content = new Group();

        content.position.set(-center.x, -box.min.y, -center.z);
        content.add(this.objects);

        if (source.baked) {
            content.add(bakedFloor(source.floor));
        } else {
            addRealtimeLights(this.scene, 1024);
        }

        this.spin = new Group();
        this.spin.rotation.y = REST_ANGLE;
        this.spin.add(content);
        this.scene.add(this.spin);

        this.onPointerMove = this.onPointerMove.bind(this);
        this.onPointerLeave = this.onPointerLeave.bind(this);
        this.onClick = this.onClick.bind(this);

        this.trigger.addEventListener('pointermove', this.onPointerMove);
        this.trigger.addEventListener('pointerleave', this.onPointerLeave);
        this.trigger.addEventListener('click', this.onClick);

        this.resize();
    }

    get animated() {
        return !this.still.matches;
    }

    resize() {
        const width = this.canvas.clientWidth;
        const height = this.canvas.clientHeight;

        if (!width || !height) {
            return;
        }

        this.renderer.setSize(width, height, false);
        const desktop = this.desktop.matches;

        // On desktop the layer runs down to the first section; keep framing the objects as if it
        // only covered the hero (+ the 40px/96px overflow for shadows), so they keep their size and place.
        const frameHeight = desktop && this.hero ? Math.min(height, this.hero.offsetHeight + 136) : height;

        fitCamera(this.camera, width, height, this.radius, this.size.y, { zoom: 0.66, right: desktop, frameHeight });
    }

    /**
     * Top-level object under the given client position.
     */
    pick(clientX, clientY) {
        const rect = this.canvas.getBoundingClientRect();
        const ndc = new Vector2(((clientX - rect.left) / rect.width) * 2 - 1, -(((clientY - rect.top) / rect.height) * 2 - 1));

        this.raycaster.setFromCamera(ndc, this.camera);

        let object = this.raycaster.intersectObjects(this.objects.children, true)[0]?.object;

        while (object && object.parent !== this.objects) {
            object = object.parent;
        }

        return object ?? null;
    }

    onPointerMove(event) {
        const rect = this.canvas.getBoundingClientRect();

        this.pointer.set(((event.clientX - rect.left) / rect.width) * 2 - 1, ((event.clientY - rect.top) / rect.height) * 2 - 1);
        this.hovered = event.pointerType === 'mouse' && !event.target.closest('a, button') ? this.pick(event.clientX, event.clientY) : null;
        this.trigger.classList.toggle('is-pointing', Boolean(this.hovered && LINKS[this.hovered.name]));
    }

    onPointerLeave() {
        this.pointer.set(0, 0);
        this.hovered = null;
        this.trigger.classList.remove('is-pointing');
    }

    onClick(event) {
        if (event.target.closest('a, button')) {
            return;
        }

        const id = LINKS[this.pick(event.clientX, event.clientY)?.name];
        const section = id && document.getElementById(id);

        if (section) {
            section.scrollIntoView({ behavior: this.animated ? 'smooth' : 'auto' });
            history.replaceState(null, '', `#${id}`);
        }
    }

    /**
     * Rotation added to the rest angle.
     */
    motion() {
        switch (HERO_MOTION) {
            case 'turn':
                return (this.time * TURN_SPEED) % (Math.PI * 2);
            case 'sway':
                return Math.sin(this.time * 0.45) * SWAY;
            default:
                return 0;
        }
    }

    update(delta) {
        if (this.animated) {
            this.time += delta;
            this.tilt.x += (this.pointer.y * 0.06 - this.tilt.x) * 0.06;
            this.tilt.y += (this.pointer.x * 0.18 - this.tilt.y) * 0.06;
            this.spin.rotation.set(this.tilt.x, REST_ANGLE + this.tilt.y + this.motion(), 0);
        }

        this.renderer.render(this.scene, this.camera);
    }
}
