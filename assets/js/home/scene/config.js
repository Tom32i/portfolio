/** Top-level objects of /3d/scene.glb */
export const OBJECTS = ['laptop', 'coffee_cup', 'gamepad', 'longboard', 'plant'];

/** Clicking an object in the hero scrolls to its section. */
export const LINKS = { gamepad: 'p-games', laptop: 'p-talks', plant: 'p-oss', coffee_cup: 'p-contact' };

/** Resting Y rotation of the hero group: the Blender bake is lit for this pose. */
export const REST_ANGLE = -0.35;
export const SWAY = 0.22;

export const COLORS = {
    coral: '#ff9aa6',
    ink: '#241a33',
};

export const DESKTOP = '(min-width: 901px)';
export const REDUCED_MOTION = '(prefers-reduced-motion: reduce)';
