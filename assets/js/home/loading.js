// A loading circle shows where each 3D view will appear, until its first frame is drawn.
// Set by script, so without JS (or 3D) no endless spinner is left behind.
const LOADING = 'is-loading';

export function markLoading(element) {
    element.classList.add(LOADING);
    element.setAttribute('aria-busy', 'true');
}

export function markLoaded(element) {
    if (element.classList.contains(LOADING)) {
        element.classList.remove(LOADING);
        element.removeAttribute('aria-busy');
    }
}
