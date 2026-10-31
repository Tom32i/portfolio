import { Color, Group, MeshBasicMaterial } from 'three';
import { GLTFLoader } from 'three/examples/jsm/loaders/GLTFLoader.js';
import { OBJECT_TINTS, OBJECTS } from './config';

/**
 * Screens ignore the lights: render them flat in their own colour.
 */
function isScreen(mesh) {
    return mesh.name === 'lid_display' || mesh.parent?.name === 'lid_display';
}

/**
 * Load the scene once and prepare its materials.
 *
 * Realtime mode (no `floor` mesh): lit standard materials with realtime shadows.
 * Baked mode (`floor` mesh present): light and shadows live in the textures, everything is unlit.
 *
 * @return {Promise<{ objects: Object<string, Object3D>, floor: ?Mesh, baked: boolean }>}
 */
export async function loadScene(url) {
    const { scene } = await new GLTFLoader().loadAsync(url);
    const floor = scene.getObjectByName('floor') ?? null;
    const baked = floor !== null;

    scene.updateMatrixWorld(true);
    scene.traverse((node) => {
        if (!node.isMesh || node === floor) {
            return;
        }

        const { material } = node;

        if (isScreen(node) || (baked && !material.map)) {
            node.material = new MeshBasicMaterial({ name: material.name, color: material.color.clone(), side: material.side });
        } else if (baked) {
            node.material = new MeshBasicMaterial({
                name: material.name,
                color: material.color.clone(),
                map: material.map,
                side: material.side,
                alphaTest: material.alphaTest,
                transparent: material.transparent,
            });
        }

        node.castShadow = node.receiveShadow = !baked;
    });

    const objects = {};

    for (const name of OBJECTS) {
        const object = scene.getObjectByName(name);

        if (object) {
            objects[name] = object;

            if (OBJECT_TINTS[name]) {
                tint(object, OBJECT_TINTS[name]);
            }
        }
    }

    if (floor) {
        floor.updateWorldMatrix(true, false);
    }

    return { objects, floor, baked };
}

/**
 * Multiply an object's materials by a colour, on its own copies (the baked material is shared).
 */
function tint(object, color) {
    object.traverse((node) => {
        if (node.isMesh && !isScreen(node)) {
            node.material = node.material.clone();
            node.material.color.multiply(new Color(color));
        }
    });
}

/**
 * Copy of an object, detached from its parents but keeping its world placement.
 */
export function placedClone(object) {
    const clone = object.clone(true);

    object.matrixWorld.decompose(clone.position, clone.quaternion, clone.scale);

    return clone;
}

/**
 * Group of placed clones for the given object names.
 */
export function cloneObjects(source, names) {
    const group = new Group();

    names.filter((name) => source.objects[name]).forEach((name) => group.add(placedClone(source.objects[name])));

    return group;
}
