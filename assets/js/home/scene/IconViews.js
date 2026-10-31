import { Box3, Group, Scene, Vector3 } from 'three';
import { createCamera, fitCamera } from './camera';
import { REST_ANGLE } from './config';
import { addRealtimeLights } from './lighting';
import { placedClone } from './load';
import { createRenderer, watchContext } from './renderer';

const PIXEL_RATIO = Math.min(window.devicePixelRatio, 1.5);
const TURN_SPEED = 0.35;

/**
 * One object alone, slowly turning.
 */
class Icon {
    constructor(element, object, baked) {
        this.element = element;
        this.visible = false;
        this.angle = REST_ANGLE;
        this.canvas = document.createElement('canvas');
        this.context = this.canvas.getContext('2d');
        element.append(this.canvas);

        this.scene = new Scene();
        this.camera = createCamera();

        const clone = placedClone(object);
        const box = new Box3().setFromObject(clone);
        const center = box.getCenter(new Vector3());

        this.size = box.getSize(new Vector3());
        this.radius = Math.max(Math.hypot(this.size.x, this.size.z) / 2, this.size.y * 0.8);
        clone.position.sub(new Vector3(center.x, box.min.y, center.z));

        this.spin = new Group();
        this.spin.add(clone);
        this.spin.rotation.y = this.angle;
        this.scene.add(this.spin);

        // Section backgrounds are mint, lilac or lemon: no baked coral floor here.
        if (!baked) {
            addRealtimeLights(this.scene, 512);
        }

        this.resize();
    }

    resize() {
        this.width = Math.round(this.element.clientWidth * PIXEL_RATIO);
        this.height = Math.round(this.element.clientHeight * PIXEL_RATIO);
        this.canvas.width = this.width;
        this.canvas.height = this.height;

        if (this.width && this.height) {
            fitCamera(this.camera, this.width, this.height, this.radius, this.size.y, { zoom: 0.92 });
        }
    }
}

/**
 * Section icons, all drawn by a single shared offscreen renderer and copied into 2D canvases,
 * so the page never holds more than two WebGL contexts (hero + icons).
 */
export default class IconViews {
    constructor(elements, source, { still }) {
        this.still = still;
        this.ready = true;
        this.renderer = createRenderer({ canvas: document.createElement('canvas'), shadows: !source.baked });
        this.renderer.setPixelRatio(1);
        this.renderer.setScissorTest(true);
        watchContext(this.renderer, (ready) => { this.ready = ready; });

        this.icons = elements
            .filter((element) => source.objects[element.dataset.scene])
            .map((element) => new Icon(element, source.objects[element.dataset.scene], source.baked));

        this.resize();
    }

    get animated() {
        return !this.still.matches;
    }

    get visible() {
        return this.icons.some((icon) => icon.visible);
    }

    resize() {
        this.icons.forEach((icon) => icon.resize());
        this.renderer.setSize(
            Math.max(1, ...this.icons.map((icon) => icon.width)),
            Math.max(1, ...this.icons.map((icon) => icon.height)),
            false,
        );

        // Resizing a canvas wipes it: redraw every icon, including the off-screen ones.
        if (this.ready) {
            this.icons.forEach((icon) => this.draw(icon));
        }
    }

    update(delta) {
        this.icons.filter((icon) => icon.visible).forEach((icon) => {
            if (this.animated) {
                icon.angle += delta * TURN_SPEED;
                icon.spin.rotation.y = icon.angle;
            }

            this.draw(icon);
        });
    }

    /**
     * Render in the bottom-left corner of the shared canvas, then copy that area into the icon.
     */
    draw(icon) {
        if (!icon.width || !icon.height) {
            return;
        }

        const { height } = this.renderer.domElement;

        this.renderer.setViewport(0, 0, icon.width, icon.height);
        this.renderer.setScissor(0, 0, icon.width, icon.height);
        this.renderer.render(icon.scene, icon.camera);
        icon.context.clearRect(0, 0, icon.width, icon.height);
        icon.context.drawImage(this.renderer.domElement, 0, height - icon.height, icon.width, icon.height, 0, 0, icon.width, icon.height);
    }
}
