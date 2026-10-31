import { MathUtils, PerspectiveCamera } from 'three';

/**
 * Camera looking down at the objects, fitted to their bounding sphere.
 */
export function createCamera() {
    return new PerspectiveCamera(28, 1, 0.01, 20);
}

/**
 * @param {number} radius Bounding radius of the objects
 * @param {number} height Height of the objects
 * @param {object} options zoom: fit factor, right: frame the objects in the right part of a wide canvas
 */
export function fitCamera(camera, width, height, radius, objectHeight, { zoom, right = false }) {
    camera.aspect = width / height;

    const vertical = MathUtils.degToRad(camera.fov) / 2;
    const horizontal = Math.atan(Math.tan(vertical) * (right ? Math.min(camera.aspect, 0.95) : camera.aspect));
    const distance = (radius / Math.sin(Math.min(vertical, horizontal))) * zoom;

    camera.position.set(0, distance * 0.66, distance * 0.75);
    camera.lookAt(0, objectHeight * 0.18, 0);

    if (right) {
        camera.setViewOffset(width, height, -width * 0.24, 0, width, height);
    } else {
        camera.clearViewOffset();
    }

    camera.updateProjectionMatrix();
}
