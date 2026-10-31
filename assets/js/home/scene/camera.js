import { MathUtils, PerspectiveCamera, Vector3 } from 'three';

/**
 * Camera looking down at the objects, fitted to their bounding sphere.
 */
export function createCamera() {
    return new PerspectiveCamera(28, 1, 0.01, 20);
}

/**
 * @param {number} radius Bounding radius of the objects
 * @param {number} objectHeight Height of the objects
 * @param {object} options
 *   zoom: fit factor
 *   right: frame the objects in the right part of a wide canvas
 *   frameHeight: height the framing is computed for; a taller canvas extends the view below it
 *                without scaling or moving the objects
 */
export function fitCamera(camera, width, height, radius, objectHeight, { zoom, right = false, frameHeight = height }) {
    camera.aspect = width / frameHeight;

    const vertical = MathUtils.degToRad(camera.fov) / 2;
    const horizontal = Math.atan(Math.tan(vertical) * (right ? Math.min(camera.aspect, 0.95) : camera.aspect));
    const distance = (radius / Math.sin(Math.min(vertical, horizontal))) * zoom;

    camera.position.set(0, distance * 0.66, distance * 0.75);
    camera.lookAt(0, objectHeight * 0.18, 0);

    if (right || frameHeight !== height) {
        camera.setViewOffset(width, frameHeight, right ? -width * 0.24 : 0, 0, width, height);
    } else {
        camera.clearViewOffset();
    }

    camera.updateProjectionMatrix();
}

const VIEW_DIRECTION = new Vector3(0, 0.66, 0.75).normalize();

/**
 * Frame a sphere, centred in the canvas: used for a single object turning on itself,
 * whose swept volume always stays inside that sphere, so it never crops while turning.
 */
export function fitSphere(camera, width, height, center, radius, { margin = 1 } = {}) {
    camera.aspect = width / height;

    const vertical = MathUtils.degToRad(camera.fov) / 2;
    const horizontal = Math.atan(Math.tan(vertical) * camera.aspect);
    const distance = (radius / Math.sin(Math.min(vertical, horizontal))) * margin;

    camera.position.copy(center).addScaledVector(VIEW_DIRECTION, distance);
    camera.lookAt(center);
    camera.clearViewOffset();
    camera.updateProjectionMatrix();
}
