/** Top-level objects of /3d/scene.glb */
export const OBJECTS = ['laptop', 'coffee_cup', 'gamepad', 'longboard', 'plant'];

/** Clicking an object in the hero scrolls to its section. */
export const LINKS = { gamepad: 'p-games', laptop: 'p-talks', plant: 'p-oss', coffee_cup: 'p-contact' };

/** Resting Y rotation of the hero group: the Blender bake is lit for this pose. */
export const REST_ANGLE = -0.35;

/**
 * Hero motion around the rest angle: 'still' (only the pointer parallax), 'turn' (continuous turntable)
 * or 'sway' (back and forth).
 */
export const HERO_MOTION = 'still';
/** Turntable speed in rad/s: about one revolution per minute. */
export const TURN_SPEED = 0.1;
export const SWAY = 0.22;

/** Colour multiplied onto an object's (baked) material, e.g. to dim the white gamepad. */
export const OBJECT_TINTS = {
    gamepad: '#e4dfe8',
};

export const COLORS = {
    coral: '#ff9aa6',
    ink: '#241a33',
};

export const DESKTOP = '(min-width: 901px)';
export const REDUCED_MOTION = '(prefers-reduced-motion: reduce)';
