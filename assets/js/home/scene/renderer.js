import { NoToneMapping, PCFShadowMap, SRGBColorSpace, WebGLRenderer } from 'three';

/**
 * Transparent renderer, with exact flat colours (no tone mapping).
 */
export function createRenderer({ canvas, shadows }) {
    const renderer = new WebGLRenderer({ canvas, antialias: true, alpha: true, powerPreference: 'low-power' });

    renderer.outputColorSpace = SRGBColorSpace;
    renderer.toneMapping = NoToneMapping;
    renderer.setClearColor(0x000000, 0);
    renderer.shadowMap.enabled = shadows;
    renderer.shadowMap.type = PCFShadowMap;

    return renderer;
}

/**
 * Pause rendering while the GPU context is lost; three.js restores its state on its own.
 */
export function watchContext(renderer, onChange) {
    const canvas = renderer.domElement;

    canvas.addEventListener('webglcontextlost', (event) => {
        event.preventDefault();
        onChange(false);
    });
    canvas.addEventListener('webglcontextrestored', () => onChange(true));
}
