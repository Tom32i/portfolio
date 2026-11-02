import { DirectionalLight, HemisphereLight, Mesh, MeshBasicMaterial, PlaneGeometry, ShadowMaterial, SRGBColorSpace } from 'three';
import { COLORS } from './config';

/**
 * Realtime lights (no baked GLB yet): soft hemisphere + one shadow-casting sun, from the front-left.
 * Matches the Blender bake light, in world space, for the -0.35 rest angle; sun and sky are balanced
 * so the open floor reads exactly as the page coral.
 */
export function addRealtimeLights(scene, mapSize) {
    scene.add(new HemisphereLight('#fff6fb', '#c9a6e8', 1.79));

    const sun = new DirectionalLight('#ffffff', 2.7);

    sun.position.set(-1.3, 1.0, 0.06);
    sun.castShadow = true;
    sun.shadow.mapSize.set(mapSize, mapSize);
    Object.assign(sun.shadow.camera, { left: -1.1, right: 1.1, top: 1.1, bottom: -1.1, near: 0.1, far: 4 });
    sun.shadow.bias = -0.0004;
    sun.shadow.normalBias = 0.002;
    sun.shadow.radius = 8;
    scene.add(sun);

    // Shadow catcher: only the shadow is drawn, the page background shows through.
    const ground = new Mesh(new PlaneGeometry(6, 6), new ShadowMaterial({ color: COLORS.ink, opacity: 0.2 }));

    ground.rotation.x = -Math.PI / 2;
    ground.receiveShadow = true;
    scene.add(ground);
}

/**
 * Baked floor: unlit coral × shadow texture, alpha fading at the edges.
 */
export function bakedFloor(floor) {
    const clone = floor.clone();
    const map = floor.material.map;

    if (map) {
        map.colorSpace = SRGBColorSpace;
    }

    clone.material = new MeshBasicMaterial({ color: COLORS.coral, map, transparent: true, depthWrite: false });
    floor.matrixWorld.decompose(clone.position, clone.quaternion, clone.scale);

    return clone;
}
